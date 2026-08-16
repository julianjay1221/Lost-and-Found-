@extends('layouts.app')

@section('title', 'Login')

@section('content')
    @php
        $selectedSide = old('side', $loginSide);
    @endphp

    <section class="auth-card" style="max-width: 620px; margin: 24px auto;">
        <p class="eyebrow">Account</p>
        <h1>{{ $selectedSide === 'admin' ? 'Admin Login' : 'Student Login' }}</h1>

        <div class="segmented" style="margin-bottom: 16px;">
            <a class="{{ $selectedSide === 'student' ? 'button' : 'ghost-button' }}" href="{{ route('login', ['side' => 'student']) }}">
                Student Side
            </a>
            <a class="{{ $selectedSide === 'admin' ? 'button' : 'ghost-button' }}" href="{{ route('login', ['side' => 'admin']) }}">
                Admin Side
            </a>
        </div>

        <form method="POST" action="{{ route('login') }}" class="form-grid">
            @csrf
            <input type="hidden" name="side" value="{{ $selectedSide }}">

            <div class="field span-2">
                <label for="user_id">{{ $selectedSide === 'admin' ? 'Admin ID' : 'School ID' }}</label>
                <input class="input" id="user_id" type="text" name="user_id" value="{{ old('user_id', old('email')) }}" required autofocus autocomplete="new-password" spellcheck="false" autocapitalize="none" data-school-id-input>
            </div>

            <div class="field span-2">
                <label for="password">Password</label>
                <div class="password-field">
                    <input class="input" id="password" type="password" name="password" required autocomplete="new-password" spellcheck="false">
                    <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false">👁️</button>
                </div>
            </div>

            <label class="field span-2" style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="remember" value="1">
                <span class="radio-label">Remember me</span>
            </label>

            <div class="span-2" style="display: flex; gap: 10px; align-items: center;">
                <button class="button" type="submit">Login</button>
                @if ($selectedSide === 'student')
                    <a class="ghost-button" href="{{ route('register') }}">Create Account</a>
                @endif
            </div>

            @if ($selectedSide === 'admin')
                <p class="field span-2 muted" style="margin: 0; font-size: 0.9rem;">
                    Use the predefined Admin ID and Password configured in the environment.
                </p>
            @endif
        </form>
    </section>
@endsection
