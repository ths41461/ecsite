<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CartService
{
    /**
     * Cart key format: cart:{sessionId}
     */
    private function key(string $sessionId): string
    {
        return "cart:{$sessionId}";
    }

    /**
     * TTL for the cart in seconds (14 days).
     */
    private int $ttl = 14 * 24 * 60 * 60;

    /**
     * Add a variant (or increase qty if line exists).
     *
     * @return array Computed cart
     * @throws ValidationException
     */
    public function add(string $sessionId, int $variantId, int $qty): array
    {
        if ($qty < 1 || $qty > 20) {
            throw ValidationException::withMessages(['qty' => 'Quantity must be between 1 and 20.']);
        }

        $raw = $this->loadRaw($sessionId);

        $lineId = $this->lineId($variantId);
        $raw[$lineId] = [
            'variant_id' => $variantId,
            'qty'        => ($raw[$lineId]['qty'] ?? 0) + $qty,
        ];

        $this->saveRaw($sessionId, $raw);

        // Recompute with server pricing/stock rules
        return $this->get($sessionId);
    }

    /**
     * Update a line's quantity (0 removes).
     *
     * @return array Computed cart
     * @throws ValidationException
     */
    public function update(string $sessionId, string $lineId, int $qty): array
    {
        if ($qty < 0 || $qty > 20) {
            throw ValidationException::withMessages(['qty' => 'Quantity must be between 0 and 20.']);
        }

        $raw = $this->loadRaw($sessionId);

        if (!isset($raw[$lineId])) {
            // idempotent: ignore unknown line
            return $this->get($sessionId);
        }

        if ($qty === 0) {
            unset($raw[$lineId]);
        } else {
            $raw[$lineId]['qty'] = $qty;
        }

        $this->saveRaw($sessionId, $raw);

        return $this->get($sessionId);
    }

    /**
     * Remove a line.
     *
     * @return array Computed cart
     */
    public function remove(string $sessionId, string $lineId): array
    {
        $raw = $this->loadRaw($sessionId);
        unset($raw[$lineId]);
        $this->saveRaw($sessionId, $raw);

        return $this->get($sessionId);
    }

    /**
     * Get computed cart (lines + totals).
     * Server is source of truth: fetches latest prices/stock from DB.
     *
     * @return array{
     *   lines: array<int, array{
     *     line_id: string,
     *     variant_id: int,
     *     sku: string,
     *     product: array{id:int,name:string,slug:string},
     *     price_cents: int,
     *     compare_at_cents: int|null,
     *     qty: int,
     *     managed: bool,
     *     available_qty: int|null,
     *     line_total_cents: int,
     *     savings_cents: int,
     *     stock_badge: string
     *   }>,
     *   subtotal_cents: int,
     *   savings_cents: int,
     *   total_cents: int,
     *   currency: string
     * }
     */
    public function get(string $sessionId): array
    {
        $raw = $this->loadRaw($sessionId);
        if (empty($raw)) {
            return [
                'lines' => [],
                'subtotal_cents' => 0,
                'savings_cents' => 0,
                'total_cents' => 0,
                'currency' => 'JPY',
            ];
        }

        $byLine = collect($raw); // [lineId => ['variant_id'=>, 'qty'=>]]

        $variantIds = $byLine->pluck('variant_id')->filter()->map(fn($v) => (int)$v)->all();
        if (empty($variantIds)) {
            // corrupted cart; wipe
            $this->saveRaw($sessionId, []);
            return [
                'lines' => [],
                'subtotal_cents' => 0,
                'savings_cents' => 0,
                'total_cents' => 0,
                'currency' => 'JPY',
            ];
        }

        // Pull fresh pricing + stock from DB. (No model assumptions needed.)
        $rows = DB::table('product_variants as pv')
            ->join('products as p', 'p.id', '=', 'pv.product_id')
            ->leftJoin('inventories as inv', 'inv.product_variant_id', '=', 'pv.id')
            ->select([
                'pv.id as variant_id',
                'pv.sku',
                'pv.product_id',
                'pv.price_yen',
                'pv.sale_price_yen',
                'p.name as product_name',
                'p.slug as product_slug',
                DB::raw('COALESCE(inv.managed, 0) as managed'),
                'inv.stock',
                'inv.safety_stock',
            ])
            ->whereIn('pv.id', $variantIds)
            ->get()
            ->keyBy('variant_id');

        $lines = [];
        $subtotal = 0;
        $savings = 0;

        foreach ($byLine as $lineId => $data) {
            $variantId = (int)($data['variant_id'] ?? 0);
            $qtyWanted = (int)($data['qty'] ?? 0);
            if ($qtyWanted < 1) {
                continue; // ignore invalid
            }

            $row = $rows->get($variantId);
            if (!$row) {
                // variant deleted; drop line
                continue;
            }

            $managed = (bool)$row->managed;
            $stock = is_null($row->stock) ? null : (int)$row->stock;
            $safety = is_null($row->safety_stock) ? 0 : (int)$row->safety_stock;
            $available = null;

            if ($managed) {
                $available = max(0, (int)$stock - (int)$safety);
                if ($available === 0) {
                    // nothing available; clamp to 0 (line will still show as out-of-stock)
                    $qty = 0;
                } else {
                    $qty = min($qtyWanted, $available);
                }
            } else {
                $qty = $qtyWanted;
            }

            // Compute prices from server-side values (DB stores yen, convert to cents)
            $priceYen = !is_null($row->sale_price_yen)
                ? (int)$row->sale_price_yen
                : (int)$row->price_yen;
            $compareYen = !is_null($row->sale_price_yen)
                ? (int)$row->price_yen
                : null;
            $price = $priceYen * 100;
            $compare = is_null($compareYen) ? null : $compareYen * 100;
            $lineTotal = $qty * $price;
            $lineSavings = ($compare && $compare > $price) ? ($compare - $price) * $qty : 0;

            $lines[] = [
                'line_id'          => (string)$lineId,
                'variant_id'       => $variantId,
                'sku'              => (string)$row->sku,
                'product'          => [
                    'id'   => (int)$row->product_id,
                    'name' => (string)$row->product_name,
                    'slug' => (string)$row->product_slug,
                ],
                'price_cents'      => $price,
                'compare_at_cents' => $compare,
                'qty'              => $qty,
                'managed'          => $managed,
                'available_qty'    => $available,
                'line_total_cents' => $lineTotal,
                'savings_cents'    => $lineSavings,
                'stock_badge'      => $this->stockBadge($managed, $stock, $safety),
            ];

            $subtotal += $lineTotal;
            $savings  += $lineSavings;
        }

        // Filter out clamped-to-0 lines when managed stock says none
        $lines = array_values(array_filter($lines, fn($l) => $l['qty'] > 0));

        return [
            'lines'          => $lines,
            'subtotal_cents' => $subtotal,
            'savings_cents'  => $savings,
            'total_cents'    => $subtotal, // No tax/shipping yet
            'currency'       => 'JPY',
        ];
    }

    /**
     * Clear cart (not part of 4.3.0 public API, but useful internally/tests).
     */
    public function clear(string $sessionId): void
    {
        Redis::del($this->key($sessionId));
    }

    /**
     * ===== Internals =====
     */

    private function loadRaw(string $sessionId): array
    {
        $val = Redis::get($this->key($sessionId));
        if (!$val) return [];
        $arr = json_decode($val, true);
        return is_array($arr) ? $arr : [];
    }

    private function saveRaw(string $sessionId, array $raw): void
    {
        // Normalize: strip invalid entries, clamp qty to [1, 20]
        $normalized = [];
        foreach ($raw as $lineId => $line) {
            $vid = (int)($line['variant_id'] ?? 0);
            $qty = (int)($line['qty'] ?? 0);
            if ($vid > 0 && $qty > 0) {
                $normalized[(string)$lineId] = [
                    'variant_id' => $vid,
                    'qty'        => min(20, max(1, $qty)),
                ];
            }
        }

        if (empty($normalized)) {
            Redis::del($this->key($sessionId));
            return;
        }

        Redis::setex($this->key($sessionId), $this->ttl, json_encode($normalized));
    }

    private function lineId(int $variantId): string
    {
        // For now, 1:1 with variant (no options). Hashing keeps format flexible for future options.
        return (string)$variantId;
    }

    private function stockBadge(bool $managed, ?int $stock, int $safety): string
    {
        if (!$managed) return 'In stock';
        $s = (int)($stock ?? 0);
        if ($s <= 0) return 'Out of stock';
        if ($s <= $safety) return 'Low stock';
        return 'In stock';
    }
}
