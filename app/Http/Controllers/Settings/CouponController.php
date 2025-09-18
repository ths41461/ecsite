<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CouponController extends Controller
{
    public function index(Request $request): Response
    {
        $coupons = Coupon::query()
            ->with(['products:id,name'])
            ->orderByDesc('id')
            ->paginate(15)
            ->through(function (Coupon $coupon) {
                return [
                    'id' => $coupon->id,
                    'code' => $coupon->code,
                    'description' => $coupon->description,
                    'type' => $coupon->type,
                    'value' => $coupon->value,
                    'is_active' => (bool) $coupon->is_active,
                    'starts_at' => optional($coupon->starts_at)->toDateTimeString(),
                    'ends_at' => optional($coupon->ends_at)->toDateTimeString(),
                    'max_uses' => $coupon->max_uses,
                    'used_count' => $coupon->used_count,
                    'max_uses_per_user' => $coupon->max_uses_per_user,
                    'min_subtotal_yen' => $coupon->min_subtotal_yen,
                    'max_discount_yen' => $coupon->max_discount_yen,
                    'exclude_sale_items' => (bool) $coupon->exclude_sale_items,
                    'product_ids' => $coupon->products->pluck('id'),
                    'product_names' => $coupon->products->pluck('name'),
                ];
            });

        return Inertia::render('settings/coupons', [
            'coupons' => $coupons,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $productIds = $this->parseProductIds($data['product_ids'] ?? null);
        unset($data['product_ids']);
        $coupon = Coupon::create($data + ['used_count' => 0]);
        $coupon->products()->sync($productIds);

        return back()->with('success', 'Coupon created.');
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $data = $this->validated($request, $coupon->id);
        $productIds = $this->parseProductIds($data['product_ids'] ?? null);
        unset($data['product_ids']);
        $coupon->update($data);
        $coupon->products()->sync($productIds);

        return back()->with('success', 'Coupon updated.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon->products()->detach();
        $coupon->delete();

        return back()->with('success', 'Coupon removed.');
    }

    private function validated(Request $request, ?int $couponId = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:40', Rule::unique('coupons', 'code')->ignore($couponId)],
            'description' => ['nullable', 'string', 'max:160'],
            'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => ['required', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'max_uses' => ['nullable', 'integer', 'min:0'],
            'max_uses_per_user' => ['nullable', 'integer', 'min:0'],
            'min_subtotal_yen' => ['nullable', 'integer', 'min:0'],
            'max_discount_yen' => ['nullable', 'integer', 'min:0'],
            'exclude_sale_items' => ['boolean'],
            'is_active' => ['boolean'],
            'product_ids' => ['nullable', 'string'],
        ]);
    }

    private function parseProductIds(?string $raw): array
    {
        if (!$raw) {
            return [];
        }

        return collect(explode(',', $raw))
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
