<aside class="crm-sidebar">
    <div class="crm-sidebar-search">
        <input type="text" placeholder="searchMenu" id="sidebar-search">
    </div>

    <nav>
        <div class="crm-nav-section">
            <div class="crm-nav-section-title">Operations</div>
            <a href="{{ route('dashboard') }}" class="crm-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            @can('viewAny', App\Models\Lead::class)
                <a href="{{ route('leads.index') }}" class="crm-nav-link {{ request()->routeIs('leads.*') ? 'active' : '' }}">
                    <i class="bi bi-funnel"></i> Leads
                </a>
            @endcan
            @can('viewAny', App\Models\Customer::class)
                <a href="{{ route('customers.index') }}" class="crm-nav-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                    <i class="bi bi-people"></i> Customers
                </a>
            @endcan
            @can('viewAny', App\Models\Company::class)
                <a href="{{ route('companies.index') }}" class="crm-nav-link {{ request()->routeIs('companies.*') ? 'active' : '' }}">
                    <i class="bi bi-building"></i> Companies
                </a>
            @endcan
            @can('viewAny', App\Models\Product::class)
                <a href="{{ route('products.index') }}" class="crm-nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                    <i class="bi bi-box-seam"></i> Products
                </a>
            @endcan
            @can('viewAny', App\Models\Quotation::class)
                <a href="{{ route('quotations.index') }}" class="crm-nav-link {{ request()->routeIs('quotations.*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-text"></i> Quotations
                </a>
            @endcan
            @can('viewAny', App\Models\Task::class)
                <a href="{{ route('tasks.index') }}" class="crm-nav-link {{ request()->routeIs('tasks.*') ? 'active' : '' }}">
                    <i class="bi bi-list-check"></i> Tasks
                </a>
            @endcan
        </div>

        <div class="crm-nav-section">
            <div class="crm-nav-section-title">Administration</div>
            <a href="{{ route('profile.edit') }}" class="crm-nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                <i class="bi bi-gear"></i> Settings
            </a>
        </div>
    </nav>
</aside>
