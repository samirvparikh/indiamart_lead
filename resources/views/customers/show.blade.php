@extends('layouts.crm')

@section('title', $customer->name)
@section('module', 'customers')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Home</a><span>&rsaquo;</span>
    <a href="{{ route('customers.index') }}">Customers</a><span>&rsaquo;</span> {{ $customer->name }}
@endsection

@section('toolbar')
    <div class="crm-page-title"><i class="bi bi-person"></i> {{ $customer->name }}</div>
    <div class="crm-toolbar-actions">
        @can('update', $customer)
            <a href="{{ route('customers.edit', $customer) }}" class="crm-btn crm-btn-primary-sm"><i class="bi bi-pencil"></i> Edit</a>
        @endcan
        <a href="{{ route('customers.index') }}" class="crm-btn">Back</a>
    </div>
@endsection

@section('content')
    <div class="crm-stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(160px,1fr));">
        <div class="crm-stat-card"><p>Mobile</p><h3 style="font-size:var(--crm-text-lg);">{{ $customer->mobile ?? '—' }}</h3></div>
        <div class="crm-stat-card"><p>Email</p><h3 style="font-size:var(--crm-text-lg);">{{ $customer->email ?? '—' }}</h3></div>
        <div class="crm-stat-card"><p>Company</p><h3 style="font-size:var(--crm-text-lg);">{{ $customer->company?->name ?? '—' }}</h3></div>
        <div class="crm-stat-card"><p>Leads</p><h3 style="font-size:var(--crm-text-lg);">{{ $customer->leads->count() }}</h3></div>
        <div class="crm-stat-card"><p>Quotations</p><h3 style="font-size:var(--crm-text-lg);">{{ $customer->quotations->count() }}</h3></div>
        <div class="crm-stat-card"><p>Status</p><h3 style="font-size:var(--crm-text-lg);">{{ $customer->is_active ? 'Active' : 'Inactive' }}</h3></div>
    </div>

    @if($customer->gst_number || $customer->pan || $customer->whatsapp)
        <div class="crm-content-card" style="margin-bottom:16px;">
            <div class="crm-content-card-body">
                <h4 style="margin:0 0 12px;font-size:var(--crm-text-base);color:var(--crm-muted);text-transform:uppercase;letter-spacing:0.05em;">Business Details</h4>
                <div class="crm-form-grid">
                    @if($customer->gst_number)<div><strong>GST:</strong> {{ $customer->gst_number }}</div>@endif
                    @if($customer->pan)<div><strong>PAN:</strong> {{ $customer->pan }}</div>@endif
                    @if($customer->whatsapp)<div><strong>WhatsApp:</strong> {{ $customer->whatsapp }}</div>@endif
                    @if($customer->designation)<div><strong>Designation:</strong> {{ $customer->designation }}</div>@endif
                </div>
            </div>
        </div>
    @endif

    @if($customer->notes)
        <div class="crm-content-card" style="margin-bottom:16px;">
            <div class="crm-content-card-body"><p style="margin:0;">{{ $customer->notes }}</p></div>
        </div>
    @endif

    <div class="crm-content-card" style="margin-bottom:16px;">
        <div class="crm-content-card-body">
            <h4 style="margin:0 0 12px;font-size:var(--crm-text-base);color:var(--crm-muted);text-transform:uppercase;">Contact Persons</h4>
            @forelse ($customer->contactPersons as $person)
                <div style="padding:10px 0;border-bottom:1px solid var(--crm-border);">
                    <strong>{{ $person->name }}</strong>
                    @if($person->designation)<span style="color:var(--crm-muted);"> — {{ $person->designation }}</span>@endif
                    <div style="font-size:var(--crm-text-sm);color:var(--crm-muted);margin-top:4px;">
                        {{ $person->mobile }} {{ $person->email ? '· '.$person->email : '' }}
                    </div>
                </div>
            @empty
                <p style="color:var(--crm-muted);margin:0;">No contact persons added.</p>
            @endforelse
        </div>
    </div>

    <div class="crm-content-card">
        <div class="crm-content-card-body">
            <h4 style="margin:0 0 12px;font-size:var(--crm-text-base);color:var(--crm-muted);text-transform:uppercase;">Addresses</h4>
            @forelse ($customer->addresses as $address)
                <div style="padding:10px 0;border-bottom:1px solid var(--crm-border);">
                    <strong>{{ $address->label ?? 'Address' }}</strong>
                    <p style="margin:4px 0 0;color:var(--crm-muted);">{{ $address->address }}, {{ $address->city }} {{ $address->state }}</p>
                </div>
            @empty
                <p style="color:var(--crm-muted);margin:0;">No addresses added.</p>
            @endforelse
        </div>
    </div>
@endsection
