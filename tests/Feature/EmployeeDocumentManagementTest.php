<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Department;
use App\Models\DocumentTypeMaster;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\SystemAlarm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeDocumentManagementTest extends TestCase
{
    use RefreshDatabase;

    private function employee(): Employee
    {
        Storage::fake('employee_documents');
        $user = User::factory()->create();
        $user->role = 'admin';
        $user->save();
        $this->actingAs($user);
        $branch = Branch::create(['branch_code' => 'HQ', 'name' => 'Head Office']);
        $department = Department::create(['branch_id' => $branch->id, 'name' => 'HR']);
        $employee = Employee::create(['employee_id' => 'EMP001', 'name' => 'Test Employee', 'first_name' => 'Test', 'last_name' => 'Employee',
            'branch_id' => $branch->id, 'department_id' => $department->id, 'employment_type' => 'Permanent']);
        $this->withSession(['person_registration_ids' => [$employee->id]]);

        return $employee;
    }

    private function upload(Employee $employee, string $expiry, string $code = 'nic')
    {
        return $this->post(route('hr.person.sections.store'), ['section' => 'documents', 'record_id' => $employee->id,
            'uploads' => [$code => [UploadedFile::fake()->createWithContent('copy.pdf', "%PDF-1.4\nEmployee document")]],
            'upload_expiry' => [$code => $expiry]], ['Accept' => 'application/json']);
    }

    public function test_mandatory_documents_block_completion_until_a_valid_copy_exists(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(12, 0));
        $employee = $this->employee();
        DocumentTypeMaster::where('code', 'nic')->update(['is_mandatory' => true, 'requires_expiry' => true]);
        $url = route('hr.person.onboarding.complete', $employee);
        $this->postJson($url)->assertUnprocessable()->assertJsonValidationErrors('documents');
        $this->post(route('hr.person.sections.store'), ['section' => 'documents', 'record_id' => $employee->id,
            'uploads' => ['nic' => [UploadedFile::fake()->createWithContent('nic.pdf', "%PDF-1.4\nTest")]]], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors('upload_expiry.nic');
        $this->upload($employee, '2026-10-28')->assertOk()->assertJsonPath('onboarding.can_complete', false);
        $this->postJson($url)->assertUnprocessable();
        $this->upload($employee, '2027-10-29')->assertOk()->assertJsonPath('onboarding.can_complete', true);
        $this->postJson($url)->assertOk()->assertJsonPath('onboarding.completed', true);
        $this->assertNotNull($employee->fresh()->onboarding_completed_at);
        $document = EmployeeDocument::whereDate('expires_on', '2027-10-29')->firstOrFail();
        $this->assertSame($employee->id, $document->employee->id);
        $this->assertSame('nic', $document->documentType->code);
        $this->postJson(route('hr.person.sections.store'), ['section' => 'documents', 'record_id' => $employee->id,
            'remove_documents' => [$document->file_id]])->assertOk()->assertJsonPath('onboarding.completed', false);
        $this->assertNull($employee->fresh()->onboarding_completed_at);
        $this->withSession(['person_registration_ids' => []])->postJson($url)->assertForbidden();
    }

    public function test_expiry_alarms_refresh_without_duplicates_and_resolve_on_renewal(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(12, 0));
        $employee = $this->employee();
        DocumentTypeMaster::where('code', 'nic')->update(['requires_expiry' => true]);
        $this->upload($employee, '2026-11-28')->assertOk();
        $document = EmployeeDocument::firstOrFail();
        $this->assertDatabaseHas('system_alarms', ['employee_document_id' => $document->id, 'status' => 'expiring', 'resolved_at' => null]);
        $this->artisan('documents:refresh-alarms')->assertSuccessful();
        $this->artisan('documents:refresh-alarms')->assertSuccessful();
        $this->assertDatabaseCount('system_alarms', 1);
        $this->travelTo(now()->setDate(2026, 11, 28)->setTime(12, 0));
        $this->artisan('documents:refresh-alarms')->assertSuccessful();
        $this->assertSame('expiring', SystemAlarm::first()->status);
        $this->travelTo(now()->setDate(2026, 11, 29)->setTime(12, 0));
        $this->artisan('documents:refresh-alarms')->assertSuccessful();
        $this->assertSame('expired', SystemAlarm::first()->status);
        $this->get(route('hr.person.system-alarms.index'))->assertRedirect(route('dashboard', ['notifications' => 1]));
        $this->getJson(route('hr.person.notifications.index'))->assertOk()->assertJsonPath('notifications.0.employee_id', 'EMP001')->assertJsonPath('notifications.0.status', 'expired');
        $this->postJson(route('hr.person.sections.store'), ['section' => 'documents', 'record_id' => $employee->id,
            'document_expiry' => [$document->file_id => '2027-12-31']])->assertOk();
        $this->assertNotNull(SystemAlarm::first()->resolved_at);
        $this->getJson(route('hr.person.lookup', ['employee_id' => 'EMP001']))->assertOk()
            ->assertJsonPath('employee.documents.nic.files.0.expires_on', '2027-12-31')->assertJsonMissingPath('employee.documents.nic.files.0.path');
        $this->postJson(route('hr.person.sections.store'), ['section' => 'documents', 'record_id' => $employee->id,
            'remove_documents' => [$document->file_id]])->assertOk();
        $this->assertDatabaseCount('employee_documents', 0);
        $this->assertDatabaseCount('system_alarms', 0);
    }

    public function test_document_types_are_managed_by_admin_or_hr_and_appear_in_registration(): void
    {
        $employee = $this->employee();
        $payload = ['code' => 'work_permit', 'name' => 'Work Permit', 'is_mandatory' => 1, 'requires_expiry' => 1, 'reminder_days' => 14];
        $this->post(route('hr.person.document-types.store'), $payload)->assertRedirect();
        $type = DocumentTypeMaster::where('code', 'work_permit')->firstOrFail();
        $this->withoutVite()->get(route('hr.person.document-types.index'))->assertOk()->assertSee('Work Permit');
        $this->get(route('hr.person.create', ['employee' => $employee->id]))->assertOk()->assertSee('uploads[work_permit][]', false)->assertSee('Mandatory');
        $this->upload($employee, '2027-01-01', 'work_permit')->assertOk();
        $this->put(route('hr.person.document-types.update', $type), array_diff_key($payload, ['code' => 1]) + ['unused' => 'ignored'])->assertRedirect();
        $this->putJson(route('hr.person.document-types.update', $type), $payload)->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->actingAs(User::factory()->create());
        $this->get(route('hr.person.document-types.index'))->assertForbidden();
        $this->postJson(route('hr.person.document-types.store'), $payload)->assertForbidden();
        $this->putJson(route('hr.person.document-types.update', $type), $payload)->assertForbidden();
        $this->get(route('hr.person.system-alarms.index'))->assertForbidden();
    }

    public function test_document_types_can_be_managed_inside_registration_and_return_to_the_same_employee(): void
    {
        $employee = $this->employee();
        $this->withoutVite()->get(route('hr.person.create', ['employee' => $employee->id, 'tab' => 'documents']))
            ->assertOk()->assertSee('Manage document types')->assertSee('id="documentTypeSettingsDialog"', false)
            ->assertDontSee('href="'.route('hr.person.document-types.index').'"', false);
        $payload = ['code' => 'training_record', 'name' => 'Training Record', 'is_mandatory' => 0, 'requires_expiry' => 0,
            'reminder_days' => 30, 'document_type_context' => 'registration', 'document_employee_id' => $employee->id];
        $destination = route('hr.person.create', ['tab' => 'documents', 'employee' => $employee->id]);
        $this->post(route('hr.person.document-types.store'), $payload)->assertRedirect($destination);
        $type = DocumentTypeMaster::where('code', 'training_record')->firstOrFail();
        unset($payload['code']);
        $payload['name'] = 'Training Certificate';
        $this->put(route('hr.person.document-types.update', $type), $payload)->assertRedirect($destination);
        $this->get($destination)->assertOk()->assertSee('Training Certificate');
        $this->withSession(['person_registration_ids' => []])->putJson(route('hr.person.document-types.update', $type), $payload)->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('hr.person.create'))->assertOk()
            ->assertDontSee('id="documentTypeSettingsDialog"', false)->assertDontSee('Manage document types</button>', false);
    }

    public function test_saved_expiry_cannot_be_cleared_when_required_or_updated_for_another_employee(): void
    {
        $employee = $this->employee();
        DocumentTypeMaster::where('code', 'nic')->update(['requires_expiry' => true]);
        $this->upload($employee, '2027-01-01')->assertOk();
        $document = EmployeeDocument::firstOrFail();
        $this->postJson(route('hr.person.sections.store'), ['section' => 'documents', 'record_id' => $employee->id,
            'document_expiry' => [$document->file_id => '']])->assertUnprocessable();
        $this->postJson(route('hr.person.sections.store'), ['section' => 'documents', 'record_id' => $employee->id,
            'document_expiry' => ['unknown-document' => '2027-01-01']])->assertUnprocessable();
        $this->postJson(route('hr.person.sections.store'), ['section' => 'documents', 'record_id' => $employee->id,
            'upload_expiry' => ['nic' => 'invalid-date']])->assertUnprocessable()->assertJsonValidationErrors('upload_expiry.nic');
        $this->assertSame('2027-01-01', $document->fresh()->expires_on->toDateString());
    }

    public function test_educational_certificates_do_not_expire_and_driving_licences_require_expiry(): void
    {
        $employee = $this->employee();
        DocumentTypeMaster::where('code', 'ol')->update(['is_mandatory' => true]);
        $this->withoutVite()->get(route('hr.person.create', ['employee' => $employee->id]))->assertOk()
            ->assertDontSee('name="upload_expiry[ol]"', false)
            ->assertDontSee('name="upload_expiry[al]"', false)
            ->assertDontSee('name="upload_expiry[higher_education]"', false)
            ->assertSee('name="upload_expiry[driving_license]"', false);
        $this->post(route('hr.person.sections.store'), ['section' => 'documents', 'record_id' => $employee->id,
            'uploads' => ['ol' => [UploadedFile::fake()->createWithContent('ol.pdf', "%PDF-1.4\nCertificate")]]], ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('onboarding.can_complete', true);
        $this->postJson(route('hr.person.onboarding.complete', $employee))->assertOk();
        $this->assertDatabaseCount('system_alarms', 0);
        $this->upload($employee, '2027-01-01', 'ol')->assertUnprocessable()->assertJsonValidationErrors('upload_expiry.ol');
        $this->post(route('hr.person.sections.store'), ['section' => 'documents', 'record_id' => $employee->id,
            'uploads' => ['driving_license' => [UploadedFile::fake()->createWithContent('licence.pdf', "%PDF-1.4\nLicence")]]], ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors('upload_expiry.driving_license');
    }

    public function test_existing_json_documents_are_backfilled_without_moving_files(): void
    {
        $employee = $this->employee();
        $migration = require database_path('migrations/2026_10_29_160000_create_document_management_tables.php');
        $migration->down();
        Storage::disk('employee_documents')->put('legacy/copy.pdf', 'existing copy');
        $employee->documents = ['nic' => ['files' => [['id' => 'legacy-document', 'disk' => 'employee_documents', 'path' => 'legacy/copy.pdf', 'name' => 'copy.pdf']]]];
        $employee->save();
        $migration->up();
        $this->assertDatabaseHas('employee_documents', ['employee_id' => $employee->id, 'file_id' => 'legacy-document', 'path' => 'legacy/copy.pdf']);
        Storage::disk('employee_documents')->assertExists('legacy/copy.pdf');
        $this->get(route('hr.person.documents.download', [$employee, 'legacy-document']))->assertOk();
        $this->assertSame('legacy-document', $employee->fresh()->documents['nic']['files'][0]['id']);
    }

    public function test_missing_files_and_expired_mandatory_documents_revoke_onboarding(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(12, 0));
        $employee = $this->employee();
        DocumentTypeMaster::where('code', 'nic')->update(['is_mandatory' => true, 'requires_expiry' => true]);
        $this->upload($employee, '2026-10-29')->assertOk();
        $url = route('hr.person.onboarding.complete', $employee);
        $this->postJson($url)->assertOk();
        $this->travelTo(now()->addDay());
        $this->artisan('documents:refresh-alarms')->assertSuccessful();
        $this->assertNull($employee->fresh()->onboarding_completed_at);
        $this->postJson($url)->assertUnprocessable();
        $this->upload($employee, '2027-01-01')->assertOk();
        $document = EmployeeDocument::whereDate('expires_on', '2027-01-01')->firstOrFail();
        Storage::disk($document->disk)->delete($document->path);
        $this->postJson($url)->assertUnprocessable();
    }
}
