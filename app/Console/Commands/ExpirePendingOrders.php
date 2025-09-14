<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Services\OrderService;

class ExpirePendingOrders extends Command
{
    protected $signature = 'orders:expire-pending';
    protected $description = 'Expire and cancel pending orders past TTL';

    public function handle(OrderService $orders): int
    {
        $pendingId = (int) DB::table('order_statuses')->where('code', 'pending')->value('id');
        if (!$pendingId) return self::SUCCESS;

        $rows = Order::where('order_status_id', $pendingId)
            ->whereNotNull('pending_expires_at')
            ->where('pending_expires_at', '<', now())
            ->limit(200)
            ->get();

        foreach ($rows as $order) {
            $orders->cancelIfNotPaid($order->loadMissing('items'), 'expired', (string)($order->cart_session_id ?? ''));
        }

        $this->info('Expired ' . $rows->count() . ' orders.');
        return self::SUCCESS;
    }
}

