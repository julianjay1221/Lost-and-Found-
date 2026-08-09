<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
            'contact_phone' => is_string($request->input('contact_phone'))
                ? trim($request->input('contact_phone'))
                : $request->input('contact_phone'),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'contact_phone' => ['required', 'string', 'max:50'],
            'password' => ['required', 'confirmed', 'string', 'min:8', 'regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'contact_phone' => $data['contact_phone'],
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

}
