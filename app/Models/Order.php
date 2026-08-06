<?php

namespace App\Models;

use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Order extends Model
{
    use HasFactory, HasPublicId;

    protected $fillable = [
        'order_number',
        'customer_name',
        'client_id',
        'last_edited_by',
        'items',
        'payments_total',
        'amount_due',
        'total_cost',
    ];

    protected $casts = [
        'last_edited_by' => 'integer',
        'client_id' => 'integer',
        'items' => 'array',
        'payments_total' => 'decimal:2',
        'amount_due' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::created(function (Order $order): void {
            if (blank($order->order_number)) {
                $order->forceFill([
                    'order_number' => sprintf('O-%06d', $order->id),
                ])->saveQuietly();
            }
        });

        static::updated(function (Order $order): void {
            if (! $order->wasChanged('items')) {
                return;
            }

            $beforeItems = json_decode((string) ($order->getRawOriginal('items') ?? '[]'), true);
            $beforeItems = is_array($beforeItems) ? $beforeItems : [];
            $afterItems = is_array($order->items) ? $order->items : [];

            $order->recordItemChanges($beforeItems, $afterItems);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function lastEditedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_edited_by');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(OrderHistory::class)->latest('created_at');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ClientPayment::class);
    }

    /**
     * Adds stable identifiers to positions created before item_id was introduced.
     * The technical backfill must not appear in the user-facing change history.
     */
    public function ensureItemIds(): void
    {
        $items = is_array($this->items) ? $this->items : [];
        $changed = false;

        foreach ($items as &$item) {
            if (! is_array($item) || filled($item['item_id'] ?? null)) {
                continue;
            }

            $item['item_id'] = (string) str()->uuid();
            $changed = true;
        }
        unset($item);

        if ($changed) {
            $this->forceFill(['items' => $items])->saveQuietly();
            $this->syncOriginalAttribute('items');
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $beforeItems
     * @param  array<int, array<string, mixed>>  $afterItems
     */
    private function recordItemChanges(array $beforeItems, array $afterItems): void
    {
        $beforeByKey = $this->itemsByStableKey($beforeItems);
        $afterByKey = $this->itemsByStableKey($afterItems);
        $userId = Auth::id() ?: $this->last_edited_by;

        foreach (array_diff_key($beforeByKey, $afterByKey) as $entry) {
            $this->histories()->create([
                'user_id' => $userId,
                'operation_type' => 'item_deleted',
                'item_index' => $entry['index'],
                'description' => 'Видалено позицію номенклатури',
                'before_value' => $entry['item'],
                'after_value' => null,
            ]);
        }

        foreach (array_diff_key($afterByKey, $beforeByKey) as $entry) {
            $this->histories()->create([
                'user_id' => $userId,
                'operation_type' => 'item_created',
                'item_index' => $entry['index'],
                'description' => 'Додано позицію номенклатури',
                'before_value' => null,
                'after_value' => $entry['item'],
            ]);
        }

        $fieldLabels = [
            'nomenclature' => 'Номенклатура',
            'quantity' => 'Кількість',
            'unit_cost' => 'Вартість за одн.',
        ];

        foreach (array_intersect_key($afterByKey, $beforeByKey) as $key => $afterEntry) {
            $beforeEntry = $beforeByKey[$key];
            foreach ($fieldLabels as $field => $label) {
                $beforeValue = $beforeEntry['item'][$field] ?? null;
                $afterValue = $afterEntry['item'][$field] ?? null;
                if ($beforeValue == $afterValue) {
                    continue;
                }

                $this->histories()->create([
                    'user_id' => $userId,
                    'operation_type' => 'item_updated',
                    'item_index' => $afterEntry['index'],
                    'field_name' => $field,
                    'description' => $label,
                    'before_value' => ['value' => $beforeValue],
                    'after_value' => ['value' => $afterValue],
                ]);
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, array{index: int, item: array<string, mixed>}>
     */
    private function itemsByStableKey(array $items): array
    {
        $result = [];
        foreach (array_values($items) as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $itemId = trim((string) ($item['item_id'] ?? ''));
            $key = $itemId !== '' ? 'id:'.$itemId : 'index:'.$index;
            $result[$key] = [
                'index' => $index + 1,
                'item' => $item,
            ];
        }

        return $result;
    }
}
