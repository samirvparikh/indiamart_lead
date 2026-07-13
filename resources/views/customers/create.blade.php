@extends('layouts.crm')

@section('title', 'Create Customer')
@section('module', 'customers')

@section('breadcrumb')
    <a href="{{ route('dashboard') }}">Home</a><span>&rsaquo;</span>
    <a href="{{ route('customers.index') }}">Customers</a><span>&rsaquo;</span> Create
@endsection

@section('toolbar')
    <div class="crm-page-title"><i class="bi bi-person-plus"></i> Create Customer</div>
@endsection

@section('content')
    <div class="crm-content-card">
        <div class="crm-content-card-body">
            <form id="customer-form">
                <div class="crm-form-grid">
                    <div class="crm-field">
                        <label class="crm-field-label">Name *</label>
                        <input class="crm-input" name="name" required>
                    </div>
                    <div class="crm-field">
                        <label class="crm-field-label">Company</label>
                        <select class="crm-input" name="company_id">
                            <option value="">None</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="crm-field"><label class="crm-field-label">Mobile</label><input class="crm-input" name="mobile"></div>
                    <div class="crm-field"><label class="crm-field-label">Email</label><input class="crm-input" type="email" name="email"></div>
                    <div class="crm-field"><label class="crm-field-label">WhatsApp</label><input class="crm-input" name="whatsapp"></div>
                    <div class="crm-field"><label class="crm-field-label">Designation</label><input class="crm-input" name="designation"></div>
                    <div class="crm-field"><label class="crm-field-label">GST Number</label><input class="crm-input" name="gst_number"></div>
                    <div class="crm-field"><label class="crm-field-label">PAN</label><input class="crm-input" name="pan"></div>
                </div>
                <div class="crm-field"><label class="crm-field-label">Notes</label><textarea class="crm-input" name="notes" rows="2"></textarea></div>
                <div class="crm-toolbar-actions" style="margin-top:16px;">
                    <button type="submit" class="crm-btn crm-btn-primary-sm">Save Customer</button>
                    <a href="{{ route('customers.index') }}" class="crm-btn">Cancel</a>
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
    const data = Object.fromEntries(new FormData(e.target));
    if (!data.company_id) delete data.company_id;
    const res = await fetch('{{ route('customers.store') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify(data),
    });
    const json = await res.json();
    if (json.success) window.location.href = '{{ route('customers.index') }}';
    else document.getElementById('form-message').textContent = json.message || Object.values(json.errors || {}).flat().join(' ');
});
</script>
@endpush
