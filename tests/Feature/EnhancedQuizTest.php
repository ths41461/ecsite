<?php

use function Pest\Laravel\artisan;

beforeEach(function () {
    artisan('db:seed', ['--force' => true]);
});

describe('FragranceDiagnosis Quiz Enhanced', function () {
    test('quiz page loads successfully', function () {
        $response = $this->get('/fragrance-diagnosis');

        $response->assertStatus(200);
    });

    test('quiz page is an Inertia page', function () {
        $response = $this->get('/fragrance-diagnosis');

        $response->assertInertia(fn ($page) => $page
            ->component('FragranceDiagnosis')
        );
    });

    test('quiz page results show recommended products', function () {
        $quizData = [
            'personality' => 'romantic',
            'vibe' => 'floral',
            'occasion' => ['daily'],
            'style' => 'feminine',
            'budget' => 5000,
            'experience' => 'beginner',
            'season' => 'spring',
        ];

        $response = $this->get('/fragrance-diagnosis/results?'.http_build_query($quizData));

        $response->assertStatus(200);
        $props = $response->inertiaProps();

        expect($props['recommendations'])->not->toBeEmpty();
    });

    test('quiz page shows scent profile', function () {
        $quizData = [
            'personality' => 'romantic',
            'vibe' => 'floral',
            'occasion' => ['daily'],
            'style' => 'feminine',
            'budget' => 5000,
            'experience' => 'beginner',
            'season' => 'spring',
        ];

        $response = $this->get('/fragrance-diagnosis/results?'.http_build_query($quizData));

        $response->assertStatus(200);
        $props = $response->inertiaProps();

        expect($props['profile'])->not->toBeNull();
        expect($props['profile']['name'])->not->toBeEmpty();
    });

    test('quiz validates required parameters', function () {
        $response = $this->get('/fragrance-diagnosis/results');

        $response->assertStatus(302);
    });

    test('quiz validates personality parameter', function () {
        $quizData = [
            'personality' => 'invalid',
            'vibe' => 'floral',
            'occasion' => ['daily'],
            'style' => 'feminine',
            'budget' => 5000,
            'experience' => 'beginner',
            'season' => 'spring',
        ];

        $response = $this->get('/fragrance-diagnosis/results?'.http_build_query($quizData));

        $response->assertStatus(302);
    });

    test('quiz validates vibe parameter', function () {
        $quizData = [
            'personality' => 'romantic',
            'vibe' => 'invalid',
            'occasion' => ['daily'],
            'style' => 'feminine',
            'budget' => 5000,
            'experience' => 'beginner',
            'season' => 'spring',
        ];

        $response = $this->get('/fragrance-diagnosis/results?'.http_build_query($quizData));

        $response->assertStatus(302);
    });

    test('quiz accepts valid personality values', function () {
        $personalities = ['romantic', 'energetic', 'cool', 'natural'];

        foreach ($personalities as $personality) {
            $quizData = [
                'personality' => $personality,
                'vibe' => 'floral',
                'occasion' => ['daily'],
                'style' => 'feminine',
                'budget' => 5000,
                'experience' => 'beginner',
                'season' => 'spring',
            ];

            $response = $this->get('/fragrance-diagnosis/results?'.http_build_query($quizData));
            $response->assertStatus(200);
        }
    });

    test('quiz accepts valid vibe values', function () {
        $vibes = ['floral', 'citrus', 'vanilla', 'woody', 'ocean'];

        foreach ($vibes as $vibe) {
            $quizData = [
                'personality' => 'romantic',
                'vibe' => $vibe,
                'occasion' => ['daily'],
                'style' => 'feminine',
                'budget' => 5000,
                'experience' => 'beginner',
                'season' => 'spring',
            ];

            $response = $this->get('/fragrance-diagnosis/results?'.http_build_query($quizData));
            $response->assertStatus(200);
        }
    });

    test('quiz accepts valid occasion values', function () {
        $quizData = [
            'personality' => 'romantic',
            'vibe' => 'floral',
            'occasion' => ['daily', 'date', 'special', 'work', 'casual'],
            'style' => 'feminine',
            'budget' => 5000,
            'experience' => 'beginner',
            'season' => 'spring',
        ];

        $response = $this->get('/fragrance-diagnosis/results?'.http_build_query($quizData));
        $response->assertStatus(200);
    });

    test('quiz accepts valid style values', function () {
        $styles = ['feminine', 'casual', 'chic', 'natural'];

        foreach ($styles as $style) {
            $quizData = [
                'personality' => 'romantic',
                'vibe' => 'floral',
                'occasion' => ['daily'],
                'style' => $style,
                'budget' => 5000,
                'experience' => 'beginner',
                'season' => 'spring',
            ];

            $response = $this->get('/fragrance-diagnosis/results?'.http_build_query($quizData));
            $response->assertStatus(200);
        }
    });

    test('quiz accepts valid experience values', function () {
        $experiences = ['beginner', 'some', 'experienced'];

        foreach ($experiences as $experience) {
            $quizData = [
                'personality' => 'romantic',
                'vibe' => 'floral',
                'occasion' => ['daily'],
                'style' => 'feminine',
                'budget' => 5000,
                'experience' => $experience,
                'season' => 'spring',
            ];

            $response = $this->get('/fragrance-diagnosis/results?'.http_build_query($quizData));
            $response->assertStatus(200);
        }
    });

    test('quiz accepts valid season values', function () {
        $seasons = ['spring', 'fall', 'all'];

        foreach ($seasons as $season) {
            $quizData = [
                'personality' => 'romantic',
                'vibe' => 'floral',
                'occasion' => ['daily'],
                'style' => 'feminine',
                'budget' => 5000,
                'experience' => 'beginner',
                'season' => $season,
            ];

            $response = $this->get('/fragrance-diagnosis/results?'.http_build_query($quizData));
            $response->assertStatus(200);
        }
    });

    test('quiz results page has 7 questions worth of profile data', function () {
        $quizData = [
            'personality' => 'romantic',
            'vibe' => 'floral',
            'occasion' => ['daily', 'date'],
            'style' => 'feminine',
            'budget' => 5000,
            'experience' => 'beginner',
            'season' => 'spring',
        ];

        $response = $this->get('/fragrance-diagnosis/results?'.http_build_query($quizData));

        $response->assertStatus(200);
        $props = $response->inertiaProps();

        expect($props['quizData']['personality'])->toBe('romantic');
        expect($props['quizData']['vibe'])->toBe('floral');
        expect($props['quizData']['style'])->toBe('feminine');
        expect($props['quizData']['budget'])->toBe(5000);
        expect($props['quizData']['experience'])->toBe('beginner');
    });

    test('quiz creates unique session for each result', function () {
        $quizData = [
            'personality' => 'romantic',
            'vibe' => 'floral',
            'occasion' => ['daily'],
            'style' => 'feminine',
            'budget' => 5000,
            'experience' => 'beginner',
            'season' => 'spring',
        ];

        $response1 = $this->get('/fragrance-diagnosis/results?'.http_build_query($quizData));
        $props1 = $response1->inertiaProps();

        $response2 = $this->get('/fragrance-diagnosis/results?'.http_build_query($quizData));
        $props2 = $response2->inertiaProps();

        expect($props1['sessionId'])->not->toBe($props2['sessionId']);
    });

    test('quiz budget filter works in results', function () {
        $quizData = [
            'personality' => 'romantic',
            'vibe' => 'floral',
            'occasion' => ['daily'],
            'style' => 'feminine',
            'budget' => 5000,
            'experience' => 'beginner',
            'season' => 'spring',
        ];

        $response = $this->get('/fragrance-diagnosis/results?'.http_build_query($quizData));

        $response->assertStatus(200);
        $props = $response->inertiaProps();

        $allInBudget = collect($props['recommendations'])->every(fn ($rec) => $rec['price'] <= 8000);
        expect($allInBudget)->toBeTrue();
    });
});
