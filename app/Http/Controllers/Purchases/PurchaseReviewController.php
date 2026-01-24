<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseImportError;
use Illuminate\View\View;

class PurchaseReviewController extends Controller
{
    public function show(Purchase $purchase): View
    {
        $items = $purchase->items()
            ->with('supplier')
            ->orderBy('id')
            ->paginate(50)
            ->withQueryString();

        $errors = PurchaseImportError::query()
            ->where('purchase_id', $purchase->id)
            ->orderBy('row_number')
            ->get();

        return view('purchases.review', [
            'purchase' => $purchase->load('supplier'),
            'items' => $items,
            'errors' => $errors,
        ]);
    }
}
