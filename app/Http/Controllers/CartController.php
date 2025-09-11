<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Http\Requests\StoreCartRequest;
use App\Http\Requests\UpdateCartRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CartController extends Controller
{
    public function __construct(private CartService $cart)
    {
        //
    }

    /**
     * GET /cart
     * Returns the computed cart as JSON (UI comes in 4.3.2).
     */
    public function index(Request $request)
    {
        $sessionId = $request->session()->getId();
        $cart = $this->cart->get($sessionId);

        if ($request->wantsJson()) {
            return response()->json($cart);
        }

        return Inertia::render('Cart/Index', [
            'initialCart' => $cart,
        ]);
    }


    /**
     * POST /cart
     * Body: { variant_id:int, qty:int }
     * Adds to cart and returns the new computed cart.
     */
    public function store(StoreCartRequest $request)
    {
        $sessionId = $request->session()->getId();
        $cart = $this->cart->add(
            $sessionId,
            $request->integer('variant_id'),
            $request->integer('qty')
        );

        return response()->json($cart);
    }

    /**
     * PATCH /cart/{line}
     * Body: { qty:int } (0 removes)
     * Updates a line quantity and returns the new computed cart.
     */
    public function update(UpdateCartRequest $request, string $line)
    {
        $sessionId = $request->session()->getId();

        $cart = $this->cart->update(
            $sessionId,
            $line,
            $request->integer('qty')
        );

        return response()->json($cart);
    }

    /**
     * DELETE /cart/{line}
     * Removes the line and returns the new computed cart.
     */
    public function destroy(Request $request, string $line)
    {
        $sessionId = $request->session()->getId();
        $cart = $this->cart->remove($sessionId, $line);

        return response()->json($cart);
    }
}
