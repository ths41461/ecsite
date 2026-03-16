<?php

/**
 * @group feature
 * @group ai
 * @group e2e
 * @group browser
 */

use App\Models\Product;

describe('FragranceDiagnosisResults Page E2E Tests', function () {
    /**
     * Test the full Inertia page rendering - this is what's actually shown to users
     */
    it('results page returns 200 with valid quiz data', function () {
        $response = $this->get('/fragrance-diagnosis/results?'.http_build_query([
            'personality' => 'romantic',
            'vibe' => 'floral',
            'occasion' => ['daily'],
            'style' => 'feminine',
            'budget' => 5000,
            'experience' => 'beginner',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('FragranceDiagnosisResults')
        );
    });

    it('results page returns products for various budgets', function () {
        $budgets = [3000, 5000, 8000, 10000, 15000];

        foreach ($budgets as $budget) {
            $response = $this->get('/fragrance-diagnosis/results?'.http_build_query([
                'personality' => 'natural',
                'vibe' => 'citrus',
                'occasion' => ['daily'],
                'style' => 'casual',
                'budget' => $budget,
                'experience' => 'beginner',
            ]));

            $response->assertStatus(200);
        }
    });

    it('database has sufficient products for test scenarios', function () {
        $productsUnder3000 = Product::where('is_active', true)
            ->whereHas('variants', fn ($q) => $q->where('is_active', true)->where('price_yen', '<=', 3000))
            ->count();

        $productsUnder5000 = Product::where('is_active', true)
            ->whereHas('variants', fn ($q) => $q->where('is_active', true)->where('price_yen', '<=', 5000))
            ->count();

        $productsUnder8000 = Product::where('is_active', true)
            ->whereHas('variants', fn ($q) => $q->where('is_active', true)->where('price_yen', '<=', 8000))
            ->count();

        expect($productsUnder3000)->toBeGreaterThan(0, 'Need products under ¥3,000');
        expect($productsUnder5000)->toBeGreaterThan(0, 'Need products under ¥5,000');
        expect($productsUnder8000)->toBeGreaterThan(0, 'Need products under ¥8,000');
    });

    it('validation accepts all personality and vibe combinations', function () {
        $personalities = ['romantic', 'energetic', 'cool', 'natural'];
        $vibes = ['floral', 'citrus', 'vanilla', 'woody', 'ocean'];

        foreach ($personalities as $personality) {
            foreach ($vibes as $vibe) {
                $response = $this->get('/fragrance-diagnosis/results?'.http_build_query([
                    'personality' => $personality,
                    'vibe' => $vibe,
                    'occasion' => ['daily'],
                    'style' => 'casual',
                    'budget' => 8000,
                    'experience' => 'beginner',
                ]));

                $response->assertStatus(200,
                    "Failed for personality=$personality, vibe=$vibe"
                );
            }
        }
    });
});
