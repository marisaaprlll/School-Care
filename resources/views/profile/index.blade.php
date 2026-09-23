@extends('layouts.app')

@php($activePage = 'profile')
@php($crumb = __('nav.menu_profile'))

@section('title', __('nav.menu_profile'))

@section('content')
<div class="page-head">
    <div>
        <p class="eyebrow">{{ __('profile.eyebrow') }}</p>
        <h1>{{ __('profile.title') }}</h1>
        <p>{{ __('profile.desc') }}</p>
    </div>
</div>

<div class="profile-grid">
    <section class="panel profile-card">
        <div class="big-avatar">{{ $user->avatar }}</div>
        <h2>{{ $user->name }}</h2>
        <p>{{ $user->isAdmin() ? __('profile.role_admin') : __('profile.role_reporter_prefix') . $user->class_name }}</p>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn btn-danger btn-full" type="submit">{{ __('profile.logout_button') }}</button>
        </form>
    </section>

    <section class="panel form-panel">
        <h2>{{ __('profile.settings_title') }}</h2>
        <p>{{ __('profile.settings_desc') }}</p>

        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PUT')

            @if ($errors->any())
                <div class="error" style="margin-bottom:14px">{{ $errors->first() }}</div>
            @endif

            <div class="field"><label for="profileName">{{ __('profile.label_name') }}</label><input id="profileName" name="name" value="{{ old('name', $user->name) }}" required></div>
            <div class="field"><label for="profileEmail">{{ __('profile.label_email') }}</label><input id="profileEmail" name="email" value="{{ old('email', $user->email) }}" type="email" required></div>
            <div class="field"><label for="profileClass">{{ __('profile.label_class') }}</label><input id="profileClass" name="class_name" value="{{ old('class_name', $user->class_name) }}" required></div>
            <div class="field"><label for="newPassword">{{ __('profile.label_new_password') }}</label><input id="newPassword" name="password" type="password" placeholder="{{ __('profile.placeholder_new_password') }}"></div>
            <div class="field"><label for="newPasswordConfirm">{{ __('profile.label_confirm_password') }}</label><input id="newPasswordConfirm" name="password_confirmation" type="password"></div>
            <button class="btn btn-primary" type="submit">{{ __('profile.submit') }}</button>
        </form>

        @if (session('success'))
            <div class="success" style="margin-top:14px">{{ session('success') }}</div>
        @endif
    </section>
</div>
@endsection
