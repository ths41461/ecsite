<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    /** @var int TTL for Redis key in seconds (from config/cart.php) */
    private int $ttl;

    /** @var int Max quantity per line (from config/cart.php) */
    private int $maxQty;

    /** @var string Currency code (from config/cart.php) */
    private string $currency;

    /** @var float Tax rate percent (from config/cart.php) */
    private float $taxRate;

    // Separate Redis key for cart metadata (e.g., coupon)
    private function metaKey(string $sessionId): string
    {
        return "cartmeta:{$sessionId}";
    }

    public function __construct(private CouponEligibilityService $couponEligibility)
    {
        // NEW: configurable TTL, max qty, currency
        $this->ttl      = (int) config('cart.ttl_seconds', 14 * 24 * 60 * 60);
        $this->maxQty   = (int) config('cart.max_qty', 20);
        $this->currency = (string) config('cart.currency', 'JPY');
        $this->taxRate  = (float) config('cart.tax_rate_percent', 0);
    }

    /**
     * Cart key format: cart:{sessionId}
     */
    private function key(string $sessionId): string
    {
        return "cart:{$sessionId}";
    }

    /**
     * Add a variant (or increase qty if line exists).
     *
     * @return array Computed cart
     * @throws ValidationException
     */
    public function add(string $sessionId, int $variantId, int $qty): array
    {
        // Only enforce minimum of 1 on add; upper bound is clamped, not rejected
        if ($qty < 1) {
            throw ValidationException::withMessages(['qty' => 'Quantity must be at least 1.']);
        }

        // NEW: pre-validate that the variant exists (avoid ghost writes)
        $exists = DB::table('product_variants')->where('id', $variantId)->exists();
        if (!$exists) {
            throw ValidationException::withMessages(['variant_id' => 'Selected variant does not exist.']);
        }

        $raw = $this->loadRaw($sessionId);

        $lineId = $this->lineId($variantId);
        $prevQty = (int)($raw[$lineId]['qty'] ?? 0);
        $requestedTotal = $prevQty + $qty;

        $raw[$lineId] = [
            'variant_id' => $variantId,
            'qty'        => min($this->maxQty, $requestedTotal),
        ];

        $this->saveRaw($sessionId, $raw);

        // Recompute with server pricing/stock rules
        $cart = $this->get($sessionId);

        // If the requested total exceeded what was finally applied (due to stock or maxQty),
        // attach a notice to the relevant line so the UI can toast it.
        foreach ($cart['lines'] as $i => $line) {
            if ((int)$line['variant_id'] === $variantId) {
                $finalQty = (int)($line['qty'] ?? 0);
                $managed = (bool)($line['managed'] ?? false);
                $availableQty = $line['available_qty'] ?? null;
                if ($managed && $availableQty !== null && $requestedTotal > $finalQty) {
                    $cart['lines'][$i]['notice'] = [
                        'code'      => 'qty_clamped_to_available',
                        'requested' => $requestedTotal,
                        'available' => $availableQty,
                    ];
                }
                break;
            }
        }

        return $cart;
    }

    /**
     * Update a line's quantity (0 removes).
     *
     * @return array Computed cart
     * @throws ValidationException
     */
    public function update(string $sessionId, string $lineId, int $qty): array
    {
        if ($qty < 0 || $qty > $this->maxQty) {
            throw ValidationException::withMessages(['qty' => "Quantity must be between 0 and {$this->maxQty}."]);
        }

        $raw = $this->loadRaw($sessionId);

        if (!isset($raw[$lineId])) {
            // Treat update as upsert when qty > 0 and line is missing (e.g., new session)
            if ($qty === 0) {
                return $this->get($sessionId);
            }

            $variantId = (int)$lineId;
            if ($variantId <= 0 || !DB::table('product_variants')->where('id', $variantId)->exists()) {
                // Unknown line and invalid variant; return current cart
                return $this->get($sessionId);
            }

            $raw[$lineId] = [
                'variant_id' => $variantId,
                'qty'        => $qty,
            ];
            $this->saveRaw($sessionId, $raw);
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
     *     stock_badge: string,
     *     notice?: array{code:string,requested:int,available:int}
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
                'tax_cents' => 0,
                'total_cents' => 0,
                'currency' => $this->currency,
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
                'tax_cents' => 0,
                'total_cents' => 0,
                'currency' => $this->currency,
            ];
        }

        // Pull fresh pricing + stock from DB
        $rows = DB::table('product_variants as pv')
            ->join('products as p', 'p.id', '=', 'pv.product_id')
            ->leftJoin('inventories as inv', 'inv.product_variant_id', '=', 'pv.id')
            ->select([
                'pv.id as variant_id',
                'pv.sku',
                'pv.product_id',
                // Compute cents server-side from yen columns
                DB::raw('CASE WHEN pv.sale_price_yen IS NULL THEN pv.price_yen * 100 ELSE pv.sale_price_yen * 100 END as price_cents'),
                DB::raw('CASE WHEN pv.sale_price_yen IS NULL THEN NULL ELSE pv.price_yen * 100 END as compare_at_cents'),
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

            $qtyFinal = $qtyWanted;
            $notice = null; // NEW: will hold clamp info for UI toast

            if ($managed) {
                $available = max(0, (int)$stock - (int)$safety);
                if ($available <= 0) {
                    $qtyFinal = 0;
                } elseif ($qtyWanted > $available) {
                    // NEW: clamp notice
                    $notice = [
                        'code'      => 'qty_clamped_to_available',
                        'requested' => $qtyWanted,
                        'available' => $available,
                    ];
                    $qtyFinal = $available;
                }
            }

            // Compute prices from server-side values
            $price = (int)$row->price_cents;
            $compare = is_null($row->compare_at_cents) ? null : (int)$row->compare_at_cents;
            $lineTotal = $qtyFinal * $price;
            $lineSavings = ($compare && $compare > $price) ? ($compare - $price) * $qtyFinal : 0;

            if ($qtyFinal > 0) {
                $line = [
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
                    'qty'              => $qtyFinal,
                    'managed'          => $managed,
                    'available_qty'    => $available,
                    'line_total_cents' => $lineTotal,
                    'savings_cents'    => $lineSavings,
                    'stock_badge'      => $this->stockBadge($managed, $stock, $safety),
                ];
                // NEW: attach clamp notice if present
                if ($notice) {
                    $line['notice'] = $notice;
                }

                $lines[] = $line;
                $subtotal += $lineTotal;
                $savings  += $lineSavings;
            }
        }

        // (same behavior) Filter out clamped-to-0 lines
        $lines = array_values(array_filter($lines, fn($l) => $l['qty'] > 0));

        // Coupon (from meta)
        $meta = $this->normalizeCouponMeta($sessionId, $this->loadMeta($sessionId));
        $couponsMeta = $meta['coupons'] ?? [];
        $activeCoupons = [];
        $couponDiscount = 0;
        $couponSummary = null;
        $couponCode = null;

        foreach ($couponsMeta as $entry) {
            $code = strtoupper((string)($entry['code'] ?? ''));
            if ($code === '') {
                continue;
            }

            $lockedLineIds = null;
            if (isset($entry['locked_line_ids']) && is_array($entry['locked_line_ids'])) {
                $ids = array_values(array_map('strval', $entry['locked_line_ids']));
                if (!empty($ids)) {
                    $lockedLineIds = $ids;
                }
            }

            $couponableLines = $lines;
            if ($lockedLineIds !== null) {
                $couponableLines = array_values(array_filter(
                    $lines,
                    fn ($line) => isset($line['line_id']) && in_array((string)$line['line_id'], $lockedLineIds, true)
                ));
            }

            if (empty($couponableLines)) {
                continue;
            }

            $couponableSubtotal = array_reduce(
                $couponableLines,
                fn ($carry, $line) => $carry + (int) ($line['line_total_cents'] ?? 0),
                0
            );

            if ($couponableSubtotal <= 0) {
                continue;
            }

            $evaluation = $this->couponEligibility->evaluate(
                $code,
                $couponableLines,
                $couponableSubtotal,
                null,
                ['enforce_per_user' => false],
            );

            if ($evaluation->isValid()) {
                if ($couponCode === null) {
                    $couponCode = $code;
                    $couponSummary = $evaluation->summary;
                }

                $couponDiscount += $evaluation->discountCents;
                $activeCoupons[] = [
                    'code' => $code,
                    ...(isset($lockedLineIds) ? ['locked_line_ids' => $lockedLineIds] : []),
                ];

                // Maintain current single-coupon behaviour for now.
                break;
            }
        }

        if (!empty($couponsMeta) && empty($activeCoupons)) {
            $meta['coupons'] = [];
            $this->saveMeta($sessionId, $meta);
            $couponCode = null;
        } elseif ($couponDiscount > 0 && $activeCoupons !== $couponsMeta) {
            $meta['coupons'] = $activeCoupons;
            $this->saveMeta($sessionId, $meta);
        }

        $taxableBase = max(0, $subtotal - $couponDiscount);
        $taxCents = (int) round($taxableBase * max($this->taxRate, 0) / 100);
        $total = max(0, $taxableBase + $taxCents);

        return [
            'lines'                   => $lines,
            'subtotal_cents'          => $subtotal,
            'savings_cents'           => $savings,
            'coupon_code'             => $couponCode,
            'coupon_discount_cents'   => $couponDiscount,
            ...(isset($couponSummary) ? ['coupon_summary' => $couponSummary] : []),
            'tax_cents'               => $taxCents,
            'total_cents'             => $total,
            'currency'                => $this->currency,
        ];
    }

    /**
     * Clear cart (helper).
     */
    public function clear(string $sessionId): void
    {
        Redis::del($this->key($sessionId));
        Redis::del($this->metaKey($sessionId));
    }

    /**
     * NEW: Merge carts (guest -> current session).
     * Useful when login arrives (or to merge multiple device sessions).
     */
    public function mergeSessions(string $fromSessionId, string $toSessionId): void
    {
        if ($fromSessionId === $toSessionId) {
            return;
        }

        $from = $this->loadRaw($fromSessionId);
        if (empty($from)) {
            return;
        }

        $to = $this->loadRaw($toSessionId);

        foreach ($from as $lineId => $line) {
            $vid = (int)($line['variant_id'] ?? 0);
            $qty = (int)($line['qty'] ?? 0);
            if ($vid <= 0 || $qty <= 0) continue;

            $toLineId = $this->lineId($vid);
            $to[$toLineId] = [
                'variant_id' => $vid,
                'qty'        => min($this->maxQty, ($to[$toLineId]['qty'] ?? 0) + $qty),
            ];
        }

        $this->saveRaw($toSessionId, $to);
        Redis::del($this->key($fromSessionId));

        $fromMeta = $this->normalizeCouponMeta($fromSessionId, $this->loadMeta($fromSessionId));
        $toMeta = $this->normalizeCouponMeta($toSessionId, $this->loadMeta($toSessionId));
        if (!empty($fromMeta) && empty($toMeta)) {
            $this->saveMeta($toSessionId, $fromMeta);
        }
        if (!empty($fromMeta)) {
            Redis::del($this->metaKey($fromSessionId));
        }
    }

    /**
     * ===== Internals (unchanged behavior) =====
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
        // Normalize: strip invalid entries, clamp qty to [1, maxQty]
        $normalized = [];
        foreach ($raw as $lineId => $line) {
            $vid = (int)($line['variant_id'] ?? 0);
            $qty = (int)($line['qty'] ?? 0);
            if ($vid > 0 && $qty > 0) {
                $normalized[(string)$lineId] = [
                    'variant_id' => $vid,
                    'qty'        => min($this->maxQty, max(1, $qty)),
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

    /**
     * Apply a coupon code to the cart meta for this session.
     * Returns the updated computed cart.
     */
    public function applyCoupon(string $sessionId, string $code, ?int $userId = null): array
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            throw ValidationException::withMessages(['code' => 'Coupon code is required.']);
        }

        $cart = $this->get($sessionId);
        $evaluation = $this->couponEligibility->evaluate(
            $code,
            $cart['lines'] ?? [],
            (int) ($cart['subtotal_cents'] ?? 0),
            $userId,
            ['enforce_per_user' => true],
        );

        if (! $evaluation->isValid()) {
            $message = $evaluation->error ?? 'Coupon not currently valid.';
            throw ValidationException::withMessages(['code' => $message]);
        }

        $lockedLineIds = [];
        $couponRow = $evaluation->coupon;
        $excludeSaleItems = is_object($couponRow) && !empty($couponRow->exclude_sale_items);

        foreach (($cart['lines'] ?? []) as $line) {
            $productId = $line['product']['id'] ?? null;
            if ($productId === null) {
                continue;
            }
            if (!in_array((int) $productId, $evaluation->eligibleProductIds, true)) {
                continue;
            }

            if ($excludeSaleItems) {
                $compare = $line['compare_at_cents'] ?? null;
                $price = $line['price_cents'] ?? null;
                if ($compare !== null && $price !== null && (int) $compare > (int) $price) {
                    continue;
                }
            }

            $lockedLineIds[] = (string) ($line['line_id'] ?? $line['variant_id'] ?? '');
        }

        $lockedLineIds = array_values(array_filter($lockedLineIds, fn ($id) => $id !== ''));

        $meta = $this->normalizeCouponMeta($sessionId, $this->loadMeta($sessionId));
        $entry = ['code' => $code];
        if (!empty($lockedLineIds)) {
            $entry['locked_line_ids'] = $lockedLineIds;
        }

        // For now we only keep a single coupon entry, but the structure supports more.
        $meta['coupons'] = [$entry];

        $this->saveMeta($sessionId, $meta);

        return $this->get($sessionId);
    }

    /**
     * Remove any applied coupon from the cart.
     */
    public function removeCoupon(string $sessionId): array
    {
        $meta = $this->normalizeCouponMeta($sessionId, $this->loadMeta($sessionId));
        if (!empty($meta['coupons'])) {
            $meta['coupons'] = [];
            $this->saveMeta($sessionId, $meta);
        }

        return $this->get($sessionId);
    }

    /**
     * Return applied coupon info (code and computed discount) for this session.
     */
    public function getAppliedCoupon(string $sessionId): array
    {
        $cart = $this->get($sessionId);
        return [
            'code' => $cart['coupon_code'] ?? null,
            'discount_cents' => (int)($cart['coupon_discount_cents'] ?? 0),
        ];
    }

    /**
     * Preview a coupon against the current cart (or supplied lines) without mutating state.
     */
    public function previewCoupon(string $sessionId, string $code, ?array $lines = null, ?int $userId = null): array
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return [
                'valid' => false,
                'error' => 'Coupon code is required.',
                'discount_cents' => 0,
                'summary' => null,
                'eligible_product_ids' => [],
            ];
        }

        if ($lines === null) {
            $cart = $this->get($sessionId);
            $candidateLines = $cart['lines'] ?? [];
        } else {
            $candidateLines = $this->normalizePreviewLines($lines);
        }

        if (empty($candidateLines)) {
            return [
                'valid' => false,
                'error' => 'Cart is empty.',
                'discount_cents' => 0,
                'summary' => null,
                'eligible_product_ids' => [],
            ];
        }

        $subtotal = array_reduce(
            $candidateLines,
            fn ($carry, $line) => $carry + (int) ($line['line_total_cents'] ?? 0),
            0
        );

        $evaluation = $this->couponEligibility->evaluate(
            $code,
            $candidateLines,
            $subtotal,
            $userId,
            ['enforce_per_user' => true],
        );

        return [
            'valid' => $evaluation->isValid(),
            'error' => $evaluation->error,
            'discount_cents' => $evaluation->isValid() ? $evaluation->discountCents : 0,
            'summary' => $evaluation->summary,
            'eligible_product_ids' => $evaluation->eligibleProductIds,
        ];
    }

    private function loadMeta(string $sessionId): array
    {
        $val = Redis::get($this->metaKey($sessionId));
        if (!$val) return [];
        $arr = json_decode($val, true);
        return is_array($arr) ? $arr : [];
    }

    private function saveMeta(string $sessionId, array $meta): void
    {
        if (empty($meta)) {
            Redis::del($this->metaKey($sessionId));
            return;
        }
        Redis::setex($this->metaKey($sessionId), $this->ttl, json_encode($meta));
    }

    private function normalizeCouponMeta(string $sessionId, array $meta): array
    {
        if (isset($meta['coupon_code'])) {
            $entry = ['code' => strtoupper((string) $meta['coupon_code'])];
            if (!empty($meta['coupon_locked_lines']) && is_array($meta['coupon_locked_lines'])) {
                $entry['locked_line_ids'] = array_values(array_map('strval', $meta['coupon_locked_lines']));
            }
            $meta['coupons'] = [$entry];
            unset($meta['coupon_code'], $meta['coupon_locked_lines']);
            $this->saveMeta($sessionId, $meta);
        }

        return $meta;
    }

    private function normalizePreviewLines(array $lines): array
    {
        $normalized = [];
        foreach ($lines as $index => $line) {
            $productId = (int) ($line['product_id'] ?? 0);
            $qty = max(1, (int) ($line['qty'] ?? 0));
            $price = (int) ($line['price_cents'] ?? 0);
            $compare = isset($line['compare_at_cents']) ? (int) $line['compare_at_cents'] : null;
            if ($productId <= 0 || $price < 0 || $qty <= 0) {
                continue;
            }

            $normalized[] = [
                'line_id' => (string) ($line['line_id'] ?? ('preview-' . $index)),
                'variant_id' => $line['variant_id'] ?? null,
                'sku' => $line['sku'] ?? null,
                'product' => [
                    'id' => $productId,
                    'name' => (string) ($line['name'] ?? ''),
                    'slug' => (string) ($line['slug'] ?? ''),
                ],
                'price_cents' => $price,
                'compare_at_cents' => $compare,
                'qty' => $qty,
                'line_total_cents' => $price * $qty,
            ];
        }

        return $normalized;
    }
}
