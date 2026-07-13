@extends('layouts.crm')

@section('title', 'Edit Customer')
@section('module', 'customers')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Home</a><span>&rsaquo;</span>
    <a href="{{ route('customers.index') }}">Customers</a><span>&rsaquo;</span> Edit
@endsection

@section('toolbar')
    <div class="crm-page-title"><i class="bi bi-pencil"></i> Edit {{ $customer->name }}</div>
@endsection

@section('content')
    <div class="crm-content-card">
        <div class="crm-content-card-body">
            <form id="customer-form">
                <div class="crm-form-grid">
                    <div class="crm-field">
                        <label class="crm-field-label">Name *</label>
                        <input class="crm-input" name="name" value="{{ $customer->name }}" required>
                    </div>
                    <div class="crm-field">
                        <label class="crm-field-label">Company</label>
                        <select class="crm-input" name="company_id">
                            <option value="">None</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}" @selected($customer->company_id == $company->id)>{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="crm-field"><label class="crm-field-label">Mobile</label><input class="crm-input" name="mobile" value="{{ $customer->mobile }}"></div>
                    <div class="crm-field"><label class="crm-field-label">Email</label><input class="crm-input" type="email" name="email" value="{{ $customer->email }}"></div>
                    <div class="crm-field"><label class="crm-field-label">WhatsApp</label><input class="crm-input" name="whatsapp" value="{{ $customer->whatsapp }}"></div>
                    <div class="crm-field"><label class="crm-field-label">GST</label><input class="crm-input" name="gst_number" value="{{ $customer->gst_number }}"></div>
                    <div class="crm-field"><label class="crm-field-label">PAN</label><input class="crm-input" name="pan" value="{{ $customer->pan }}"></div>
                    <div class="crm-field">
                        <label class="crm-checkbox"><input type="checkbox" name="is_active" value="1" @checked($customer->is_active)> Active</label>
                    </div>
                </div>
                <div class="crm-field"><label class="crm-field-label">Notes</label><textarea class="crm-input" name="notes" rows="2">{{ $customer->notes }}</textarea></div>
                <div class="crm-toolbar-actions" style="margin-top:16px;">
                    <button type="submit" class="crm-btn crm-btn-primary-sm">Update Customer</button>
                    <a href="{{ route('customers.show', $customer) }}" class="crm-btn">Cancel</a>
                </div>
                <p id="form-message" class="crm-auth-error"></p>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.getElementById('customer-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const data = Object.fromEntries(new FormData(form));
    data.is_active = form.elements.is_active.checked;
    if (!data.company_id) data.company_id = null;
    const res = await fetch('{{ route('customers.update', $customer) }}', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify(data),
    });
    const json = await res.json();
    if (json.success) window.location.href = '{{ route('customers.show', $customer) }}';
    else document.getElementById('form-message').textContent = json.message || Object.values(json.errors || {}).flat().join(' ');
});
</script>
@endpush
