<?php

use Inertia\Testing\AssertableInertia as Assert;

test('quiz page returns 200 status', function () {
    $response = $this->get('/fragrance-diagnosis');

    $response->assertStatus(200);
});

test('quiz page returns inertia component', function () {
    $response = $this->get('/fragrance-diagnosis');

    $response->assertInertia(fn (Assert $page) => $page
        ->component('FragranceDiagnosis')
    );
});

test('quiz page loads the diagnosis page', function () {
    $response = $this->get('/fragrance-diagnosis');

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('FragranceDiagnosis')
    );
});
