<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
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
                    'product_ids' => $coupon->products->pluck('id')->values(),
                    'product_names' => $coupon->products->pluck('name')->values(),
                ];
            });

        return Inertia::render('settings/coupons', [
            'coupons' => $coupons,
            'productOptions' => Product::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->map(fn ($product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $productIds = $data['product_ids'] ?? [];
        unset($data['product_ids']);
        $coupon = Coupon::create($data + ['used_count' => 0]);
        $coupon->products()->sync($productIds);

        return back()->with('success', 'クーポンを作成しました。');
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $data = $this->validated($request, $coupon->id);
        $productIds = $data['product_ids'] ?? [];
        unset($data['product_ids']);
        $coupon->update($data);
        $coupon->products()->sync($productIds);

        return back()->with('success', 'クーポンを更新しました。');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        DB::transaction(function () use ($coupon) {
            DB::table('coupon_redemptions')->where('coupon_id', $coupon->id)->delete();
            DB::table('coupon_products')->where('coupon_id', $coupon->id)->delete();
            DB::table('coupon_categories')->where('coupon_id', $coupon->id)->delete();
            $coupon->products()->detach();
            $coupon->delete();
        });

        return back()->with('success', 'クーポンを削除しました。');
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
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);
    }
}
