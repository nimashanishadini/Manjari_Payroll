<?php

namespace Tests\Feature;

use App\Models\{Branch, Department, Employee, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeSectionSaveTest extends TestCase
{
    use RefreshDatabase;

    public function test_additional_fields_store_validate_and_reload_employee_values(): void
    {
        $this->actingAs(User::factory()->create());
        $field = \App\Models\EmployeeAdditionalField::create(['name' => 'Uniform size', 'type' => 'select', 'visibility' => 'private', 'show_in_person_list' => false, 'options' => ['Small', 'Large']]);
        $number = \App\Models\EmployeeAdditionalField::create(['name' => 'Locker', 'type' => 'number', 'visibility' => 'public', 'show_in_person_list' => false]);
        $this->postJson(route('hr.person.sections.store'), ['section' => 'extra'])->assertUnprocessable();
        $id = $this->postJson(route('hr.person.sections.store'), $this->basicData())->assertOk()->json('id');
        $payload = ['section' => 'extra', 'record_id' => $id, 'additional_values' => [$field->id => 'Large', $number->id => '12']];
        $this->postJson(route('hr.person.sections.store'), $payload)->assertOk();
        $this->getJson(route('hr.person.lookup', ['employee_id' => 'EMP001']))->assertOk()->assertJsonPath('employee.additional_values.'.$field->id, 'Large');
        $this->withoutVite()->get(route('hr.person.create', ['employee' => $id]))->assertOk()->assertSee('Uniform size')->assertSee('additional_values['.$field->id.']', false);
        $payload['additional_values'] = [$field->id => 'Invalid', $number->id => 'abc'];
        $this->postJson(route('hr.person.sections.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors(['additional_values.'.$field->id, 'additional_values.'.$number->id]);
        $payload['additional_values'] = [99999 => 'Unknown field'];
        $this->postJson(route('hr.person.sections.store'), $payload)->assertUnprocessable();
        $payload['additional_values'] = [$field->id => ''];
        $this->postJson(route('hr.person.sections.store'), $payload)->assertOk();
        $this->assertDatabaseHas('employee_additional_values', ['employee_id' => $id, 'employee_additional_field_id' => $field->id, 'value' => null]);
        $this->withSession(['person_registration_ids' => []])->postJson(route('hr.person.sections.store'), $payload)->assertForbidden();
    }

    public function test_documents_upload_download_reload_and_remove(): void
    {
        Storage::fake('employee_documents');
        $this->actingAs(User::factory()->create());
        $id = $this->postJson(route('hr.person.sections.store'), $this->basicData())->assertOk()->json('id');
        $payload = ['section' => 'documents', 'record_id' => $id,
            'qualifications' => ['ol' => ['institution' => 'Test School', 'year' => 2015, 'details' => '9 A passes']],
            'uploads' => ['nic' => [UploadedFile::fake()->createWithContent('nic.pdf', "%PDF-1.4\nTest document")]]];
        $result = $this->post(route('hr.person.sections.store'), $payload, ['Accept' => 'application/json'])->assertOk();
        $file = Employee::findOrFail($id)->documents['nic']['files'][0];
        Storage::disk('employee_documents')->assertExists($file['path']);
        $url = $result->json('documents.nic.files.0.url');
        $this->get($url)->assertOk()->assertDownload('nic.pdf');
        $this->getJson(route('hr.person.lookup', ['employee_id' => 'EMP001']))->assertOk()
            ->assertJsonPath('employee.documents.ol.qualification.institution', 'Test School')
            ->assertJsonMissingPath('employee.documents.nic.files.0.path');
        $this->withoutVite()->get(route('hr.person.create', ['employee' => $id]))->assertOk()->assertSee('G.C.E. O/L');
        $this->postJson(route('hr.person.sections.store'), ['section' => 'documents', 'record_id' => $id, 'remove_documents' => [$file['id']]])->assertOk();
        Storage::disk('employee_documents')->assertMissing($file['path']);
        $this->get($url)->assertNotFound();
        $this->withSession(['person_registration_ids' => []])->get($url)->assertForbidden();
    }

    public function test_documents_validate_uploads_and_require_an_authorized_employee(): void
    {
        Storage::fake('employee_documents');
        $this->actingAs(User::factory()->create());
        $this->postJson(route('hr.person.sections.store'), ['section' => 'documents'])->assertUnprocessable();
        $id = $this->postJson(route('hr.person.sections.store'), $this->basicData())->assertOk()->json('id');
        $this->postJson(route('hr.person.sections.store'), ['section' => 'documents', 'record_id' => $id,
            'uploads' => ['nic' => [UploadedFile::fake()->create('bad.txt', 1, 'text/plain')]],
            'qualifications' => ['ol' => ['year' => 1800]],
        ])->assertUnprocessable()->assertJsonValidationErrors(['uploads.nic.0', 'qualifications.ol.year']);
        $this->postJson(route('hr.person.sections.store'), ['section' => 'documents', 'record_id' => $id,
            'uploads' => ['nic' => [UploadedFile::fake()->create('large.pdf', 5121, 'application/pdf')]],
        ])->assertUnprocessable()->assertJsonValidationErrors('uploads.nic.0');
        $this->withSession(['person_registration_ids' => []])->postJson(route('hr.person.sections.store'),
            ['section' => 'documents', 'record_id' => $id])->assertForbidden();
    }

    public function test_existing_bank_names_are_decrypted_by_migration(): void
    {
        $this->actingAs(User::factory()->create());
        $id = $this->postJson(route('hr.person.sections.store'), $this->basicData())->assertOk()->json('id');
        $values = ['bank_name' => 'Test Bank', 'bank_branch' => 'Colombo', 'bank_account_name' => 'Test Employee'];
        \Illuminate\Support\Facades\DB::table('employees')->where('id', $id)->update(array_map(
            fn ($value) => \Illuminate\Support\Facades\Crypt::encryptString($value), $values
        ) + ['bank_account_number' => \Illuminate\Support\Facades\Crypt::encryptString('001234567890')]);
        $migration = require database_path('migrations/2026_10_29_110000_decrypt_employee_bank_names.php');
        $migration->up();
        $employee = Employee::findOrFail($id);
        foreach ($values as $field => $value) $this->assertSame($value, $employee->getRawOriginal($field));
        $this->assertSame('001234567890', $employee->bank_account_number);
        $this->assertNotSame('001234567890', $employee->getRawOriginal('bank_account_number'));
        $migration->down();
        $employee = $employee->fresh();
        foreach ($values as $field => $value) {
            $this->assertSame($value, \Illuminate\Support\Facades\Crypt::decryptString($employee->getRawOriginal($field)));
        }
        $migration->up();
    }

    public function test_private_documents_are_copied_to_public_with_updated_references(): void
    {
        Storage::fake('local');
        Storage::fake('employee_documents');
        $this->actingAs(User::factory()->create());
        $id = $this->postJson(route('hr.person.sections.store'), $this->basicData())->assertOk()->json('id');
        $employee = Employee::findOrFail($id);
        $path = "employee-documents/$id/certificate.pdf";
        Storage::disk('local')->put($path, 'certificate content');
        $employee->documents = ['ol' => ['files' => [['id' => 'test-document', 'path' => $path, 'name' => 'certificate.pdf']]]];
        $employee->save();
        $migration = require database_path('migrations/2026_10_29_120000_copy_employee_documents_to_public.php');
        $migration->up();
        $migration->up();
        $file = $employee->fresh()->documents['ol']['files'][0];
        $this->assertSame('employee_documents', $file['disk']);
        $this->assertSame("$id/certificate.pdf", $file['path']);
        $this->assertSame('certificate content', Storage::disk('employee_documents')->get($file['path']));
        Storage::disk('local')->assertExists($path);
        $this->get(route('hr.person.documents.download', [$id, 'test-document']))->assertOk();
    }

    public function test_identical_upload_names_do_not_overwrite_employee_files(): void
    {
        Storage::fake('employee_photos');
        Storage::fake('employee_documents');
        $this->actingAs(User::factory()->create());
        $basic = $this->basicData();
        $photos = [];
        $documents = [];
        foreach (['EMP001', 'EMP002'] as $employeeId) {
            $photo = UploadedFile::fake()->createWithContent('photo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
            $id = $this->postJson(route('hr.person.sections.store'), array_merge($basic, ['employee_id' => $employeeId, 'profile_photo' => $photo]))->assertOk()->json('id');
            $photos[] = Employee::findOrFail($id)->profile_photo;
            for ($i = 0; $i < 2; $i++) {
                $this->post(route('hr.person.sections.store'), ['section' => 'documents', 'record_id' => $id,
                    'uploads' => ['nic' => [UploadedFile::fake()->createWithContent('copy.pdf', "%PDF-1.4\n$employeeId-$i")]],
                ], ['Accept' => 'application/json'])->assertOk();
            }
            foreach (Employee::findOrFail($id)->documents['nic']['files'] as $file) {
                $documents[] = $file['path'];
                $this->assertSame('copy.pdf', $file['name']);
                Storage::disk('employee_documents')->assertExists($file['path']);
            }
        }
        $this->assertCount(2, array_unique($photos));
        $this->assertCount(4, array_unique($documents));
        foreach ($photos as $path) Storage::disk('employee_photos')->assertExists($path);
    }

    public function test_probation_and_confirmation_dates_save_reload_and_preserve_probation_history(): void
    {
        $this->actingAs(User::factory()->create());
        $data = $this->basicData();
        $data['employment_type'] = 'Probation';
        $data['probation_end_date'] = '2027-01-28';
        $id = $this->postJson(route('hr.person.sections.store'), $data)->assertOk()->json('id');
        $this->getJson(route('hr.person.lookup', ['employee_id' => 'EMP001']))->assertOk()
            ->assertJsonPath('employee.probation_end_date', '2027-01-28')->assertJsonPath('employee.confirmed_date', null);
        $data['record_id'] = $id;
        $data['employment_type'] = 'Permanent';
        $data['probation_end_date'] = null;
        $this->postJson(route('hr.person.sections.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('confirmed_date');
        $data['confirmed_date'] = '2026-10-29';
        $this->postJson(route('hr.person.sections.store'), $data)->assertOk();
        $this->getJson(route('hr.person.lookup', ['employee_id' => 'EMP001']))->assertOk()
            ->assertJsonPath('employee.employment_type', 'Permanent')
            ->assertJsonPath('employee.probation_end_date', '2027-01-28')
            ->assertJsonPath('employee.confirmed_date', '2026-10-29');
        $this->withoutVite()->get(route('hr.person.create', ['employee' => $id]))->assertOk()
            ->assertSee('id="probation_end_date"', false)->assertSee('id="confirmed_date"', false);
    }

    public function test_employment_dates_reject_invalid_values_and_dates_before_joining(): void
    {
        $this->actingAs(User::factory()->create());
        $data = $this->basicData();
        $data['employment_type'] = 'Probation';
        $data['probation_end_date'] = 'not-a-date';
        $this->postJson(route('hr.person.sections.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('probation_end_date');
        $data['probation_end_date'] = '2026-10-27';
        $this->postJson(route('hr.person.sections.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('probation_end_date');
        $data['employment_type'] = 'Permanent';
        $data['confirmed_date'] = '2026-10-27';
        $this->postJson(route('hr.person.sections.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('confirmed_date');
        $data['confirmed_date'] = now('Asia/Colombo')->addDay()->toDateString();
        $this->postJson(route('hr.person.sections.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('confirmed_date');
        $this->assertDatabaseCount('employees', 0);
    }

    private function basicData(): array
    {
        $branch = Branch::create(['branch_code' => 'HQ', 'name' => 'Head Office']);
        $department = Department::create(['branch_id' => $branch->id, 'name' => 'HR']);
        return ['section' => 'basic', 'employee_id' => 'EMP001', 'name' => 'Test Employee',
            'branch_id' => $branch->id, 'department_id' => $department->id,
            'joined_date' => '2026-10-28', 'employment_type' => 'Permanent', 'allow_self_service' => 1];
    }

    public function test_sections_update_the_same_employee_and_photo_is_stored(): void
    {
        Storage::fake('employee_photos');
        $this->actingAs(User::factory()->create());
        $data = $this->basicData();
        $photo = UploadedFile::fake()->createWithContent('photo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
        $id = $this->postJson(route('hr.person.sections.store'), $data + ['profile_photo' => $photo])->assertOk()->json('id');
        $employee = Employee::findOrFail($id);
        Storage::disk('employee_photos')->assertExists($employee->profile_photo);
        $this->get(route('hr.person.registration.photo', $id))->assertOk();
        $this->postJson(route('hr.person.sections.store'), ['section' => 'private', 'record_id' => $id, 'contact_number' => '0771234567'])->assertOk();
        $this->postJson(route('hr.person.sections.store'), array_merge($data, ['record_id' => $id, 'name' => 'Updated Name']))->assertOk();
        $this->assertDatabaseCount('employees', 1);
        $this->assertDatabaseHas('employees', ['id' => $id, 'name' => 'Updated Name', 'contact_number' => '0771234567']);
        $this->withoutVite()->get(route('hr.person.create', ['employee' => $id]))->assertOk();
    }

    public function test_rejects_large_photos_wrong_departments_and_unsaved_private_sections(): void
    {
        $this->actingAs(User::factory()->create());
        $data = $this->basicData();
        $this->postJson(route('hr.person.sections.store'), $data + ['profile_photo' => UploadedFile::fake()->create('photo.jpg', 5121, 'image/jpeg')])->assertUnprocessable()->assertJsonValidationErrors('profile_photo');
        $other = Branch::create(['branch_code' => 'OTHER', 'name' => 'Other']);
        $this->postJson(route('hr.person.sections.store'), array_merge($data, ['branch_id' => $other->id]))->assertUnprocessable()->assertJsonValidationErrors('department_id');
        $this->postJson(route('hr.person.sections.store'), ['section' => 'private'])->assertUnprocessable();
        $this->postJson(route('hr.person.sections.store'), $data + ['record_id' => 999])->assertForbidden();
        $this->assertDatabaseCount('employees', 0);
    }

    public function test_lookup_loads_existing_details_and_allows_updating_the_same_record(): void
    {
        $data = $this->basicData();
        unset($data['section']);
        $employee = Employee::create($data + [
            'first_name' => 'Test', 'last_name' => 'Employee',
            'nic_number' => '123456789V', 'personal_email' => 'employee@example.com',
        ]);
        $this->actingAs(User::factory()->create());
        $this->getJson(route('hr.person.lookup', ['employee_id' => 'EMP001']))
            ->assertOk()
            ->assertJsonPath('employee.id', $employee->id)
            ->assertJsonPath('employee.name', 'Test Employee')
            ->assertJsonPath('employee.nic_number', '123456789V')
            ->assertJsonPath('employee.personal_email', 'employee@example.com')
            ->assertJsonPath('employee.branch_name', 'Head Office')
            ->assertJsonPath('employee.department_name', 'HR')
            ->assertJsonPath('employee.employment_history', [])
            ->assertJsonMissingPath('employee.bank_name')
            ->assertJsonMissingPath('employee.bank_branch')
            ->assertJsonMissingPath('employee.bank_account_name')
            ->assertJsonMissingPath('employee.bank_account_number')
            ->assertJsonPath('employee.department_id', $employee->department_id);
        $this->postJson(route('hr.person.sections.store'), [
            'section' => 'private', 'record_id' => $employee->id, 'contact_number' => '0779876543',
        ])->assertOk();
        $this->assertDatabaseCount('employees', 1);
        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'contact_number' => '0779876543']);
        $this->getJson(route('hr.person.lookup', ['employee_id' => 'UNKNOWN']))->assertNotFound();
        $this->getJson(route('hr.person.lookup'))->assertUnprocessable();
    }

    public function test_lookup_requires_authentication(): void
    {
        $this->getJson(route('hr.person.lookup', ['employee_id' => 'EMP001']))->assertUnauthorized();
    }

    public function test_registration_tabs_include_employment_and_documents_and_remove_unused_tabs(): void
    {
        $this->actingAs(User::factory()->create())->withoutVite()
            ->get(route('hr.person.create'))->assertOk()
            ->assertSee('Documents &amp; Qualifications', false)
            ->assertSee('Employment Details')
            ->assertSee('employmentDetailsSearch')
            ->assertSee('Residential Address')
            ->assertDontSee('Resident Information')
            ->assertDontSee('Skin-Surface Temperature');
    }

    public function test_directory_displays_photo_without_a_registration_session(): void
    {
        Storage::fake('local');
        Storage::fake('employee_photos');
        $data = $this->basicData();
        unset($data['section']);
        $employee = Employee::create($data + ['first_name' => 'Test', 'last_name' => 'Employee', 'profile_photo' => 'employee-photos/test.png']);
        Storage::disk('local')->put($employee->profile_photo, 'photo');
        $url = route('hr.person.employees.photo', $employee);
        $this->getJson($url)->assertUnauthorized();
        $this->actingAs(User::factory()->create());
        $this->get($url)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->withoutVite()->get(route('hr.person.index'))->assertOk()->assertSee($url, false);
        Storage::disk('local')->delete($employee->profile_photo);
        $this->get($url)->assertNotFound();
    }
    public function test_bank_details_require_hr_role_and_password_and_only_account_number_is_encrypted(): void
    {
        $data = $this->basicData();
        unset($data['section']);
        $employee = Employee::create($data + ['first_name' => 'Test', 'last_name' => 'Employee']);
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['person_registration_ids' => [$employee->id]]);
        $bank = ['section' => 'private', 'record_id' => $employee->id, 'update_bank' => 1,
            'bank_name' => 'Test Bank', 'bank_branch' => 'Colombo', 'bank_account_name' => 'Test Employee',
            'bank_account_number' => '001234567890', 'bank_password' => 'password', 'blood_group' => 'O+'];
        $url = route('hr.person.bank-details', $employee);
        $this->postJson($url, ['bank_password' => 'password'])->assertForbidden();
        $this->postJson(route('hr.person.sections.store'), $bank)->assertForbidden();
        $user->role = 'hr';
        $user->save();
        $this->postJson(route('hr.person.sections.store'), array_merge($bank, ['bank_password' => 'wrong']))
            ->assertUnprocessable()->assertJsonValidationErrors('bank_password');
        $this->postJson(route('hr.person.sections.store'), $bank)->assertOk();
        $employee->refresh();
        $this->assertSame('001234567890', $employee->bank_account_number);
        $this->assertNotSame('001234567890', $employee->getRawOriginal('bank_account_number'));
        $this->assertSame('Test Bank', $employee->getRawOriginal('bank_name'));
        $this->assertSame('Colombo', $employee->getRawOriginal('bank_branch'));
        $this->assertSame('Test Employee', $employee->getRawOriginal('bank_account_name'));
        $this->assertArrayNotHasKey('bank_account_number', $employee->toArray());
        $this->getJson(route('hr.person.lookup', ['employee_id' => 'EMP001']))->assertOk()
            ->assertJsonPath('employee.blood_group', 'O+')->assertJsonMissingPath('employee.bank_account_number');
        $this->postJson($url, ['bank_password' => 'wrong'])->assertUnprocessable();
        $this->postJson($url, ['bank_password' => 'password'])->assertOk()
            ->assertJsonPath('bank.bank_account_number', '001234567890')->assertHeader('Cache-Control', 'no-store, private');
        $this->postJson(route('hr.person.sections.store'), ['section' => 'private', 'record_id' => $employee->id, 'update_bank' => 0, 'blood_group' => 'AB-'])->assertOk();
        $this->assertSame('001234567890', $employee->fresh()->bank_account_number);
        $this->postJson(route('hr.person.sections.store'), array_merge($bank, ['blood_group' => 'INVALID']))
            ->assertUnprocessable()->assertJsonValidationErrors('blood_group');
    }

    public function test_roles_cannot_be_assigned_through_user_mass_assignment(): void
    {
        $user = User::factory()->create();
        $user->fill(['role' => 'admin'])->save();
        $this->assertFalse($user->fresh()->canManageEmployeeBankDetails());
    }

    public function test_bank_secrets_are_not_flashed_after_validation_failure(): void
    {
        $data = $this->basicData();
        unset($data['section']);
        $employee = Employee::create($data + ['first_name' => 'Test', 'last_name' => 'Employee']);
        $user = User::factory()->create();
        $user->role = 'admin';
        $user->save();
        $this->actingAs($user)->from('/hr/person/add-employee')->post(route('hr.person.bank-details', $employee), [
            'bank_password' => 'wrong', 'bank_account_number' => '00123456',
        ])->assertRedirect('/hr/person/add-employee')->assertSessionMissing('_old_input.bank_password')
            ->assertSessionMissing('_old_input.bank_account_number');
    }
}
