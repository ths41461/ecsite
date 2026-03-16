<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Scout\Jobs\MakeSearchable;
use Laravel\Scout\Jobs\RemoveFromSearch;
use App\Models\Product;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate');
    // Ensure Scout dispatches jobs instead of doing work inline
    config(['scout.queue' => true]);
    Queue::fake();
});

it('queues MakeSearchable when a product is created or updated', function () {
    $p = Product::factory()->create(); // created => index
    Queue::assertPushed(MakeSearchable::class, function (MakeSearchable $job) use ($p) {
        return collect($job->models)->first()->id === $p->id;
    });

    Queue::fake(); // reset
    $p->update(['name' => $p->name . ' Updated']);
    Queue::assertPushed(MakeSearchable::class, function (MakeSearchable $job) use ($p) {
        return collect($job->models)->first()->id === $p->id;
    });
});

it('queues RemoveFromSearch when a product is deleted', function () {
    $p = Product::factory()->create();
    Queue::fake(); // reset
    $p->delete();
    Queue::assertPushed(RemoveFromSearch::class, function (RemoveFromSearch $job) use ($p) {
        return collect($job->models)->first()->id === $p->id;
    });
});
