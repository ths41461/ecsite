<?php

use Laravel\Dusk\Browser;

test('basic page loads', function () {
    $this->browse(function (Browser $browser) {
        $html = $browser->visit('/')
            ->driver->getPageSource();

        // Save HTML to a file for debugging
        file_put_contents(storage_path('logs/dusk-debug.html'), $html);

        expect($html)->toContain('html');
    });
});
