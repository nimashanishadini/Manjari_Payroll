using System.Net;
using System.Net.Http.Headers;
using System.Text;
using System.Text.Json;
using System.Text.RegularExpressions;

internal static class BridgeApiClient
{
    public static async Task<int> SendAsync(string payload, CancellationToken cancellationToken, bool punch = false)
    {
            var originText = Environment.GetEnvironmentVariable("BRIDGE_API_URL");
            if (!Uri.TryCreate(originText, UriKind.Absolute, out var origin)
                || origin.UserInfo.Length > 0 || origin.AbsolutePath != "/" || origin.Query.Length > 0 || origin.Fragment.Length > 0
                || (origin.Scheme != "https" && !(origin.Scheme == "http" && origin.IsLoopback)))
                throw new ArgumentException("BRIDGE_API_URL must be an HTTPS server origin, or loopback HTTP for local testing.");
            if (!long.TryParse(Environment.GetEnvironmentVariable("BRIDGE_DEVICE_ID"), out var deviceId) || deviceId <= 0)
                throw new ArgumentException("BRIDGE_DEVICE_ID must be a positive registered device ID.");
            var token = Environment.GetEnvironmentVariable("BRIDGE_TOKEN");
            if (token is null || !Regex.IsMatch(token, "\\A[a-fA-F0-9]{64}\\z"))
                throw new ArgumentException("BRIDGE_TOKEN must be the 64-character token issued by Laravel.");

            // Do not follow redirects carrying credentials; do not disable TLS validation.
            using var handler = new HttpClientHandler { AllowAutoRedirect = false, UseCookies = false };
            using var client = new HttpClient(handler) { Timeout = TimeSpan.FromSeconds(30) };
            client.DefaultRequestHeaders.Authorization = new AuthenticationHeaderValue("Bearer", token);
            client.DefaultRequestHeaders.Accept.Add(new MediaTypeWithQualityHeaderValue("application/json"));
            var resource = punch ? "punches" : "enrollments";
            var endpoint = new Uri(origin, $"api/biometric/devices/{deviceId}/{resource}");
            for (var attempt = 1; attempt <= 3; attempt++)
            {
                var delay = TimeSpan.FromSeconds(Math.Pow(2, attempt));
                try
                {
                    using var body = new StringContent(payload, Encoding.UTF8, "application/json");
                    using var response = await client.PostAsync(endpoint, body, cancellationToken);
                    if (response.StatusCode is HttpStatusCode.OK or HttpStatusCode.Created)
                    {
                        using var result = JsonDocument.Parse(await response.Content.ReadAsStringAsync(cancellationToken));
                        var data = result.RootElement.GetProperty("data");
                        if (punch)
                            Console.WriteLine($"Accepted. Punch ID: {data.GetProperty("id")}; created: {data.GetProperty("created")}.");
                        else Console.WriteLine($"Accepted. Staging ID: {data.GetProperty("id")}; status: {data.GetProperty("status")}; created: {data.GetProperty("created")}.");
                        return 0;
                    }
                    if (punch && response.StatusCode == HttpStatusCode.Conflict)
                    {
                        Console.Error.WriteLine("Employee not registered yet. Keep this punch file and resend after HR promotion. No immediate retry.");
                        return 2;
                    }
                    var status = (int)response.StatusCode;
                    if (status != 429 && status < 500)
                    {
                        Console.Error.WriteLine($"Request rejected (HTTP {status}). Check token/device settings for 401/403, payload for 422. Redirects are not followed. No automatic retry.");
                        return 1;
                    }
                    if (status == 429)
                    {
                        var retry = response.Headers.RetryAfter;
                        delay = retry?.Delta ?? (retry?.Date is { } retryDate ? retryDate - DateTimeOffset.UtcNow : TimeSpan.FromSeconds(60));
                        // Avoid replaying earlier than a server-specified long wait.
                        if (delay > TimeSpan.FromSeconds(60))
                        {
                            Console.Error.WriteLine("Rate limited for more than 60 seconds. Keep the input file and retry later.");
                            return 2;
                        }
                        if (delay < TimeSpan.Zero) delay = TimeSpan.Zero;
                    }
                    Console.Error.WriteLine($"Temporary response HTTP {status}; attempt {attempt}/3.");
                }
                catch (HttpRequestException)
                {
                    Console.Error.WriteLine($"Network failure; attempt {attempt}/3.");
                }
                catch (TaskCanceledException) when (!cancellationToken.IsCancellationRequested)
                {
                    Console.Error.WriteLine($"Request timed out; attempt {attempt}/3.");
                }
                if (attempt < 3) await Task.Delay(delay, cancellationToken);
            }
            Console.Error.WriteLine("Retries exhausted. Input file retained: rerun --send with the same file after resolving connectivity.");
            return 2;
    }
}
