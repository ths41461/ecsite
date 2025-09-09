<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function pdpView(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'min:1'],
            'sku' => ['nullable', 'string', 'max:64'],
        ]);

        $userId = optional($request->user())->id;
        $anon = $userId ? null : (string) Str::uuid();

        DB::table('events')->insert([
            'product_id' => $data['product_id'],
            'user_id' => $userId,
            'user_hash' => $anon,
            'event_type' => 'view_pdp',
            'value' => null,
            'occurred_at' => now(),
            'meta_json' => json_encode([
                'sku' => $data['sku'] ?? null,
                'ua' => substr($request->userAgent() ?? '', 0, 200),
                'ip' => $request->ip(),
                'referer' => $request->headers->get('referer'),
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->noContent();
    }

    public function addToCart(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'min:1'],
            'variant_id' => ['nullable', 'integer', 'min:1'],
            'sku' => ['required', 'string', 'max:64'],
            'qty' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $userId = optional($request->user())->id;
        $anon = $userId ? null : (string) Str::uuid();

        DB::table('events')->insert([
            'product_id' => $data['product_id'],
            'user_id' => $userId,
            'user_hash' => $anon,
            'event_type' => 'add_to_cart',
            'value' => $data['qty'],
            'occurred_at' => now(),
            'meta_json' => json_encode([
                'sku' => $data['sku'],
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->noContent();
    }

    public function wishlistAdd(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'sku' => ['nullable', 'string', 'max:64'],
        ]);

        $userId = optional($request->user())->id;
        $anon = $userId ? null : (string) Str::uuid();

        DB::table('events')->insert([
            'product_id' => $data['product_id'],
            'user_id' => $userId,
            'user_hash' => $anon,
            'event_type' => 'wishlist_add',
            'value' => null,
            'occurred_at' => now(),
            'meta_json' => json_encode([
                'sku' => $data['sku'] ?? null,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->noContent();
    }
}
