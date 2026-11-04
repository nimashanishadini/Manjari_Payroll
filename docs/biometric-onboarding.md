# Biometric onboarding: local review flow

1. Register a terminal under **Device → Biometric Devices**.
2. Open **Person → Employee Management → To Be Reviewed**.
3. Until the C# bridge is integrated, use **Add sample enrollment (testing)** to enter a device, the exact enrollment ID and its optional keypad name. This creates only a staging row, not a physical enrollment or employee. It does not change device sync telemetry.
4. Choose **Review**. Add Person opens with the enrollment ID, source branch and keypad name prefilled. The ID and branch are retained. Select the department, designation, manager, employment date/type and any initial contact details.
5. Optionally choose **Create a self-service login account**, enter a unique login email and an initial password with confirmation. The new account always receives the Employee role; it does not reuse or change an existing privileged account. Login passwords are hashed and excluded from validation flash data.
6. Choose **Promote to Employee**. The employee, initial employment history, optional login and staging status/link are saved in one transaction. The profile then opens on Private Information to finish details and upload required documents. Promotion does not bypass the existing mandatory-document onboarding completion check.
7. For unwanted records choose **Ignore**, with a reason. The record is retained under the Ignored filter.

Only HR/Admin can list, stage, review, promote or ignore enrollments. Repeated entries are idempotent per device/enrollment ID and do not reset reviewed records. Employee IDs remain globally unique, including soft-deleted employees. If the same ID is staged from multiple devices, only one can create an employee; the other remains pending with a conflict notice for HR to resolve. No reviewed records are automatically relinked.

`biometric_staging_users` uses the ER names `device_id`, `raw_enrollment_id`, nullable `raw_name` and `fetched_at`. The fetch time records when the enrollment was fetched and advances only for a newer fetch; it is independent of row creation time. For records created before this field existed, the migration backfills `fetched_at` from `created_at` as the best available fallback. HR must supply a full employee name before promotion even if the device supplied none.

The table also retains the enrollment's source branch snapshot, its device, review status, reviewer/time and promoted employee link. No pending rows are automatically seeded into the live database.

The authenticated enrollment ingestion API is available as described below. The standalone C# client includes a Hikvision ISAPI user-search adapter based on the supplied ZIP; real-device compatibility remains to be verified. Sample entries are marked **Manual**; API entries are marked **Bridge**.

Pending enrollments also appear in the header notification bell for HR/Admin. The unread counter combines document alerts and pending enrollments, and refreshes every minute while the page is visible. Opening an enrollment notification marks it read for that user and opens its review form. A disabled device or employee-ID conflict opens the filtered review list instead. Promoted/ignored enrollments disappear from the active feed. Re-fetching the same enrollment does not create duplicate notifications or reset another user's read state.

## Enrollment bridge API

Run migrations before use: `php artisan migrate`.

On the trusted application host, issue a token for a registered, enabled device (replace `1` with its database ID):

```sh
php artisan biometric:bridge-token 1 --days=90
```

This prints a secret once, stores only its SHA-256 hash and expiry, and immediately invalidates any previous token for that device. Keep it in the bridge's secret configuration, never source control or URLs. Each device needs its own token. Rotate with the same command; revoke with `php artisan biometric:bridge-token 1 --revoke`. Default lifetime is 90 days; allowed range is 1–365. Disabled devices cannot ingest even with a valid token. Existing HR/browser login sessions do not authenticate this endpoint.

### Postman request

- Method: **POST**
- Local URL: `http://127.0.0.1:8000/api/biometric/devices/1/enrollments`
- Authorization: **Bearer Token**, paste the generated token.
- Headers: `Accept: application/json`, `Content-Type: application/json`.
- Body: raw JSON:

```json
{
  "raw_enrollment_id": "00025",
  "raw_name": "Kamal",
  "fetched_at": "2026-10-31T10:00:00+05:30"
}
```

Use the actual fetch time with an explicit timezone (`Z` or `±HH:MM`); future fetch times are rejected. Name may be null or omitted. Enrollment ID must remain a string, including leading zeros; allowed characters are letters, digits, underscore and hyphen (maximum 255). One enrollment per request. Branch is derived from the registered device, never trusted from the bridge. Review status and promoted employee links cannot be supplied.

Responses:

- **201**: new staging row, `data.created: true`, Pending HR Review.
- **200**: existing device/enrollment pair, `data.created: false`; review state/name/branch snapshot retained. Only newer fetch time advances. Original source is retained on existing manual rows.
- **401**: unknown device, missing/wrong/revoked/expired token.
- **403**: disabled device.
- **422**: invalid input.
- **429**: rate limit exceeded (60 requests/minute per caller IP). Queue and retry after the indicated delay; this includes bridges sharing an IP.

The response returns staging ID, device ID, raw enrollment ID, current status, creation flag and UTC fetched time. It does not expose login passwords, tokens or employee details. Use HTTPS outside local development. Do not retry 401/403/422 unchanged; correct credentials/configuration/data first. Retry network failures and server errors with backoff, preserving the enrollment ID and fetch timestamp. Duplicate delivery is safe.

Verify the staged enrollment in **To Be Reviewed** and the HR/Admin notification bell, then exercise Promote/Ignore. Ingestion does not create employees/users, bypass onboarding, reset notification read state, or claim device Online/last-sync telemetry. An already reviewed row may be returned; it is never reset to Pending.

The API imports enrollment metadata only. Attendance punches, fingerprint/face templates, SDK connectivity and the C# application are outside this endpoint.

## C# sample client

A standalone client is available at `AttendanceBridge`. See [bridge setup and testing](../AttendanceBridge/README.md) for .NET prerequisites, generating a sample file, dry-run validation and sending with a device-scoped token on macOS/Linux or Windows. It retries transient errors with the same payload and leaves input files intact. It also supports a one-shot `--sync-hikvision` enrollment import using device credentials supplied locally to the bridge; see the README. This does not import attendance punches, and actual device compatibility still needs verification.

Sample raw punch ingestion is now available separately; see [attendance ingestion](attendance-ingestion.md). Live device tests remain deferred.
