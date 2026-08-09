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
                <label for="email">Email</label>
                <input class="input" id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
            </div>

            <div class="field span-2">
                <label for="password">Password</label>
                <input class="input" id="password" type="password" name="password" required>
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
        </form>
    </section>
@endsection
