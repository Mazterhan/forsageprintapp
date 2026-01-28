<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Http\Requests\Purchases\PurchaseImportRequest;
use App\Models\Purchase;
use App\Models\PurchaseImportError;
use App\Models\PurchaseItem;
use App\Models\PricingHistory;
use App\Models\PricingItem;
use App\Models\Supplier;
use App\Models\Tariff;
use App\Services\Purchases\PurchaseFileParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PurchaseImportController extends Controller
{
    public function create(): View
    {
        $suppliers = Supplier::query()
            ->orderBy('name')
            ->get()
            ->unique('name')
            ->values();

        return view('purchases.import', [
            'suppliers' => $suppliers,
        ]);
    }

    public function store(PurchaseImportRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $warnings = [];
        $vatIncluded = ($data['vat_mode'] ?? 'vat') === 'vat';

        $supplier = null;

        if (! empty($data['supplier_id'])) {
            $supplier = Supplier::find($data['supplier_id']);
        }

        if (! $supplier && ! empty($data['supplier_name'])) {
            $supplierName = trim($data['supplier_name']);

            $supplier = Supplier::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($supplierName)])
                ->first();

            if (! $supplier) {
                $supplier = Supplier::create([
                    'name' => $supplierName,
                    'code' => 'MANUAL-'.now()->format('YmdHis').'-'.Str::upper(Str::random(4)),
                    'status' => 'active',
                    'is_active' => true,
                ]);
            } else {
                $warnings[] = __('Supplier with this name already exists. Using existing supplier.');
            }
        }

        $file = $data['file'];

        $duplicateCount = 0;

        $purchase = DB::transaction(function () use ($request, $supplier, $file, $data, $vatIncluded, &$duplicateCount) {
            $purchase = Purchase::create([
                'supplier_id' => $supplier->id,
                'original_filename' => $file->getClientOriginalName(),
                'imported_by' => $request->user()?->id,
                'imported_at' => now(),
                'source_type' => Str::lower($file->getClientOriginalExtension()),
                'source_hash' => sha1_file($file->getRealPath()),
                'price_includes_vat' => $vatIncluded,
                'notes' => null,
            ]);

            $file->storeAs('purchases', $purchase->id.'-'.$file->getClientOriginalName());

            $parser = new PurchaseFileParser();
            $result = $parser->parse($file->getRealPath(), $supplier->id, [
                'extension' => $file->getClientOriginalExtension(),
                'original_name' => $file->getClientOriginalName(),
                'vat_included' => $vatIncluded,
            ]);

            $latestItems = PurchaseItem::query()
                ->select('purchase_items.*')
                ->joinSub(
                    PurchaseItem::query()
                        ->selectRaw('MAX(id) as id')
                        ->where('supplier_id', $supplier->id)
                        ->groupBy('internal_code'),
                    'latest_items',
                    'purchase_items.id',
                    '=',
                    'latest_items.id'
                )
                ->get()
                ->keyBy('internal_code');

            $items = [];
            $seen = [];
            foreach ($result['items'] as $item) {
                $existing = $latestItems->get($item['internal_code']);
                $signature = $item['internal_code'].'|'.$item['price_vat'];

                if (isset($seen[$signature])) {
                    $duplicateCount++;
                    continue;
                }

                if ($existing && $existing->name === $item['name'] && (float) $existing->price_vat === (float) $item['price_vat']) {
                    $duplicateCount++;
                    continue;
                }

                $items[] = array_merge($item, [
                    'purchase_id' => $purchase->id,
                    'supplier_id' => $supplier->id,
                    'imported_at' => $purchase->imported_at,
                    'is_active' => true,
                ]);

                $seen[$signature] = true;
            }

            if (! empty($items)) {
                PurchaseItem::insert($items);
            }

            $errorRows = [];
            foreach ($result['errors'] as $error) {
                $rowData = $error['row_data'] ?? null;
                $errorRows[] = [
                    'purchase_id' => $purchase->id,
                    'row_number' => $error['row'],
                    'message' => $error['message'],
                    'row_data' => is_array($rowData) ? json_encode($rowData, JSON_UNESCAPED_UNICODE) : $rowData,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (! empty($errorRows)) {
                PurchaseImportError::insert($errorRows);
            }

            $this->syncPricing($supplier->id, $items, $request->user()?->id);

            return $purchase;
        });

        $redirect = redirect()
            ->route('purchases.review', $purchase)
            ->with('status', __('Імпорт завершено.'));

        if ($duplicateCount > 0) {
            $warnings[] = __('Skipped :count duplicate rows.', ['count' => $duplicateCount]);
        }

        if (! empty($warnings)) {
            $redirect->with('warnings', $warnings);
        }

        return $redirect;
    }

    public function downloadTemplate(): StreamedResponse
    {
        $headers = [
            'external_code(if_exist)',
            'name',
            'price',
            'unit(if_exist)',
            'quantity(if_exist)',
            'category(if_exist)',
        ];

        $callback = function () use ($headers): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, $headers, ';');
            fclose($handle);
        };

        return response()->streamDownload($callback, 'purchase_import_template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function downloadTemplateXlsx(): StreamedResponse
    {
        $headers = [
            'external_code(if_exist)',
            'name',
            'price',
            'unit(if_exist)',
            'quantity(if_exist)',
            'category(if_exist)',
        ];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([$headers], null, 'A1');

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'purchase_import_template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function syncPricing(int $supplierId, array $items, ?int $userId): void
    {
        if (empty($items)) {
            return;
        }

        $internalCodes = array_values(array_unique(array_map(fn ($item) => $item['internal_code'], $items)));

        $existingPricing = PricingItem::query()
            ->whereIn('internal_code', $internalCodes)
            ->get()
            ->keyBy('internal_code');

        $existingTariffs = Tariff::query()
            ->whereIn('internal_code', $internalCodes)
            ->where('is_active', true)
            ->get()
            ->keyBy('internal_code');

        foreach ($items as $item) {
            $pricing = $existingPricing->get($item['internal_code']);
            $tariff = $existingTariffs->get($item['internal_code']);

            if ($pricing) {
                if ((float) $pricing->import_price !== (float) $item['price_vat']) {
                    $markupPercent = $pricing->markup_percent ?? 50;
                    $markupPrice = $item['price_vat'] * (1 + ($markupPercent / 100));

                    $pricing->update([
                        'category' => $item['category'] ?? $pricing->category,
                        'import_price' => $item['price_vat'],
                        'markup_price' => $markupPrice,
                        'last_changed_at' => now(),
                    ]);

                }

                continue;
            }

            if (! $tariff || (float) $tariff->purchase_price !== (float) $item['price_vat']) {
                $resolvedCategory = $item['category'] ?? $tariff?->category;
                $resolvedSubcontractor = $tariff?->subcontractor_id;
                $markupPercent = 50;
                $markupPrice = $item['price_vat'] * (1 + ($markupPercent / 100));

                PricingItem::create([
                    'internal_code' => $item['internal_code'],
                    'name' => $item['name'],
                    'category' => $resolvedCategory,
                    'subcontractor_id' => $resolvedSubcontractor,
                    'import_price' => $item['price_vat'],
                    'markup_percent' => $markupPercent,
                    'markup_price' => $markupPrice,
                    'last_changed_at' => now(),
                    'is_active' => true,
                    'supplier_id' => $supplierId,
                    'external_code' => $item['external_code'] ?? null,
                    'unit' => $item['unit'] ?? null,
                    'last_imported_at' => now(),
                ]);

            }
        }
    }
}
