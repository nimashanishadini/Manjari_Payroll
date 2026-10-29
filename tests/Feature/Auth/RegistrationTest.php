<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertOk()
            ->assertSee('Create your account')
            ->assertSee('Register Account');
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertNoContent();

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('Test User', $user->name);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_new_users_can_register_via_browser_and_redirect(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'Browser User',
            'email' => 'browser@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            '_redirect_after_register' => '1',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/dashboard');

        $user = User::where('email', 'browser@example.com')->first();
        $this->assertNotNull($user);
    }

    public function test_duplicate_email_addresses_are_rejected(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->post('/register', [
            'name' => 'Another User',
            'email' => 'existing@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_password_confirmation_and_validation_work(): void
    {
        $response = $this->post('/register', [
            'name' => 'Invalid User',
            'email' => 'invalid@example.com',
            'password' => 'password',
            'password_confirmation' => 'mismatch-password',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }
}
