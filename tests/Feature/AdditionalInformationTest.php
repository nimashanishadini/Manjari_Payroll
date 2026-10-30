<?php

namespace Tests\Feature;

use App\Models\EmployeeAdditionalField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdditionalInformationTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge(['name' => 'Uniform size', 'type' => 'select', 'visibility' => 'private', 'show_in_person_list' => '1', 'options_text' => "Small\nMedium\nLarge"], $overrides);
    }

    public function test_guests_cannot_access_or_change_field_settings(): void
    {
        $this->get(route('hr.person.additional-information'))->assertRedirect('/login');
        $this->post(route('hr.person.additional-information.store'), $this->payload())->assertRedirect('/login');
        $field = EmployeeAdditionalField::create(['name' => 'Example', 'type' => 'text', 'visibility' => 'private', 'show_in_person_list' => false]);
        $this->put(route('hr.person.additional-information.update', $field), $this->payload())->assertRedirect('/login');
        $this->delete(route('hr.person.additional-information.destroy', $field))->assertRedirect('/login');
        $this->assertDatabaseCount('employee_additional_fields', 1);
    }

    public function test_fields_can_be_created_viewed_updated_and_deleted(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('hr.person.additional-information.store'), $this->payload())->assertSessionHasNoErrors()->assertRedirect(route('hr.person.additional-information'));
        $field = EmployeeAdditionalField::firstOrFail();
        $this->assertSame(['Small', 'Medium', 'Large'], $field->options);
        $this->assertTrue($field->show_in_person_list);
        $this->get(route('hr.person.additional-information'))->assertOk()->assertSee('Uniform size')->assertSee('Single selection');
        $this->put(route('hr.person.additional-information.update', $field), $this->payload(['name' => 'Locker number', 'type' => 'number', 'visibility' => 'public', 'show_in_person_list' => '0']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('employee_additional_fields', ['id' => $field->id, 'name' => 'Locker number', 'visibility' => 'public', 'show_in_person_list' => false, 'options' => null]);
        $this->delete(route('hr.person.additional-information.destroy', $field))->assertRedirect(route('hr.person.additional-information'));
        $this->assertDatabaseCount('employee_additional_fields', 0);
    }

    public function test_invalid_fields_and_duplicate_names_are_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('hr.person.additional-information.store'), $this->payload(['type' => 'invalid', 'visibility' => 'invalid', 'name' => '  ']))->assertSessionHasErrors(['name', 'type', 'visibility']);
        $this->post(route('hr.person.additional-information.store'), $this->payload(['options_text' => "Small\nSmall"]))->assertSessionHasErrors('options_text');
        $this->assertDatabaseCount('employee_additional_fields', 0);
        $this->post(route('hr.person.additional-information.store'), $this->payload())->assertSessionHasNoErrors();
        $this->post(route('hr.person.additional-information.store'), $this->payload())->assertSessionHasErrors('name');
        $this->assertDatabaseCount('employee_additional_fields', 1);
    }
}
