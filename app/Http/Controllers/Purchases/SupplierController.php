<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchases\StoreSupplierRequest;
use App\Http\Requests\Purchases\UpdateSupplierRequest;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\SupplierDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $search = (string) $request->query('search', '');
        $category = (string) $request->query('category', '');

        $query = Supplier::query();

        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($category !== '') {
            $query->where('category', $category);
        }

        $categories = Supplier::query()
            ->when($search !== '', function ($sub) use ($search) {
                $sub->where(function ($inner) use ($search) {
                    $inner
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $suppliers = $query
            ->withCount('purchaseItems')
            ->withMax('purchases', 'imported_at')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('purchases.suppliers.index', [
            'suppliers' => $suppliers,
            'categories' => $categories,
            'filters' => [
                'search' => $search,
                'category' => $category,
            ],
        ]);
    }

    public function create(): View
    {
        return view('purchases.suppliers.create');
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $supplier = Supplier::create($request->validated());

        return redirect()
            ->route('purchases.suppliers.show', $supplier)
            ->with('status', __('Supplier created.'));
    }

    public function show(Supplier $supplier): View
    {
        $documents = $supplier->documents()
            ->orderByDesc('uploaded_at')
            ->get();

        return view('purchases.suppliers.show', [
            'supplier' => $supplier,
            'documents' => $documents,
        ]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        return redirect()
            ->route('purchases.suppliers.show', $supplier)
            ->with('status', __('Supplier updated.'));
    }

    public function toggleActive(Supplier $supplier): RedirectResponse
    {
        $newActive = ! $supplier->is_active;
        $supplier->update([
            'is_active' => $newActive,
            'status' => $newActive ? 'active' : 'paused',
        ]);

        if (! $newActive) {
            PurchaseItem::where('supplier_id', $supplier->id)->update(['is_active' => false]);
        }

        return redirect()
            ->route('purchases.suppliers.index')
            ->with('status', __('Supplier status updated.'));
    }

    public function storeDocument(Request $request, Supplier $supplier): RedirectResponse
    {
        $request->validate([
            'document' => ['required', 'file', 'max:10240'],
        ]);

        $file = $request->file('document');
        $path = $file->store('supplier-documents');

        SupplierDocument::create([
            'supplier_id' => $supplier->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_ext' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $request->user()?->id,
            'uploaded_at' => now(),
        ]);

        return redirect()
            ->route('purchases.suppliers.show', $supplier)
            ->with('status', __('Document uploaded.'));
    }

    public function downloadDocument(SupplierDocument $document)
    {
        return Storage::download($document->file_path, $document->file_name);
    }
}
