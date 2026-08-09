<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\EmailVerificationCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_sends_email_verification_code(): void
    {
        Notification::fake();

        $this
            ->post(route('register.verification-code'), [
                'name' => 'Verification Student',
                'email' => 'verify@example.com',
            ])
            ->assertRedirect()
            ->assertSessionHas('registration_verification');

        $verification = $this->app['session.store']->get('registration_verification');

        $this->assertSame('verify@example.com', $verification['email']);
        $this->assertTrue(now()->parse($verification['expires_at'])->isFuture());
        $this->assertDatabaseMissing('users', ['email' => 'verify@example.com']);

        Notification::assertSentOnDemand(EmailVerificationCode::class, function (EmailVerificationCode $notification, array $channels, object $notifiable) use ($verification): bool {
            return strlen($notification->code) === 6
                && ctype_digit($notification->code)
                && in_array('mail', $channels, true)
                && ($notifiable->routes['mail'] ?? null) === 'verify@example.com'
                && Hash::check($notification->code, $verification['code']);
        });
    }

    public function test_student_can_create_verified_account_with_code(): void
    {
        Notification::fake();

        $this->post(route('register.verification-code'), [
            'name' => 'Verification Student',
            'email' => 'verify-pass@example.com',
        ]);

        $code = null;

        Notification::assertSentOnDemand(EmailVerificationCode::class, function (EmailVerificationCode $notification) use (&$code): bool {
            $code = $notification->code;

            return true;
        });

        $this
            ->post(route('register'), [
                'name' => 'Verification Student',
                'email' => 'verify-pass@example.com',
                'contact_phone' => '09171230001',
                'verification_code' => $code,
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

    public function test_account_is_not_created_without_matching_registration_code(): void
    {
        $this
            ->withSession([
                'registration_verification' => [
                    'email' => 'verify-fail@example.com',
                    'code' => Hash::make('123456'),
                    'expires_at' => now()->addMinutes(15)->toIso8601String(),
                ],
            ])
            ->post(route('register'), [
                'name' => 'Verification Student',
                'email' => 'verify-fail@example.com',
                'contact_phone' => '09171230002',
                'verification_code' => '654321',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertSessionHasErrors('verification_code');

        $this->assertDatabaseMissing('users', ['email' => 'verify-fail@example.com']);
    }

    public function test_expired_registration_verification_code_is_rejected(): void
    {
        $this
            ->withSession([
                'registration_verification' => [
                    'email' => 'expired-code@example.com',
                    'code' => Hash::make('123456'),
                    'expires_at' => now()->subMinute()->toIso8601String(),
                ],
            ])
            ->post(route('register'), [
                'name' => 'Expired Code Student',
                'email' => 'expired-code@example.com',
                'contact_phone' => '09171230003',
                'verification_code' => '123456',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertSessionHasErrors('verification_code');

        $this->assertDatabaseMissing('users', ['email' => 'expired-code@example.com']);
    }
}
