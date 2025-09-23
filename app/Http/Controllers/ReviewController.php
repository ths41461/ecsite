<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Product $product)
    {
        $reviews = $product->reviews()
            ->where('approved', true)
            ->with('user:id,name')
            ->latest()
            ->paginate(10);

        return response()->json($reviews);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Product $product)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'body' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create review (no user authentication required)
        $review = new Review();
        $review->product_id = $product->id;
        $review->rating = $request->rating;
        $review->body = $request->body;
        $review->approved = true; // Approve immediately for display
        
        // Associate with user if authenticated
        if (Auth::check()) {
            $review->user_id = Auth::id();
        }
        
        $review->save();

        return response()->json($review, 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Review $review)
    {
        // Check if user is authorized to update this review
        if (!Auth::check() || Auth::id() !== $review->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'body' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update review
        $review->rating = $request->rating;
        $review->body = $request->body;
        // Keep the existing approval status when updating
        $review->save();

        return response()->json($review);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Review $review)
    {
        // Check if user is authorized to delete this review
        if (!Auth::check() || (Auth::id() !== $review->user_id && !Auth::user()->is_admin)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $review->delete();

        return response()->json(['message' => 'Review deleted successfully']);
    }
}
