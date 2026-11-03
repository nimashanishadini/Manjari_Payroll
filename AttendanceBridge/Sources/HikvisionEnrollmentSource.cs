using System.Net;
using System.Text;
using System.Text.Json;

// ISAPI request/response shape follows the supplied AttendanceSync example.
// Collect a complete snapshot before sending anything; never treat a partial search as success.
internal sealed class HikvisionEnrollmentSource
{
    public async Task<IReadOnlyList<Enrollment>> ReadAllAsync(CancellationToken cancellationToken)
    {
        if (!Uri.TryCreate(Environment.GetEnvironmentVariable("HIKVISION_URL"), UriKind.Absolute, out var origin)
            || origin.UserInfo.Length != 0 || origin.AbsolutePath != "/" || origin.Query.Length != 0 || origin.Fragment.Length != 0
            || (origin.Scheme != "https" && origin.Scheme != "http"))
            throw new ArgumentException("HIKVISION_URL must be the device HTTP/HTTPS origin, including its ISAPI port.");
        if (origin.Scheme == "http" && !origin.IsLoopback
            && Environment.GetEnvironmentVariable("HIKVISION_ALLOW_HTTP") != "true")
            throw new ArgumentException("For devices without HTTPS on a trusted LAN, explicitly set HIKVISION_ALLOW_HTTP=true.");
        var username = Environment.GetEnvironmentVariable("HIKVISION_USERNAME");
        var password = Environment.GetEnvironmentVariable("HIKVISION_PASSWORD");
        if (string.IsNullOrWhiteSpace(username) || string.IsNullOrEmpty(password))
            throw new ArgumentException("Set HIKVISION_USERNAME and HIKVISION_PASSWORD for device authentication.");
        var credentials = new CredentialCache();
        credentials.Add(origin, "Digest", new NetworkCredential(username, password));
        // Basic credentials must not be sent over plaintext LAN HTTP.
        if (origin.Scheme == "https") credentials.Add(origin, "Basic", new NetworkCredential(username, password));
        using var handler = new HttpClientHandler { Credentials = credentials, PreAuthenticate = false, AllowAutoRedirect = false, UseCookies = false };
        using var client = new HttpClient(handler) { Timeout = TimeSpan.FromSeconds(30) };
        client.DefaultRequestHeaders.ExpectContinue = false;
        var endpoint = new Uri(origin, "ISAPI/AccessControl/UserInfo/Search?format=json");
        var searchId = Guid.NewGuid().ToString("N");
        var users = new List<Enrollment>();
        var seen = new HashSet<string>(StringComparer.Ordinal);
        const int pageSize = 30;
        int? totalExpected = null;
        for (var page = 0; page < 1000; page++)
        {
            var payload = new { UserInfoSearchCond = new { searchID = searchId, searchResultPosition = users.Count, maxResults = pageSize } };
            using var body = new StringContent(JsonSerializer.Serialize(payload), Encoding.UTF8, "application/json");
            using var response = await client.PostAsync(endpoint, body, cancellationToken);
            if (!response.IsSuccessStatusCode)
                throw new ArgumentException($"Hikvision user search failed (HTTP {(int)response.StatusCode}). No partial snapshot imported. Check ISAPI support/credentials.");
            using var doc = JsonDocument.Parse(await response.Content.ReadAsStringAsync(cancellationToken));
            if (!doc.RootElement.TryGetProperty("UserInfoSearch", out var search))
                throw new ArgumentException("Unsupported Hikvision user-search response. No partial snapshot imported.");
            if (search.TryGetProperty("totalMatches", out var total))
            {
                var count = total.GetInt32();
                if (count < 0 || (totalExpected.HasValue && totalExpected != count))
                    throw new ArgumentException("Device users changed during pagination. Retry for a consistent snapshot.");
                totalExpected = count;
            }
            var hasRows = search.TryGetProperty("UserInfo", out var rows);
            var countOnPage = hasRows ? rows.GetArrayLength() : 0;
            var responseStatus = search.TryGetProperty("responseStatusStrg", out var status) ? status.GetString() : null;
            if (responseStatus is not (null or "OK" or "MORE" or "NO MATCH"))
                throw new ArgumentException("Hikvision search did not report a successful result.");
            if (!hasRows && !(totalExpected == users.Count || responseStatus == "NO MATCH"))
                throw new ArgumentException("Hikvision search is missing its user list. No partial snapshot imported.");
            if (countOnPage > pageSize) throw new ArgumentException("Unexpected Hikvision page size.");
            var fetchedAt = DateTimeOffset.UtcNow.ToString("yyyy-MM-dd'T'HH:mm:ss'Z'");
            if (hasRows)
            {
                foreach (var item in rows.EnumerateArray())
                {
                    if (!item.TryGetProperty("employeeNo", out var id))
                        throw new ArgumentException("Hikvision user has no enrollment ID. Import stopped without staging partial data.");
                    var employeeId = id.ValueKind == JsonValueKind.String ? id.GetString()
                        : id.ValueKind == JsonValueKind.Number ? id.GetRawText() : null;
                    if (employeeId is null || !seen.Add(employeeId))
                        throw new ArgumentException("Missing/duplicate enrollment ID or repeated Hikvision page. Retry the search.");
                    var name = item.TryGetProperty("name", out var n) && n.ValueKind != JsonValueKind.Null ? n.GetString() : null;
                    var enrollment = new Enrollment(employeeId, name, fetchedAt);
                    BridgeApplication.Validate(enrollment);
                    users.Add(enrollment);
                }
            }
            if (totalExpected.HasValue && users.Count > totalExpected)
                throw new ArgumentException("Hikvision search exceeded its reported total.");
            if (countOnPage == 0 || responseStatus == "OK" || responseStatus == "NO MATCH"
                || (totalExpected.HasValue && users.Count == totalExpected)
                || (!totalExpected.HasValue && responseStatus != "MORE" && countOnPage < pageSize))
            {
                if ((totalExpected.HasValue && users.Count != totalExpected) || responseStatus == "MORE")
                    throw new ArgumentException("Incomplete Hikvision search. No partial snapshot imported; retry later.");
                return users;
            }
        }
        throw new ArgumentException("Hikvision search exceeded the page limit. No partial snapshot imported.");
    }
}
