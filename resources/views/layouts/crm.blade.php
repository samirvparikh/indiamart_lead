<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="crm-app-body">
    @include('partials.crm.header')

    <div class="crm-app-shell">
        @include('partials.crm.sidebar')

        @hasSection('module')
            @include('partials.crm.sub-sidebar', ['module' => trim($__env->yieldContent('module'))])
        @endif

        <main class="crm-main">
            @hasSection('breadcrumb')
                <nav class="crm-breadcrumb">@yield('breadcrumb')</nav>
            @endif

            @hasSection('toolbar')
                <div class="crm-page-toolbar">@yield('toolbar')</div>
            @endif

            @yield('content')
        </main>
    </div>

    @stack('scripts')
    <script>
    function toggleFullscreen() {
        if (!document.fullscreenElement) document.documentElement.requestFullscreen();
        else document.exitFullscreen();
    }

    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            document.getElementById('crm-quick-find')?.focus();
        }
    });
    </script>
</body>
</html>
