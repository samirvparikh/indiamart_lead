<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign In') — {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.jpg') }}" type="image/jpeg">
    <link rel="apple-touch-icon" href="{{ asset('images/torq.jpg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="crm-auth-body">
    <div class="crm-auth-card">
        <div class="crm-auth-header">
            <h1>@yield('auth_heading', 'Welcome ' . config('app.name'))</h1>
            <p>@yield('auth_subheading', 'Sign in to access your business workspace.')</p>
        </div>
        <div class="crm-auth-body-inner">
            @yield('content')
        </div>
    </div>

    <div class="crm-auth-footer">
        <p>© {{ date('Y') }} {{ config('app.name') }} · Version 1.0.0</p>
        <p>
            <a href="#">Privacy Policy</a>
            <a href="#">Terms of Service</a>
            <a href="#">Support</a>
        </p>
    </div>

    @stack('scripts')
</body>
</html>
