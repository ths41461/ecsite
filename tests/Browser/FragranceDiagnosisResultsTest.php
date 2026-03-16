<?php

use Laravel\Dusk\Browser;

test('results page loads and shows content', function () {
    $this->browse(function (Browser $browser) {
        $quizData = [
            'personality' => 'romantic',
            'vibe' => 'floral',
            'occasion[0]' => 'daily',
            'style' => 'feminine',
            'budget' => 5000,
            'experience' => 'beginner',
            'season' => 'spring',
        ];

        $html = $browser->visit('/fragrance-diagnosis/results?'.http_build_query($quizData))
            ->driver->getPageSource();

        // Save HTML for debugging
        file_put_contents(storage_path('logs/dusk-results-debug.html'), $html);

        // Check if page loaded
        expect($html)->toContain('html');

        // Check for any errors
        if (str_contains($html, 'Validation') || str_contains($html, 'error')) {
            file_put_contents(storage_path('logs/dusk-results-error.txt'), $html);
        }
    });
});
