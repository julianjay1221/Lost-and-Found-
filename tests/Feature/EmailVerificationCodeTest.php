<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailVerificationCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_create_account_without_verification_code(): void
    {
        $this
            ->post(route('register'), [
                'name' => 'Verification Student',
                'email' => 'verify-pass@example.com',
                'contact_phone' => '09171230001',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('student.dashboard'));

        $user = User::where('email', 'verify-pass@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->email_verification_code);
        $this->assertNull($user->email_verification_code_expires_at);
    }
}
