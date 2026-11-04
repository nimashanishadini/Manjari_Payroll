# AttendanceBridge — standalone sample sender

This .NET 8 console application sends sample enrollment files or fetches Hikvision ISAPI users and sends enrollment metadata to Laravel for HR review. It also sends sample attendance punches; live device attendance extraction and biometric templates are not implemented. The Hikvision request shape follows the supplied AttendanceSync ZIP; compatibility must still be verified on the actual device/model/firmware.

## Requirements

- .NET 8 SDK or a compatible newer SDK with the .NET 8 target/runtime installed.
- Running Laravel API with migrations applied and an enabled registered device.
- A device-scoped token issued on the Laravel host: `php artisan biometric:bridge-token 1`.

Use **two terminals**. From the Laravel root, run `php artisan serve`. In the other terminal, enter this folder:

```sh
cd AttendanceBridge
```

Run the following commands from this folder. The bridge and Laravel are independent processes; neither starts the other. You can copy the entire `AttendanceBridge` folder outside the Laravel repository or onto a branch computer. The bridge needs no PHP, Composer, Laravel `.env`, database access or parent-directory files. Its only connection to Laravel is the API URL/device ID/token. It stays inside the current workspace for now, as a separately runnable project.

```sh
dotnet build
dotnet run -- --create-sample enrollment.local.json
```

Edit `enrollment.local.json`: choose a **new testing Enrollment ID**, retain its string type/leading zeros, and set the name. The generated fetch time is UTC. The command refuses to overwrite existing files; edit/reuse the file deliberately or choose a new filename. Names can be null. Avoid using a real employee's ID for a test.

```sh
dotnet run -- --dry-run enrollment.local.json
```

Dry run validates and displays metadata only; it does not need a token or send a request.

### Send from macOS/Linux (bash/zsh)

```sh
export BRIDGE_API_URL='http://127.0.0.1:8000'
export BRIDGE_DEVICE_ID='1'
# Read the issued token without echoing it or putting its value in shell history:
read -s BRIDGE_TOKEN
export BRIDGE_TOKEN
dotnet run -- --send enrollment.local.json
unset BRIDGE_TOKEN
```

### Send from Windows PowerShell

```powershell
$env:BRIDGE_API_URL = 'http://127.0.0.1:8000'
$env:BRIDGE_DEVICE_ID = '1'
$secret = Read-Host 'Bridge token' -AsSecureString
$env:BRIDGE_TOKEN = [System.Net.NetworkCredential]::new('', $secret).Password
try {
    dotnet run -- --send enrollment.local.json
} finally {
    Remove-Item Env:BRIDGE_TOKEN
    $secret.Dispose()
}
```

Use HTTPS for a non-loopback Laravel host. `127.0.0.1` refers to the computer running this client; when Laravel runs on another computer, use its HTTPS origin. The client requires an origin URL without a path/query, refuses redirects and retains normal certificate validation. Tokens must not go in source files, screenshots, logs or URLs. `*.local.json` input files and build output under `AttendanceBridge` are git-ignored.

## Expected result

1. First send: `created: True`, a staging ID and Pending HR Review status.
2. Re-send the same file: `created: False`, same staging record; no duplicate notification.
3. HR/Admin: inspect **To Be Reviewed** and the header bell.
4. Promote/Ignore in Laravel, then re-send the file: the reviewed status remains unchanged.

Only HTTP 200/201 is treated as success. The client retries network timeouts/failures, HTTP 429 and HTTP 5xx up to three total attempts, retaining exactly the same payload. It honors Retry-After up to 60 seconds; longer waits exit for later retry. HTTP 401/403/422 and redirects exit without retry. Generic console messages omit tokens, names and response bodies. Dry-run explicitly prints sample personal data, so use testing data there.

Input files are **never deleted**. After exhausted retries, retain and resend the same file; a lost success response is safe because Laravel deduplicates by device/enrollment ID. This is a manual single-file client, not a continuous service or automatic durable queue. It does not alter device Online/Offline or last-sync telemetry.

Exit codes: 0 success/dry run/sample creation, 1 configuration/input/rejection, 2 temporary failure after retries (or long Retry-After), 130 cancellation. Ctrl+C cancels waits and requests.

## Client checks

After building, `python3 tests/test_client.py` runs standard-library checks for sample creation/no overwrite, invalid input, successful delivery, transient retries with an unchanged payload, and non-retried unauthorized responses. It uses a loopback mock server and dummy credentials; it does not write to Laravel. Set `DOTNET` to the executable path if it is not on PATH.

If a temporary verification SDK exists at `/tmp/payroll-dotnet-sdk/dotnet`, you can use that path instead of `dotnet`. Temporary storage can be cleared; a permanent SDK installation is recommended for regular use.

## Source layout

- `Program.cs`: console entry point.
- `BridgeApplication.cs`: commands, validation and API delivery/retries.
- `Models/Enrollment.cs`: API metadata contract.
- `Sources/IEnrollmentSource.cs`: boundary for enrollment sources.
- `Sources/SampleFileEnrollmentSource.cs`: current JSON-file source.

The sample source reads one file. `Sources/HikvisionEnrollmentSource.cs` reads a complete paginated snapshot, and `--sync-hikvision` submits users sequentially through the shared `BridgeApiClient.cs`. This is a one-shot import, not a background polling service. No actual device has been used to verify compatibility yet.

When copying this folder, build on the destination machine. Do not assume macOS build output runs on Windows. Temporary SDK paths from previous verification may no longer exist; install a suitable SDK or supply your current executable path.

## Hikvision user import (one shot)

Configure Laravel variables (`BRIDGE_API_URL`, `BRIDGE_DEVICE_ID`, `BRIDGE_TOKEN`) as above. Use the Laravel record/token for the **same physical device** being queried; the server derives the staging branch from that record. No configuration-discovery endpoint or device passwords are added to Laravel.

On macOS/zsh:

```sh
export HIKVISION_URL='https://192.168.1.2:443'
export HIKVISION_USERNAME='admin'
read -s HIKVISION_PASSWORD
export HIKVISION_PASSWORD
dotnet run -- --sync-hikvision
unset HIKVISION_PASSWORD
```

On Windows PowerShell:

```powershell
$env:HIKVISION_URL = 'https://192.168.1.2:443'
$env:HIKVISION_USERNAME = 'admin'
$deviceSecret = Read-Host 'Device password' -AsSecureString
$env:HIKVISION_PASSWORD = [System.Net.NetworkCredential]::new('', $deviceSecret).Password
try {
    dotnet run -- --sync-hikvision
} finally {
    Remove-Item Env:HIKVISION_PASSWORD
    $deviceSecret.Dispose()
}
```

Replace the address/port with the device's HTTP/HTTPS ISAPI address, not its proprietary SDK port. HTTPS uses normal certificate validation; configure a trusted device certificate rather than disabling checks. Digest is supported over HTTP/HTTPS; Basic is allowed only over HTTPS. For a device that supports only HTTP on a trusted LAN, use an `http://` URL and explicitly set `HIKVISION_ALLOW_HTTP=true`. This option applies only to the device connection, not Laravel's HTTPS requirement. Never put passwords in source control.

The adapter POSTs to `ISAPI/AccessControl/UserInfo/Search?format=json`, with a stable search ID, page size 30 and advancing result position. It preserves string IDs including leading zeros. Numeric IDs are converted to decimal text; zeros already omitted by the device cannot be reconstructed. Missing/invalid IDs, repeated pages, changing reported totals, incomplete pagination and unexpected response shapes fail before any enrollment is sent. An empty device is a successful no-op.

After the complete snapshot is fetched and validated, each user is sent to Laravel (at least 1.1 seconds between users to respect the current 60/minute limit). Stop on delivery failure and rerun after fixing it: previously accepted rows remain; Laravel deduplication preserves reviewed status. Fetch timestamps use the bridge computer's UTC clock, so keep its clock accurate. Names are optional and are not changed on already staged rows. Console logs omit passwords, tokens and fetched names.

This command reads device users only. It does not register/delete users on the terminal, change employee permissions, mark device Online or update last-sync telemetry. Real-device access, model/firmware compatibility and actual ISAPI permissions remain to be tested. Attendance event filtering/punch ingestion is a later step.

## Sample attendance punches (without a physical device)

Keep Laravel running and configure BRIDGE_API_URL/BRIDGE_DEVICE_ID/BRIDGE_TOKEN as above. From this folder:

```sh
dotnet run -- --create-punch-sample punch.local.json
# Edit employee_id to an existing test employee's exact Enrollment ID.
dotnet run -- --dry-run-punch punch.local.json
dotnet run -- --send-punch punch.local.json
```

This posts one punch to `/api/biometric/devices/{deviceId}/punches`. First send creates a raw punch; re-send returns the same ID with created=false. No input is deleted. If the employee does not exist, the API returns 409 and the client exits 2: keep the file and rerun after HR promotion. It does not spin/retry immediately or claim the punch was saved. The existing bounded network/server-error retries still apply, with the identical payload.

Use test employees/devices: sample punches are stored as Device source and may be used by future calculations. In/Out inference and worked-hours calculation are not part of this step. No live Hikvision attendance extraction has been added. Full API schema/limitations: [attendance ingestion](../docs/attendance-ingestion.md) in the Laravel repository. This document link is informational; standalone execution does not require the parent folder.

### Import one selected user for testing

With the same device/API environment variables configured:

```sh
dotnet run -- --sync-hikvision --employee-id YOUR_ENROLLMENT_ID
```

Replace YOUR_ENROLLMENT_ID with the exact device enrollment ID (case-sensitive; leading zeros retained). The bridge currently reads/validates the device's complete paginated user snapshot, then sends only the matching user to Laravel. It does not import anyone else, and it does not change device users or attendance. A missing ID exits with no API import. Omit `--employee-id` only when you intend to import all device users. A successful request stages the selected enrollment; it does not automatically create an employee or login.
