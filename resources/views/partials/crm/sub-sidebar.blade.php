@php
    $module = $module ?? '';
    $menus = [
        'leads' => [
            'title' => 'Leads',
            'groups' => [
                'MANAGEMENT' => [
                    ['route' => 'leads.index', 'label' => 'All Leads', 'pattern' => 'leads.index'],
                    ['route' => 'leads.create', 'label' => 'Create Lead', 'pattern' => 'leads.create'],
                ],
            ],
        ],
        'customers' => [
            'title' => 'Customers',
            'groups' => [
                'DIRECTORY' => [
                    ['route' => 'customers.index', 'label' => 'All Customers', 'pattern' => 'customers.index'],
                    ['route' => 'customers.create', 'label' => 'Add Customer', 'pattern' => 'customers.create'],
                ],
            ],
        ],
        'products' => [
            'title' => 'Products',
            'groups' => [
                'CATALOG' => [
                    ['route' => 'products.index', 'label' => 'All Products', 'pattern' => 'products.index'],
                ],
            ],
        ],
        'companies' => [
            'title' => 'Companies',
            'groups' => [
                'DIRECTORY' => [
                    ['route' => 'companies.index', 'label' => 'All Companies', 'pattern' => 'companies.index'],
                ],
            ],
        ],
        'quotations' => [
            'title' => 'Quotations',
            'groups' => [
                'SALES' => [
                    ['route' => 'quotations.index', 'label' => 'All Quotations', 'pattern' => 'quotations.index'],
                ],
            ],
        ],
        'tasks' => [
            'title' => 'Tasks',
            'groups' => [
                'WORKFLOW' => [
                    ['route' => 'tasks.index', 'label' => 'All Tasks', 'pattern' => 'tasks.index'],
                ],
            ],
        ],
    ];
    $menu = $menus[$module] ?? null;
@endphp

@if($menu)
<aside class="crm-subsidebar">
    @foreach($menu['groups'] as $groupTitle => $items)
        <div class="crm-subnav-group-title">{{ $groupTitle }}</div>
        @foreach($items as $item)
            <a href="{{ route($item['route']) }}"
               class="crm-subnav-link {{ request()->routeIs($item['pattern']) ? 'active' : '' }}">
                {{ $item['label'] }}
            </a>
        @endforeach
    @endforeach
</aside>
@endif
