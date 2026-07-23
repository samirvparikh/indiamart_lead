<aside class="crm-sidebar" id="crm-sidebar">
    {{-- <div class="crm-sidebar-search">
        <i class="bi bi-search"></i>
        <input type="text" placeholder="searchMenu" id="sidebar-search">
    </div> --}}

    <nav>
        <div class="crm-nav-section">
            <div class="crm-nav-section-title">Operations</div>
            <a href="{{ route('dashboard') }}" class="crm-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Dashboard">
                <i class="bi bi-speedometer2"></i><span class="crm-nav-label">Dashboard</span>
            </a>
            @can('viewAny', App\Models\Lead::class)
                <div class="crm-nav-group {{ request()->routeIs('leads.*') ? 'is-open' : '' }}">
                    <button type="button"
                            class="crm-nav-link crm-nav-parent {{ request()->routeIs('leads.*') ? 'active' : '' }}"
                            title="Leads"
                            aria-expanded="{{ request()->routeIs('leads.*') ? 'true' : 'false' }}"
                            aria-controls="leads-submenu">
                        <i class="bi bi-funnel"></i>
                        <span class="crm-nav-label">Leads</span>
                        <i class="bi bi-chevron-down crm-nav-chevron"></i>
                    </button>
                    <div class="crm-nav-submenu" id="leads-submenu">
                        <a href="{{ route('leads.my') }}" class="crm-nav-submenu-link {{ request()->routeIs('leads.my*') ? 'active' : '' }}">
                            <i class="bi bi-person-check"></i><span>My Leads</span>
                        </a>
                        <a href="{{ route('leads.all') }}" class="crm-nav-submenu-link {{ request()->routeIs('leads.all*') || request()->routeIs('leads.index') ? 'active' : '' }}">
                            <i class="bi bi-people"></i><span>All Leads</span>
                        </a>
                    </div>
                </div>
            @endcan
            @can('followups.view')
                <div class="crm-nav-group {{ request()->routeIs('followups.*') ? 'is-open' : '' }}">
                    <button type="button"
                            class="crm-nav-link crm-nav-parent {{ request()->routeIs('followups.*') ? 'active' : '' }}"
                            title="Followups"
                            aria-expanded="{{ request()->routeIs('followups.*') ? 'true' : 'false' }}"
                            aria-controls="followups-submenu">
                        <i class="bi bi-calendar2-check"></i>
                        <span class="crm-nav-label">Followups</span>
                        <i class="bi bi-chevron-down crm-nav-chevron"></i>
                    </button>
                    <div class="crm-nav-submenu" id="followups-submenu">
                        <a href="{{ route('followups.my') }}" class="crm-nav-submenu-link {{ request()->routeIs('followups.my*') || request()->routeIs('followups.index') ? 'active' : '' }}">
                            <i class="bi bi-person-check"></i><span>My Followups</span>
                        </a>
                        <a href="{{ route('followups.all') }}" class="crm-nav-submenu-link {{ request()->routeIs('followups.all*') ? 'active' : '' }}">
                            <i class="bi bi-calendar-week"></i><span>All Followups</span>
                        </a>
                    </div>
                </div>
            @endcan
            @can('viewAny', App\Models\Customer::class)
                <a href="{{ route('customers.index') }}" class="crm-nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}" title="Customers">
                    <i class="bi bi-people"></i><span class="crm-nav-label">Customers</span>
                </a>
            @endcan
            @can('viewAny', App\Models\Company::class)
                <a href="{{ route('companies.index') }}" class="crm-nav-link {{ request()->routeIs('companies.*') ? 'active' : '' }}" title="Companies">
                    <i class="bi bi-building"></i><span class="crm-nav-label">Companies</span>
                </a>
            @endcan
            @can('viewAny', App\Models\Product::class)
                <a href="{{ route('products.index') }}" class="crm-nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}" title="Products">
                    <i class="bi bi-box-seam"></i><span class="crm-nav-label">Products</span>
                </a>
            @endcan
            @can('viewAny', App\Models\Quotation::class)
                <a href="{{ route('quotations.index') }}" class="crm-nav-link {{ request()->routeIs('quotations.*') ? 'active' : '' }}" title="Quotations">
                    <i class="bi bi-file-earmark-text"></i><span class="crm-nav-label">Quotations</span>
                </a>
                <a href="{{ route('quotation-terms.index') }}" class="crm-nav-link {{ request()->routeIs('quotation-terms.*') ? 'active' : '' }}" title="Quotation Terms">
                    <i class="bi bi-card-checklist"></i><span class="crm-nav-label">Quotation Terms</span>
                </a>
            @endcan
            @can('viewAny', App\Models\Task::class)
                <a href="{{ route('tasks.index') }}" class="crm-nav-link {{ request()->routeIs('tasks.*') ? 'active' : '' }}" title="Tasks">
                    <i class="bi bi-list-check"></i><span class="crm-nav-label">Tasks</span>
                </a>
            @endcan
        </div>

        @if (auth()->user()?->canAccessAdministration())
            <div class="crm-nav-section">
                <div class="crm-nav-section-title">Administration</div>
                @can('users.view')
                    <a href="{{ route('users.index') }}" class="crm-nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" title="Users">
                        <i class="bi bi-person-gear"></i><span class="crm-nav-label">Users</span>
                    </a>
                @endcan
                @can('roles.view')
                    <a href="{{ route('roles.index') }}" class="crm-nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" title="Roles">
                        <i class="bi bi-shield-lock"></i><span class="crm-nav-label">Roles</span>
                    </a>
                @endcan
                @can('permissions.view')
                    <a href="{{ route('permissions.index') }}" class="crm-nav-link {{ request()->routeIs('permissions.*') ? 'active' : '' }}" title="Permissions">
                        <i class="bi bi-key"></i><span class="crm-nav-label">Permissions</span>
                    </a>
                @endcan
                @can('settings.view')
                    <a href="{{ route('settings.company.edit') }}" class="crm-nav-link {{ request()->routeIs('settings.company.*') ? 'active' : '' }}" title="Company Profile">
                        <i class="bi bi-building-gear"></i><span class="crm-nav-label">Company Profile</span>
                    </a>
                @endcan
                <a href="{{ route('profile.edit') }}" class="crm-nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}" title="Settings">
                    <i class="bi bi-gear"></i><span class="crm-nav-label">Settings</span>
                </a>
                @if (auth()->user()?->isSuperAdmin())
                    <a href="{{ route('user-activities.index') }}" class="crm-nav-link {{ request()->routeIs('user-activities.*') ? 'active' : '' }}" title="User Activity">
                        <i class="bi bi-activity"></i><span class="crm-nav-label">User Activity</span>
                    </a>
                @endif
            </div>
        @endif

        <div class="crm-nav-section">
            <div class="crm-nav-section-title">Help</div>
            <a href="{{ route('docs.user-manual') }}" class="crm-nav-link" title="User Manual PDF" target="_blank">
                <i class="bi bi-file-earmark-pdf"></i><span class="crm-nav-label">User Manual</span>
            </a>
        </div>
    </nav>
</aside>
