<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CouponEligibilityService
{
    /**
     * Evaluate a coupon code against the current cart context.
     *
     * @param  string      $code            The raw coupon code submitted by the shopper (case-insensitive).
     * @param  array       $lines           Cart lines with product info (expects product.id, line_total_cents, price_cents, compare_at_cents).
     * @param  int         $subtotalCents   Cart subtotal in cents (before coupon discount).
     * @param  int|null    $userId          Authenticated user id for per-user limits (optional).
     * @param  array       $options         Supported: ['enforce_per_user' => bool].
     */
    public function evaluate(string $code, array $lines, int $subtotalCents, ?int $userId = null, array $options = []): CouponEvaluationResult
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return CouponEvaluationResult::invalid('クーポンコードは必須です。');
        }

        $coupon = DB::table('coupons')->whereRaw('UPPER(code) = ?', [$code])->first();
        if (!$coupon || (int) ($coupon->is_active ?? 0) !== 1) {
            return CouponEvaluationResult::invalid('クーポンが見つからないか、無効です。');
        }

        $now = now();
        $startsAt = $this->asCarbon($coupon->starts_at ?? null);
        if ($startsAt && $now->lt($startsAt)) {
            return CouponEvaluationResult::invalid('クーポンは現在有効ではありません。');
        }

        $endsAt = $this->asCarbon($coupon->ends_at ?? null);
        if ($endsAt && $now->gt($endsAt)) {
            return CouponEvaluationResult::invalid('クーポンは現在有効ではありません。');
        }

        if (!is_null($coupon->max_uses) && (int) $coupon->max_uses >= 0) {
            $max = (int) $coupon->max_uses;
            $used = (int) ($coupon->used_count ?? 0);
            if ($max > 0 && $used >= $max) {
                return CouponEvaluationResult::invalid('クーポンの利用上限に達しました。');
            }
        }

        $enforcePerUser = (bool) ($options['enforce_per_user'] ?? false);
        if ($enforcePerUser && $userId && !is_null($coupon->max_uses_per_user)) {
            $perUserCap = (int) $coupon->max_uses_per_user;
            if ($perUserCap >= 0) {
                $userCount = DB::table('coupon_redemptions')
                    ->where('coupon_id', $coupon->id)
                    ->where('user_id', $userId)
                    ->count();
                if ($userCount >= $perUserCap) {
                    return CouponEvaluationResult::invalid('このクーポンはすでに最大利用回数を使用しています。');
                }
            }
        }

        if (!in_array($coupon->type, ['percent', 'fixed'], true)) {
            return CouponEvaluationResult::invalid('クーポンの設定が無効です。');
        }

        $requiredSubtotal = is_null($coupon->min_subtotal_yen) ? null : max(0, (int) $coupon->min_subtotal_yen) * 100;
        if (!is_null($requiredSubtotal) && $subtotalCents < $requiredSubtotal) {
            $minYen = number_format((int) $coupon->min_subtotal_yen);
            return CouponEvaluationResult::invalid("このクーポンを利用するには、小計が少なくとも¥{$minYen}必要です。");
        }

        $productIds = array_map(function ($line) {
            return isset($line['product']['id']) ? (int) $line['product']['id'] : null;
        }, $lines);
        $productIds = array_values(array_filter($productIds, fn ($id) => !is_null($id)));

        $includeProductIds = DB::table('coupon_products')
            ->where('coupon_id', $coupon->id)
            ->pluck('product_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        $includeCategoryIds = DB::table('coupon_categories')
            ->where('coupon_id', $coupon->id)
            ->pluck('category_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        $eligibleIds = $includeProductIds;
        if (!empty($includeCategoryIds) && !empty($productIds)) {
            $direct = DB::table('products')
                ->whereIn('id', $productIds)
                ->whereIn('category_id', $includeCategoryIds)
                ->pluck('id')
                ->map(fn ($v) => (int) $v)
                ->all();

            $viaPivot = DB::table('category_product')
                ->whereIn('category_id', $includeCategoryIds)
                ->whereIn('product_id', $productIds)
                ->pluck('product_id')
                ->map(fn ($v) => (int) $v)
                ->all();

            $eligibleIds = array_merge($eligibleIds, $direct, $viaPivot);
        }

        $hasRestrictions = !empty($includeProductIds) || !empty($includeCategoryIds);

        if (!$hasRestrictions) {
            $eligibleIds = $productIds;
        }

        $eligibleIds = array_values(array_unique(array_filter($eligibleIds)));

        $eligibleSubtotal = 0;
        foreach ($lines as $line) {
            $productId = isset($line['product']['id']) ? (int) $line['product']['id'] : null;
            if (!$productId || !in_array($productId, $eligibleIds, true)) {
                continue;
            }

            if (!empty($coupon->exclude_sale_items)) {
                $compareAt = isset($line['compare_at_cents']) ? (int) $line['compare_at_cents'] : null;
                $price = isset($line['price_cents']) ? (int) $line['price_cents'] : null;
                if (!is_null($compareAt) && !is_null($price) && $compareAt > $price) {
                    continue; // on sale, excluded
                }
            }

            $eligibleSubtotal += (int) ($line['line_total_cents'] ?? 0);
        }

        if ($eligibleSubtotal <= 0) {
            return CouponEvaluationResult::invalid('このクーポンはカート内の商品に適用できません。');
        }

        $discountCents = 0;
        if ($coupon->type === 'percent') {
            $percent = max(0, (int) $coupon->value);
            $discountCents = (int) floor($eligibleSubtotal * $percent / 100);
            if (!is_null($coupon->max_discount_yen) && (int) $coupon->max_discount_yen > 0) {
                $discountCents = min($discountCents, (int) $coupon->max_discount_yen * 100);
            }
        } else {
            $discountCents = min(max(0, (int) $coupon->value) * 100, $eligibleSubtotal);
        }

        if ($discountCents <= 0) {
            return CouponEvaluationResult::invalid('クーポンの設定が無効です。');
        }

        $summary = $this->buildSummary($coupon);

        return CouponEvaluationResult::valid($coupon, $discountCents, $summary, $eligibleIds, !$hasRestrictions);
    }

    private function asCarbon($value): ?CarbonInterface
    {
        if (empty($value)) {
            return null;
        }

        return $value instanceof CarbonInterface
            ? $value
            : Carbon::parse($value);
    }

    private function buildSummary(object $coupon): string
    {
        if ($coupon->type === 'percent') {
            $summary = sprintf('%d%% off', (int) $coupon->value);
            if (!is_null($coupon->max_discount_yen) && (int) $coupon->max_discount_yen > 0) {
                $summary .= sprintf(' (max ¥%s)', number_format((int) $coupon->max_discount_yen));
            }
        } else {
            $summary = '-¥' . number_format((int) $coupon->value);
        }

        if (!empty($coupon->exclude_sale_items)) {
            $summary .= '; excludes sale items';
        }

        if (!is_null($coupon->min_subtotal_yen) && (int) $coupon->min_subtotal_yen > 0) {
            $summary .= sprintf('; min %s¥ subtotal', number_format((int) $coupon->min_subtotal_yen));
        }

        return $summary;
    }
}
