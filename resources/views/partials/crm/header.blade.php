@php
    $user = auth()->user();
    $initials = collect(explode(' ', $user->name))->map(fn ($w) => strtoupper(substr($w, 0, 1)))->take(2)->join('');
    $companyName = \App\Models\Setting::getValue('company', 'name', config('app.name'));
    $branchName = \App\Models\Setting::getValue('company', 'branch', 'Head Office');
    $fyStart = now()->month >= 4 ? now()->year : now()->year - 1;
    $fyLabel = 'FY '.$fyStart.'-'.substr((string) ($fyStart + 1), -2);
@endphp

<header class="crm-header">
    <div class="crm-header-left">
        <a href="{{ route('dashboard') }}" class="crm-brand">
            <div class="crm-brand-icon"><i class="bi bi-infinity"></i></div>
            <span class="crm-brand-text">{{ config('app.name') }}</span>
        </a>

        <div class="crm-header-divider"></div>

        <div class="crm-header-meta">
            <button type="button" class="crm-meta-chip" title="Company">
                <span class="crm-meta-chip-icon"><i class="bi bi-buildings"></i></span>
                <span class="crm-meta-chip-body">
                    <span class="crm-meta-chip-label">Company</span>
                    <span class="crm-meta-chip-value">{{ $companyName }}</span>
                </span>
                <i class="bi bi-chevron-down crm-meta-chip-caret"></i>
            </button>

            <button type="button" class="crm-meta-chip" title="Branch">
                <span class="crm-meta-chip-icon"><i class="bi bi-geo-alt"></i></span>
                <span class="crm-meta-chip-body">
                    <span class="crm-meta-chip-label">Branch</span>
                    <span class="crm-meta-chip-value">{{ $branchName }}</span>
                </span>
                <i class="bi bi-chevron-down crm-meta-chip-caret"></i>
            </button>

            <button type="button" class="crm-meta-chip" title="Period">
                <span class="crm-meta-chip-icon"><i class="bi bi-calendar3"></i></span>
                <span class="crm-meta-chip-body">
                    <span class="crm-meta-chip-label">Period</span>
                    <span class="crm-meta-chip-value">
                        <span class="crm-period-badge">{{ $fyLabel }}</span>
                    </span>
                </span>
                <i class="bi bi-chevron-down crm-meta-chip-caret"></i>
            </button>
        </div>
    </div>

    <div class="crm-header-right">
        <div class="crm-header-search">
            <i class="bi bi-search"></i>
            <input type="text" placeholder="Quick Find..." id="crm-quick-find" autocomplete="off">
            <kbd class="crm-search-kbd">Ctrl+K</kbd>
        </div>

        <div class="crm-header-actions">
            <a href="#" class="crm-icon-btn" title="Notifications">
                <i class="bi bi-bell"></i>
                <span class="crm-badge-dot">3</span>
            </a>
            <a href="#" class="crm-icon-btn" title="Messages">
                <i class="bi bi-envelope"></i>
                <span class="crm-badge-dot">2</span>
            </a>
            <a href="{{ route('tasks.index') }}" class="crm-icon-btn" title="Tasks">
                <i class="bi bi-clipboard-check"></i>
            </a>
            <button type="button" class="crm-icon-btn" title="Full Screen" onclick="toggleFullscreen()">
                <i class="bi bi-arrows-fullscreen"></i>
            </button>

            <div class="crm-user-menu" x-data="{ open: false }" @click.outside="open = false">
                <button type="button" class="crm-user-trigger" @click="open = !open" title="{{ $user->name }}">
                    <span class="crm-user-avatar">{{ $initials }}</span>
                    <span class="crm-user-info">
                        <span class="crm-user-name">{{ $user->name }}</span>
                        <span class="crm-user-role">{{ $user->primaryRoleName() ?? 'User' }}</span>
                    </span>
                    <i class="bi bi-chevron-down"></i>
                </button>
                <div class="crm-user-dropdown" x-show="open" x-cloak x-transition>
                    <div class="crm-user-dropdown-head">
                        <strong>{{ $user->name }}</strong>
                        <span>{{ $user->email }}</span>
                    </div>
                    <a href="{{ route('profile.edit') }}"><i class="bi bi-person"></i> Profile Settings</a>
                    <a href="{{ route('dashboard') }}"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"><i class="bi bi-box-arrow-right"></i> Sign Out</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
