# Raw attendance ingestion (device/sample punches)

This stage stores punches only. It does not calculate In/Out, shifts, worked hours, lateness, breaks, overtime, daily summaries or payroll. Live Hikvision attendance fetching is deferred until a device is available.

## Storage and ER relationships

`raw_attendance` has the ER fields: id, nullable device_id, branch_code, employee_id, punch_time, nullable verify_type/punch_state/state_source/mask_status/source_request_id, is_anomalous (false), source and created_at. Device ingestion always sets source=Device and uses a non-null device ID. State fields remain null until calculation; no inferred state is fabricated at capture.

- `employee_id` is a **string FK to employees.employee_id**, not employees.id. Preserve leading zeros.
- `device_id` references biometric_devices.id.
- `branch_code` references branches.branch_code and is snapshotted from the registered punching device, not the employee's current branch. Cross-branch punches are allowed.
- punch_time is stored in UTC with up to six fractional digits. Equivalent timezone representations deduplicate to the same UTC instant.
- Unique (device_id, employee_id, punch_time) makes device retries idempotent. Duplicate submissions retain the original metadata, including branch snapshot.
- Employee/time and branch/time indexes support later attendance processing.
- FK restriction prevents hard deletion or changing a referenced natural employee/branch key. Soft-deleted/resigned employees can still receive historical punches, without being reactivated.
- Device is nullable for future Mobile/Correction sources; this endpoint does not implement them. source_request_id is reserved, without a FK until the request supertype exists. Device uniqueness does not implement future mobile/correction deduplication.

## API

`POST /api/biometric/devices/{deviceId}/punches`

Use the same per-device Bearer token issued by `php artisan biometric:bridge-token DEVICE_ID`. Device must be enabled; tokens must be valid/not expired. Browser login does not replace a token. The device and credentials are locked within the transaction. Rotation/revocation behavior is shared with the enrollment API. Send `Accept: application/json` and `Content-Type: application/json`; use HTTPS outside local loopback testing.

One punch per request:

```json
{
  "employee_id": "00025",
  "punch_time": "2026-11-02T08:30:00+05:30",
  "verify_type": "Fingerprint",
  "mask_status": null
}
```

Use a real existing employee Enrollment ID and a non-future punch timestamp with explicit offset or Z. Optional metadata is nullable string, maximum 255. Source/device/branch/state/anomaly/request linkage is server controlled and cannot be supplied. Verification type is descriptive metadata, not an instruction to infer attendance state.

Responses:

- **201** newly saved; data.created=true, ID, device/employee/branch, UTC punch_time and source.
- **200** duplicate; same ID and data.created=false.
- **409** employee missing; code=employee_not_registered, retryable=true, stored=false. Retain the punch and replay after HR promotion. Do not advance a device checkpoint past an unacknowledged punch.
- **401** missing/wrong/revoked/expired device token (or unknown device).
- **403** disabled device.
- **422** invalid/spoofed fields.
- **429** rate limited, 60/minute per caller IP/route bucket. Multiple callers on an IP must coordinate throughput.

No punches are buffered server-side for an unknown employee. Caller retention is required. The current manual client retains its input file and exits 2 on HTTP 409, without repeatedly retrying during the same run. Rerun after the employee exists. This is not an automated background outbox. Network/server/429 retry rules match the enrollment sender; repeated input is safe. Existing pending enrollment notifications are separate; a raw punch does not generate a pending employee notification.

## Local testing with the C# bridge

Run Laravel separately. In AttendanceBridge, with BRIDGE_API_URL/BRIDGE_DEVICE_ID/BRIDGE_TOKEN already configured:

```sh
dotnet run -- --create-punch-sample punch.local.json
```

Edit employee_id to an existing employee's string Enrollment ID. Adjust punch_time as needed (use testing records, never pretend a generated sample is a real attendance event).

```sh
dotnet run -- --dry-run-punch punch.local.json
dotnet run -- --send-punch punch.local.json
```

First send creates a row; re-send returns the same ID with created=false. No file is deleted. Unknown employee test: send a sample with an unregistered ID, see exit 2/HTTP409 and zero saved rows; finish HR registration, then resend the same file. Test input files under AttendanceBridge matching *.local.json are git-ignored.

No attendance screen is added in this stage. Inspect raw_attendance in your database tool. No sample rows are automatically seeded into the local database. A sample sender uses the same Device source and can affect future calculations; use designated test employees/devices.

Validation: Laravel feature tests cover UTC/fractional precision, same-instant duplicate detection, original metadata retention, natural FK relation/protection, employee-not-registered replay, invalid/spoofed fields, authentication/expiry/disabled devices and historical soft-deleted employees. C# mock tests cover sample punch creation, dry run, mapped punch endpoint and retaining an unregistered employee punch.
