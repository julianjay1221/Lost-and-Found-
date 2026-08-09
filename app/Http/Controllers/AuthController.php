<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\EmailVerificationCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function sendRegistrationVerificationCode(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => is_string($request->input('email'))
                ? trim($request->input('email'))
                : $request->input('email'),
        ]);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $code = (string) random_int(100000, 999999);

        $request->session()->put('registration_verification', [
            'email' => $data['email'],
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(15)->toIso8601String(),
        ]);

        Notification::route('mail', $data['email'])
            ->notify(new EmailVerificationCode($code, $data['name'] ?? null));

        return back()
            ->withInput($request->except('password', 'password_confirmation', 'verification_code'))
            ->with('status', 'Verification code sent to '.$data['email'].'.');
    }

    public function register(Request $request): RedirectResponse
    {
        $request->merge([
            'contact_phone' => is_string($request->input('contact_phone'))
                ? trim($request->input('contact_phone'))
                : $request->input('contact_phone'),
            'verification_code' => preg_replace('/\D/', '', (string) $request->input('verification_code')),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'contact_phone' => ['required', 'string', 'max:50'],
            'verification_code' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if (! $this->registrationVerificationCodeMatches($request, $data['email'], $data['verification_code'])) {
            return back()
                ->withErrors(['verification_code' => 'The verification code is invalid or has expired.'])
                ->withInput($request->except('password', 'password_confirmation', 'verification_code'));
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'contact_phone' => $data['contact_phone'],
            'password' => Hash::make($data['password']),
            'role' => 'student',
        ]);

        $user->markEmailAsVerified();
        $request->session()->forget('registration_verification');

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route($this->dashboardRoute($user))
            ->with('status', 'Account created and email verified.');
    }

    public function showLogin(Request $request): View
    {
        $loginSide = $this->loginSide($request->query('side', 'student'));

        return view('auth.login', compact('loginSide'));
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'side' => ['nullable', Rule::in(['student', 'admin'])],
        ]);

        $side = $credentials['side'] ?? null;
        unset($credentials['side']);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'These credentials do not match our records.'])
                ->onlyInput('email', 'side');
        }

        $user = $request->user();

        if ($side && ! $this->roleMatchesSide($user, $side)) {
            Auth::logout();

            return back()
                ->withErrors(['side' => 'Please use the correct login side for this account.'])
                ->onlyInput('email', 'side');
        }

        $request->session()->regenerate();

        return redirect()->route($this->dashboardRoute($user));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function dashboardRoute(User $user): string
    {
        return $user->isAdmin() ? 'admin.dashboard' : 'student.dashboard';
    }

    private function loginSide(?string $side): string
    {
        return in_array($side, ['student', 'admin'], true) ? $side : 'student';
    }

    private function roleMatchesSide(User $user, string $side): bool
    {
        return $side === 'admin'
            ? $user->isAdmin()
            : $user->isStudent();
    }

    private function registrationVerificationCodeMatches(Request $request, string $email, string $code): bool
    {
        $verification = $request->session()->get('registration_verification');

        if (! is_array($verification)) {
            return false;
        }

        $expiresAt = isset($verification['expires_at'])
            ? rescue(fn () => Carbon::parse($verification['expires_at']), null, false)
            : null;

        return ($verification['email'] ?? null) === $email
            && filled($verification['code'] ?? null)
            && $expiresAt !== null
            && $expiresAt->isFuture()
            && Hash::check($code, $verification['code']);
    }
}
