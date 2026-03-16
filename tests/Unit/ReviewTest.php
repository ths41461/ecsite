<?php

use App\Models\Product;
use App\Models\User;
use App\Models\Review;

test('review model can be created', function () {
    $review = Review::factory()->create([
        'rating' => 5,
        'body' => 'This is an excellent product!',
        'approved' => true
    ]);
    
    expect($review)->toBeInstanceOf(Review::class);
    expect($review->rating)->toBe(5);
    expect($review->body)->toBe('This is an excellent product!');
    expect($review->approved)->toBeTrue();
});

test('product can have reviews', function () {
    $product = Product::factory()->create();
    
    // Create some reviews for the product
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
    
    // Test relationships
    expect($product->reviews)->toHaveCount(4);
    
    // Test average rating calculation
    expect($product->averageRating())->toBe(4.25);
    expect($product->reviewCount())->toBe(4);
});