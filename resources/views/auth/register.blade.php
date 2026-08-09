@extends('layouts.app')

@section('title', 'Sign Up')

@section('content')
    <section class="auth-card" style="max-width: 620px; margin: 24px auto;">
        <p class="eyebrow">Account</p>
        <h1>Create Student Account</h1>

        <form method="POST" action="{{ route('register') }}" class="form-grid">
            @csrf

            <div class="field span-2">
                <label for="name">Name</label>
                <input class="input" id="name" type="text" name="name" value="{{ old('name') }}" required autofocus>
            </div>

            <div class="field span-2">
                <label for="email">Email</label>
                <input class="input" id="email" type="email" name="email" value="{{ old('email') }}" required>
            </div>

            <div class="field span-2">
                <label for="contact_phone">Contact Number</label>
                <input class="input" id="contact_phone" name="contact_phone" value="{{ old('contact_phone') }}" inputmode="tel" autocomplete="tel" required>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input class="input" id="password" type="password" name="password" required>
            </div>

            <div class="field">
                <label for="password_confirmation">Confirm Password</label>
                <input class="input" id="password_confirmation" type="password" name="password_confirmation" required>
            </div>

            <div class="span-2" style="display: flex; gap: 10px; align-items: center;">
                <button class="button" type="submit">Create Student Account</button>
                <a class="ghost-button" href="{{ route('login', ['side' => 'student']) }}">Login</a>
            </div>
        </form>
    </section>
@endsection
