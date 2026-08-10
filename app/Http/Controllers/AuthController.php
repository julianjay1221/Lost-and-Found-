<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => is_string($request->input('email'))
                ? trim($request->input('email'))
                : $request->input('email'),
            'user_id' => is_string($request->input('user_id'))
                ? trim($request->input('user_id'))
                : $request->input('user_id'),
        ]);

        $data = $request->validate([
            'user_id' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'string', 'min:8', 'regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/'],
        ]);

        $user = User::create([
            'name' => $data['user_id'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'student',
        ]);

        $user->markEmailAsVerified();

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
        $request->merge([
            'user_id' => is_string($request->input('user_id'))
                ? trim($request->input('user_id'))
                : $request->input('user_id'),
        ]);

        $credentials = $request->validate([
            'user_id' => ['required', 'string'],
            'password' => ['required', 'string'],
            'side' => ['nullable', Rule::in(['student', 'admin'])],
        ]);

        $side = $credentials['side'] ?? null;
        unset($credentials['side']);

        $userId = $credentials['user_id'];
        $password = $credentials['password'];
        unset($credentials['user_id']);

        if ($side === 'admin') {
            if ($this->isValidAdminCredential($userId, $password)) {
                $adminUser = $this->ensureAdminSessionUser();
                Auth::login($adminUser, $request->boolean('remember'));
                $request->session()->regenerate();

                return redirect()->route('admin.dashboard');
            }

            return back()
                ->withErrors(['user_id' => 'Invalid Admin ID or Password.'])
                ->onlyInput('user_id', 'side');
        }

        $resolvedEmail = null;
        if (filter_var($userId, FILTER_VALIDATE_EMAIL)) {
            $resolvedEmail = $userId;
        } else {
            $user = User::where('name', $userId)->first();
            $resolvedEmail = $user?->email;
        }

        if ($resolvedEmail === null || ! Auth::attempt(['email' => $resolvedEmail, 'password' => $password], $request->boolean('remember'))) {
            return back()
                ->withErrors(['user_id' => 'These credentials do not match our records.'])
                ->onlyInput('user_id', 'side');
        }

        $user = $request->user();

        if ($side && ! $this->roleMatchesSide($user, $side)) {
            Auth::logout();

            return back()
                ->withErrors(['side' => 'Please use the correct login side for this account.'])
                ->onlyInput('user_id', 'side');
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

    private function isValidAdminCredential(string $userId, string $password): bool
    {
        $configuredUserId = env('ADMIN_LOGIN_ID', '24-00000');
        $configuredPassword = env('ADMIN_LOGIN_PASSWORD', 'Admin_24');

        return $userId === $configuredUserId && $password === $configuredPassword;
    }

    private function ensureAdminSessionUser(): User
    {
        $admin = User::where('email', 'admin@local')->first();

        if ($admin instanceof User) {
            return $admin;
        }

        $admin = User::create([
            'name' => env('ADMIN_LOGIN_ID', '24-00000'),
            'email' => 'admin@local',
            'password' => Hash::make(Str::random(32)),
            'role' => 'admin',
        ]);

        return $admin;
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

}
