<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Wishlist;
use App\Models\Review;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the user dashboard with all account information.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Get user's account data
        $profileData = [
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
        ];
        
        // Get user's orders with items
        $orders = $user->orders()
            ->with(['items.product', 'payments', 'shipments'])
            ->orderBy('created_at', 'desc')  // This should use the standard Laravel timestamp
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'total_yen' => $order->total_yen,
                    'status' => $order->status,
                    'created_at' => $order->created_at ? (is_string($order->created_at) ? $order->created_at : $order->created_at->format('Y-m-d H:i:s')) : null,
                    'items_count' => $order->items->count(),
                    'items' => $order->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product_name' => $item->product->name,
                            'quantity' => $item->quantity,
                            'price_yen' => $item->price_yen,
                        ];
                    }),
                ];
            });
        
        // Get user's addresses
        $addresses = $user->addresses()
            ->get()
            ->map(function ($address) {
                return [
                    'id' => $address->id,
                    'name' => $address->name,
                    'phone' => $address->phone,
                    'address_line1' => $address->address_line1,
                    'address_line2' => $address->address_line2,
                    'city' => $address->city,
                    'state' => $address->state,
                    'zip' => $address->zip,
                    'country' => $address->country,
                    'is_default' => $address->is_default,
                    'created_at' => $address->created_at ? (is_string($address->created_at) ? $address->created_at : $address->created_at->format('Y-m-d H:i:s')) : null,
                ];
            });
        
        // Get user's wishlist items
        $wishlistItems = $user->wishlist()
            ->with('product')
            ->get()
            ->map(function ($wishlistItem) {
                return [
                    'id' => $wishlistItem->id,
                    'product_id' => $wishlistItem->product_id,
                    'product_name' => $wishlistItem->product->name,
                    'product_price' => $wishlistItem->product->price_yen ?? 0,
                    'product_image' => optional($wishlistItem->product->images()->first())->path,
                    'created_at' => $wishlistItem->created_at ? (is_string($wishlistItem->created_at) ? $wishlistItem->created_at : $wishlistItem->created_at->format('Y-m-d H:i:s')) : null,
                ];
            });
        
        // Get user's reviews
        $reviews = $user->reviews()
            ->with('product')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'product_id' => $review->product_id,
                    'product_name' => $review->product->name,
                    'rating' => $review->rating,
                    'body' => $review->body,
                    'approved' => $review->approved,
                    'created_at' => $review->created_at ? (is_string($review->created_at) ? $review->created_at : $review->created_at->format('Y-m-d H:i:s')) : null,
                ];
            });

        return Inertia::render('dashboard', [
            'profile' => $profileData,
            'orders' => $orders,
            'addresses' => $addresses,
            'wishlistItems' => $wishlistItems,
            'reviews' => $reviews,
        ]);
    }
}
