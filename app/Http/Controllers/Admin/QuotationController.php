<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Services\QuotationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuotationController extends Controller
{
    public function __construct(
        protected QuotationService $quotationService,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Quotation::class);

        return view('quotations.index', [
            'customers' => \App\Models\Customer::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'products' => \App\Models\Product::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'price', 'tax_rate', 'unit', 'sku']),
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Quotation::class);

        $quotations = $this->quotationService->list(
            $request->only(['search', 'status']),
            (int) $request->input('per_page', 25)
        );

        return response()->json([
            'success' => true,
            'data' => $quotations->items(),
            'meta' => [
                'current_page' => $quotations->currentPage(),
                'last_page' => $quotations->lastPage(),
                'per_page' => $quotations->perPage(),
                'total' => $quotations->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Quotation::class);

        $data = $request->validate([
            'lead_id' => ['nullable', 'exists:leads,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'quotation_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date'],
            'terms' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'discount_type' => ['nullable', 'string', 'max:20'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.product_name' => ['required', 'string', 'max:191'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['created_by'] = $request->user()->id;
        $quotation = $this->quotationService->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Quotation created successfully.',
            'data' => $quotation,
        ], 201);
    }

    public function destroy(Quotation $quotation): JsonResponse
    {
        $this->authorize('delete', $quotation);

        $this->quotationService->delete($quotation);

        return response()->json([
            'success' => true,
            'message' => 'Quotation deleted successfully.',
        ]);
    }
}
