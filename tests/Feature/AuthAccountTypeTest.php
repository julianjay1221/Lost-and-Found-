<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAccountTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_page_does_not_offer_admin_account_creation(): void
    {
        $this
            ->get(route('register'))
            ->assertOk()
            ->assertSeeText('Create Student Account')
            ->assertDontSeeText('Verification Code')
            ->assertDontSeeText('Send Code')
            ->assertDontSee('value="admin"', false)
            ->assertDontSeeText('Admin');
    }

    public function test_registration_rejects_weak_passwords_like_12345678(): void
    {
        $this
            ->post(route('register'), [
                'name' => 'Weak Password User',
                'email' => 'weak-password@example.com',
                'contact_phone' => '09171234568',
                'password' => '12345678',
                'password_confirmation' => '12345678',
            ])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'weak-password@example.com']);
    }

    public function test_student_login_side_has_create_account_button(): void
    {
        $this
            ->get(route('login', ['side' => 'student']))
            ->assertOk()
            ->assertSeeText('Student Login')
            ->assertSeeText('Student Side')
            ->assertSeeText('Admin Side')
            ->assertSeeText('Create Account');
    }

    public function test_admin_login_side_has_no_create_account_button(): void
    {
        $this
            ->get(route('login', ['side' => 'admin']))
            ->assertOk()
            ->assertSeeText('Admin Login')
            ->assertSeeText('Admin Side')
            ->assertDontSeeText('Create Account');
    }

    public function test_public_registration_creates_student_even_if_admin_role_is_posted(): void
    {
        $this
            ->post(route('register'), [
                'name' => 'New Student',
                'email' => 'student@example.com',
                'contact_phone' => '09171234567',
                'role' => 'admin',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('student.dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'student@example.com',
            'contact_phone' => '09171234567',
            'role' => 'student',
        ]);

        $this->assertNotNull(User::where('email', 'student@example.com')->first()->email_verified_at);
    }

    public function test_registration_requires_contact_number(): void
    {
        $this
            ->post(route('register'), [
                'name' => 'New Student',
                'email' => 'missing-phone@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertSessionHasErrors('contact_phone');
    }

    public function test_existing_admin_can_still_log_in(): void
    {
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);

        $this
            ->post(route('login'), [
                'email' => 'admin@example.com',
                'password' => 'password123',
                'side' => 'admin',
            ])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_student_cannot_log_in_through_admin_side(): void
    {
        User::create([
            'name' => 'Student User',
            'email' => 'student-login@example.com',
            'password' => 'password123',
            'role' => 'student',
        ]);

        $this
            ->post(route('login'), [
                'email' => 'student-login@example.com',
                'password' => 'password123',
                'side' => 'admin',
            ])
            ->assertSessionHasErrors('side');
    }
}
