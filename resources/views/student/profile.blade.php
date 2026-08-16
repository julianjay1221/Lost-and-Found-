@extends('layouts.app')

@section('title', 'Profile')

@section('content')
    <section class="page-head">
        <div>
            <p class="eyebrow">Account</p>
            <h1>Profile</h1>
            <p class="muted">Update your contact details, password, and profile photo.</p>
        </div>
        <a class="ghost-button" href="{{ route('student.dashboard') }}">Back to Dashboard</a>
    </section>

    <section class="panel report-form-panel">
        <form method="POST" action="{{ route('student.profile.update') }}" enctype="multipart/form-data" class="form-grid">
            @csrf
            @method('PATCH')

            <div class="field span-2" style="display:flex;align-items:center;gap:16px;">
                <span class="avatar" style="width:76px;height:76px;font-size:34px;border:1px solid var(--line);">
                    @if (auth()->user()->profile_photo_path)
                        <img src="{{ asset(auth()->user()->profile_photo_path) }}" alt="Profile photo">
                    @else
                        ♙
                    @endif
                </span>
                <div style="display:grid;gap:7px;flex:1;">
                    <label for="profile_photo">Profile Photo</label>
                    <input class="input" id="profile_photo" type="file" name="profile_photo" accept="image/*">
                </div>
            </div>

            <div class="field">
                <label for="email">Email Address</label>
                <input class="input" id="email" type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required autocomplete="email">
            </div>

            <div class="field">
                <label for="contact_phone">Contact Number</label>
                <input class="input" id="contact_phone" name="contact_phone" value="{{ old('contact_phone', auth()->user()->contact_phone) }}" inputmode="tel" autocomplete="tel">
            </div>

            <div class="field">
                <label for="password">New Password</label>
                <div class="password-field">
                    <input class="input" id="password" type="password" name="password" autocomplete="new-password" spellcheck="false" data-password-strength-input>
                    <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false">👁️</button>
                </div>
                <div class="password-strength" data-password-strength hidden>
                    <div class="password-strength-head">
                        <span>Password strength</span>
                        <strong data-password-strength-label></strong>
                    </div>
                    <div class="password-strength-track" aria-hidden="true">
                        <span data-password-strength-bar></span>
                    </div>
                </div>
            </div>

            <div class="field">
                <label for="password_confirmation">Confirm New Password</label>
                <div class="password-field">
                    <input class="input" id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" spellcheck="false">
                    <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false">👁️</button>
                </div>
            </div>

            <div class="span-2" style="display:flex;gap:10px;flex-wrap:wrap;">
                <button class="button" type="submit">Save Profile</button>
                <a class="ghost-button" href="{{ route('student.dashboard') }}">Cancel</a>
            </div>
        </form>
    </section>
@endsection
