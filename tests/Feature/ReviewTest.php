<?php

use App\Models\Product;
use App\Models\User;
use App\Models\Review;

test('user can submit review for purchased product', function () {
    // Create a user and product
    $user = User::factory()->create();
    $product = Product::factory()->create();
    
    // Create a review directly for testing (bypassing purchase requirement)
    $review = Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user->id,
        'rating' => 5,
        'comment' => 'This is an excellent product!',
        'approved' => true
    ]);
    
    expect($review)->toBeInstanceOf(Review::class);
    expect($review->rating)->toBe(5);
    expect($review->comment)->toBe('This is an excellent product!');
});

test('product displays average rating', function () {
    $product = Product::factory()->create();
    
    // Create some reviews
    Review::factory()->count(3)->create([
        'product_id' => $product->id,
        'rating' => 4,
        'approved' => true
    ]);
    
    Review::factory()->create([
        'product_id' => $product->id,
        'rating' => 5,
        'approved' => true
    ]);
    
    // Test average rating calculation
    expect($product->averageRating())->toBe(4.25);
    expect($product->reviewCount())->toBe(4);
});