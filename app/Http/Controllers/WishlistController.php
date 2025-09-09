<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class WishlistController extends Controller
{
    private const KEY = 'wishlist_product_ids';

    public function index(Request $request)
    {
        /** @var array<int,bool> */
        $ids = $request->session()->get(self::KEY, []);
        // Optional: load product cards by $ids for a full wishlist page.
        return Inertia::render('Wishlist/Index', [
            'product_ids' => array_keys($ids),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'min:1'],
        ]);

        /** @var array<int,bool> */
        $ids = $request->session()->get(self::KEY, []);
        $ids[$validated['product_id']] = true;
        $request->session()->put(self::KEY, $ids);

        return response()->noContent();
    }

    public function destroy(Request $request, int $product)
    {
        /** @var array<int,bool> */
        $ids = $request->session()->get(self::KEY, []);
        unset($ids[$product]);
        $request->session()->put(self::KEY, $ids);

        return response()->noContent();
    }
}
