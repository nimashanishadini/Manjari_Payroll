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

The authenticated C# bridge ingestion API, device connectivity and real device synchronization remain the next integration step. Current sample entries are explicitly marked **Manual**.

Pending enrollments also appear in the header notification bell for HR/Admin. The unread counter combines document alerts and pending enrollments, and refreshes every minute while the page is visible. Opening an enrollment notification marks it read for that user and opens its review form. A disabled device or employee-ID conflict opens the filtered review list instead. Promoted/ignored enrollments disappear from the active feed. Re-fetching the same enrollment does not create duplicate notifications or reset another user's read state.
