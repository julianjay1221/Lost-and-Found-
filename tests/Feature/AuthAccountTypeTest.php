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
                'user_id' => 'S12345',
                'email' => 'weak-password@example.com',
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
                'user_id' => 'S12347',
                'email' => 'student@example.com',
                'role' => 'admin',
                'password' => 'StrongP@ss123',
                'password_confirmation' => 'StrongP@ss123',
            ])
            ->assertRedirect(route('student.dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'student@example.com',
            'role' => 'student',
        ]);

        $this->assertNotNull(User::where('email', 'student@example.com')->first()->email_verified_at);
    }

    public function test_registration_rejects_malformed_email_addresses(): void
    {
        $this
            ->post(route('register'), [
                'user_id' => 'S12346',
                'email' => 'not-an-email',
                'password' => 'StrongP@ss123',
                'password_confirmation' => 'StrongP@ss123',
            ])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'not-an-email']);
    }

    public function test_registration_does_not_require_contact_number(): void
    {
        $this
            ->post(route('register'), [
                'user_id' => 'S12348',
                'email' => 'missing-phone@example.com',
                'password' => 'StrongP@ss123',
                'password_confirmation' => 'StrongP@ss123',
            ])
            ->assertRedirect(route('student.dashboard'));

        $this->assertDatabaseHas('users', ['email' => 'missing-phone@example.com']);
    }

    public function test_predefined_admin_credentials_can_log_in(): void
    {
        $this
            ->post(route('login'), [
                'user_id' => '24-00000',
                'password' => 'Admin_24',
                'side' => 'admin',
            ])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertTrue(auth()->check());
        $this->assertTrue(auth()->user()->isAdmin());
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
                'user_id' => 'student-login@example.com',
                'password' => 'password123',
                'side' => 'admin',
            ])
            ->assertSessionHasErrors('user_id');
    }

    public function test_guest_is_redirected_to_login_when_visiting_admin_dashboard(): void
    {
        $this
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_admin_dashboard(): void
    {
        $student = User::create([
            'name' => 'Regular Student',
            'email' => 'regular-student@example.com',
            'password' => 'password123',
            'role' => 'student',
        ]);

        $this
            ->actingAs($student)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }
}
