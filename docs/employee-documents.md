# Employee documents

Admin and HR users manage document types under **Add Employee → Documents & Qualifications → Manage document types**. The existing nine categories are seeded as optional so that a company can choose its mandatory requirements. Codes remain stable after creation; display names, mandatory flags, expiry requirements and reminder days can be edited.

In **Add Employee → Documents & Qualifications**, load an employee, choose files and enter an expiry date for the new copies. Date inputs appear only for types configured to expire (Driving License by default). Educational certificates do not need an expiry date. All copies in a single upload batch receive that date; each saved copy's date can be edited individually. Save the section before completing onboarding.

**Complete onboarding** checks the saved copies on the server. Every mandatory type needs at least one existing file. Types configured to expire also need a valid expiry date; non-expiring certificates remain valid without a date. A copy remains valid through its expiry day in `DOCUMENT_TIMEZONE` (default `Asia/Colombo`). Registration sections can be saved while documents are missing. Completion is tracked separately in `employees.onboarding_completed_at`; the existing employment `status` field is unchanged.

**Header notification bell** shows expiring and expired copies. Renewal updates the same alarm, and dates outside the reminder window resolve it. Removing a copy removes its alarm. A mandatory copy expiring or being removed invalidates onboarding completion when eligibility is next checked/refreshed. Users can then upload a valid replacement and complete onboarding again.

Tables:

- `document_type_masters`: document type configuration.
- `employee_documents`: employee/type foreign keys, copy storage references and expiry dates.
- `system_alarms`: one expiry alarm per employee copy.

The migration backfills existing document references without moving files. The existing `employees.documents` JSON remains a compatibility mirror for the current upload/download and qualification UI; document saves update the normalized tables in the same transaction. Use this flow when changing copies, rather than editing either representation directly.

Run `php artisan documents:refresh-alarms` for an immediate expiry refresh. The command is scheduled hourly. Automatic background checks require the Laravel scheduler to be running, for example a server cron running `php artisan schedule:run` every minute, or `php artisan schedule:work` during local development. Notifications refresh every minute while the page is visible and when the dropdown opens. Clicking an alert marks it read for that user and opens the employee’s Documents tab with the relevant document selected. Expiring alerts that become expired are unread again. No email/SMS delivery is configured by this feature.

Validation: `php artisan test --filter='EmployeeDocumentManagementTest|EmployeeSectionSaveTest'`.
