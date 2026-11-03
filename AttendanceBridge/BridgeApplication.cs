using System.Globalization;
using System.Net;
using System.Net.Http.Headers;
using System.Text;
using System.Text.Json;
using System.Text.RegularExpressions;

internal static class BridgeApplication
{
    private static readonly JsonSerializerOptions Json = new() { WriteIndented = true };

    public static async Task<int> RunAsync(string[] args)
    {
        using var cancellation = new CancellationTokenSource();
        Console.CancelKeyPress += (_, e) => { e.Cancel = true; cancellation.Cancel(); };
        try
        {
            if (args.Length == 0 || args is ["--help"])
            {
                Console.WriteLine("Enrollment bridge (sample files or Hikvision ISAPI).\n" +
                    "  --create-punch-sample <file>  Create a device punch sample.\n" +
                    "  --send-punch <file>     Send a punch file.\n" +
                    "  --dry-run-punch <file>  Validate a punch without sending.\n" +
                    "  --sync-hikvision [--employee-id ID]  Import all users or one exact Enrollment ID.\n" +
                    "  --create-sample <file>  Create a sample with the current UTC fetch time.\n" +
                    "  --send <file>           Send an enrollment JSON file.\n" +
                    "  --dry-run <file>        Validate and print payload without sending.\n" +
                    "Environment for --send: BRIDGE_API_URL (server origin), BRIDGE_DEVICE_ID, BRIDGE_TOKEN.\n" +
                    "Exit codes: 0 success, 1 configuration/input/rejected, 2 retries exhausted, 130 cancelled.");
                return 0;
            }
            if (args.Length > 0 && args[0] == "--sync-hikvision")
            {
                string? selectedId = null;
                if (args is ["--sync-hikvision", "--employee-id", var id])
                {
                    if (id.Length > 255 || !Regex.IsMatch(id, "\\A[A-Za-z0-9_-]+\\z"))
                        throw new ArgumentException("Supply a valid exact Enrollment ID for --employee-id.");
                    selectedId = id;
                }
                else if (args.Length != 1)
                    throw new ArgumentException("Use --sync-hikvision [--employee-id ID]. No import was started.");
                var deviceSource = new HikvisionEnrollmentSource();
                var enrollments = await deviceSource.ReadAllAsync(cancellation.Token);
                if (selectedId is not null)
                {
                    enrollments = enrollments.Where(item => string.Equals(item.Id, selectedId, StringComparison.Ordinal)).ToList();
                    if (enrollments.Count != 1)
                        throw new ArgumentException("Selected Enrollment ID was not found. Nothing was imported.");
                }
                foreach (var item in enrollments) Validate(item);
                Console.WriteLine($"Fetched {enrollments.Count} device users. Starting HR staging import.");
                for (var index = 0; index < enrollments.Count; index++)
                {
                    // API is limited to 60 requests/minute per caller IP.
                    if (index > 0) await Task.Delay(TimeSpan.FromSeconds(1.1), cancellation.Token);
                    var result = await BridgeApiClient.SendAsync(JsonSerializer.Serialize(enrollments[index], Json), cancellation.Token);
                    if (result != 0)
                    {
                        Console.Error.WriteLine("Import stopped. Earlier accepted rows remain saved. Rerun safely after correcting the failure.");
                        return result;
                    }
                }
                Console.WriteLine("Hikvision enrollment import completed.");
                return 0;
            }
            if (args.Length != 2)
                throw new ArgumentException("Use --help for supported commands.");
            var path = args[1];
            if (args[0] == "--create-punch-sample")
            {
                await using var file = new FileStream(path, FileMode.CreateNew, FileAccess.Write);
                await JsonSerializer.SerializeAsync(file, new AttendancePunch("TEST_BRIDGE_001",
                    DateTimeOffset.UtcNow.AddMinutes(-1).ToString("yyyy-MM-dd'T'HH:mm:ss'Z'"), "Fingerprint", null), Json, cancellation.Token);
                Console.WriteLine("Punch sample created. Set employee_id to an existing employee's exact Enrollment ID before sending.");
                return 0;
            }
            if (args[0] is "--send-punch" or "--dry-run-punch")
            {
                var content = await File.ReadAllTextAsync(path, cancellation.Token);
                var item = JsonSerializer.Deserialize<AttendancePunch>(content) ?? throw new ArgumentException("Empty punch JSON.");
                Validate(new Enrollment(item.EmployeeId, null, item.PunchTime));
                if (item.VerifyType?.Length > 255 || item.MaskStatus?.Length > 255)
                    throw new ArgumentException("Punch verify_type/mask_status cannot exceed 255 characters.");
                var punchPayload = JsonSerializer.Serialize(item, Json);
                if (args[0] == "--dry-run-punch")
                {
                    Console.WriteLine(punchPayload);
                    Console.WriteLine("Dry run only: no request sent.");
                    return 0;
                }
                return await BridgeApiClient.SendAsync(punchPayload, cancellation.Token, punch: true);
            }
            if (args[0] == "--create-sample")
            {
                // Refuse to overwrite a previous sample or pending retry payload.
                await using var file = new FileStream(path, FileMode.CreateNew, FileAccess.Write);
                await JsonSerializer.SerializeAsync(file, new Enrollment("TEST_BRIDGE_001", "Kamal Test", DateTimeOffset.UtcNow.ToString("yyyy-MM-dd'T'HH:mm:ss'Z'")), Json, cancellation.Token);
                Console.WriteLine("Sample created. Edit its ID/name before sending; retain it for retries.");
                return 0;
            }
            if (args[0] is not ("--send" or "--dry-run"))
                throw new ArgumentException("Unknown command. Use --help.");
            IEnrollmentSource source = new SampleFileEnrollmentSource(path);
            var enrollment = await source.ReadAsync(cancellation.Token);
            Validate(enrollment);
            // Re-serialize only allowed metadata, never arbitrary input fields.
            var payload = JsonSerializer.Serialize(enrollment, Json);
            if (args[0] == "--dry-run")
            {
                Console.WriteLine(payload);
                Console.WriteLine("Dry run only: no request sent.");
                return 0;
            }

            return await BridgeApiClient.SendAsync(payload, cancellation.Token);
        }
        catch (OperationCanceledException) when (cancellation.IsCancellationRequested) { return 130; }
        catch (HttpRequestException)
        {
            Console.Error.WriteLine("Hikvision request failed. Check address, credentials and device availability. No device response body is logged.");
            return 2;
        }
        catch (TaskCanceledException)
        {
            Console.Error.WriteLine("Hikvision request timed out. Rerun the import after checking connectivity.");
            return 2;
        }
        catch (Exception error) when (error is ArgumentException or IOException or UnauthorizedAccessException or JsonException or InvalidOperationException or KeyNotFoundException or FormatException)
        {
            // Avoid printing arbitrary response bodies, credentials or personal data.
            Console.Error.WriteLine(error is ArgumentException ? error.Message : "Cannot read/write the sample or parse the API response. Check the file and API configuration.");
            return 1;
        }
    }

    internal static void Validate(Enrollment item)
    {
        if (item.Id is null || item.Id.Length > 255 || !Regex.IsMatch(item.Id, "\\A[A-Za-z0-9_-]+\\z"))
            throw new ArgumentException("raw_enrollment_id must be a string of 1-255 letters, digits, underscores or hyphens.");
        if (item.Name?.Length > 255) throw new ArgumentException("raw_name cannot exceed 255 characters.");
        if (item.FetchedAt is null || !Regex.IsMatch(item.FetchedAt, @"\A\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})\z")
            || !DateTimeOffset.TryParse(item.FetchedAt, CultureInfo.InvariantCulture, DateTimeStyles.None, out var fetched)
            || fetched > DateTimeOffset.UtcNow)
            throw new ArgumentException("fetched_at must be a non-future ISO 8601 timestamp with Z or an explicit timezone offset.");
    }
}

