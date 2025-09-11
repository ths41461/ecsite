<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\WishlistService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class WishlistController extends Controller
{
    public function __construct(private WishlistService $wishlist) {}

    public function index(Request $request)
    {
        $sessionId = $request->session()->getId();
        $data = $this->wishlist->items($sessionId);

        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return Inertia::render('Wishlist/Index', $data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
        ]);

        $sessionId = $request->session()->getId();
        $this->wishlist->add($sessionId, (int)$validated['product_id']);

        // Frontend only checks res.ok; No body required.
        return response()->noContent(Response::HTTP_NO_CONTENT);
    }

    // Accept raw product id from URL instead of slug-bound Product model
    public function destroy(Request $request, $product)
    {
        $sessionId = $request->session()->getId();
        $productId = (int) $product;
        $this->wishlist->remove($sessionId, $productId);

        return response()->noContent(Response::HTTP_NO_CONTENT);
    }
}
