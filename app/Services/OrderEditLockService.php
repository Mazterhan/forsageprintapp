<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderEditLock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderEditLockService
{
    public const MESSAGE = 'Замовлення заблоковане для редагування. Відкрийте редагування зі сторінки замовлення.';

    public function active(Order $order): ?OrderEditLock
    {
        return $order->activeEditLock()->first();
    }

    public function acquire(Order $order, int $userId, string $token): OrderEditLock
    {
        return DB::transaction(function () use ($order, $userId, $token): OrderEditLock {
            Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $lock = $this->active($order);
            abort_if($lock && ($lock->user_id !== $userId || ! hash_equals($lock->lock_token, $token)), 409, self::MESSAGE);

            return OrderEditLock::query()->updateOrCreate(['order_id' => $order->id], [
                'user_id' => $userId,
                'lock_token' => Str::random(64),
                'heartbeat_at' => now(),
            ]);
        });
    }

    public function guard(Order $order, ?int $userId = null, string $token = ''): void
    {
        Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
        $lock = $this->active($order);
        abort_if($token !== '' && ! $lock, 409, self::MESSAGE);
        abort_if($lock && ($lock->user_id !== $userId || $token === '' || ! hash_equals($lock->lock_token, $token)), 409, self::MESSAGE);
    }

    public function release(Order $order, int $userId, string $token): void
    {
        OrderEditLock::query()->where('order_id', $order->id)->where('user_id', $userId)->where('lock_token', $token)->delete();
    }
}
