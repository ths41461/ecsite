<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Models\Shipment;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate');
    $this->seed(\Database\Seeders\LookupSeeder::class);
});

it('records a tracking event and advances shipment status', function () {
    $pendingId   = (int) DB::table('shipment_statuses')->where('code', 'pending')->value('id');
    $inTransitId = (int) DB::table('shipment_statuses')->where('code', 'in_transit')->value('id');

    /** @var Shipment $shipment */
    $shipment = Shipment::factory()->create(['shipment_status_id' => $pendingId]);

    $shipment->addTrack([
        'carrier'   => 'yamato',
        'track_no'  => 'ABC123',
        'status'    => 'in_transit',
        'event_time' => now(),
        'payload'   => ['ok' => true],
    ]);

    $shipment->refresh();
    expect($shipment->shipment_status_id)->toBe($inTransitId);

    $tracks = DB::table('shipment_tracks')->where('shipment_id', $shipment->id)->get();
    expect($tracks)->toHaveCount(1);
    expect($tracks[0]->carrier)->toBe('yamato');
    expect($tracks[0]->status)->toBe('in_transit');
});

it('does not blow up if no status mapping exists', function () {
    $pendingId = (int) DB::table('shipment_statuses')->where('code', 'pending')->value('id');
    $shipment  = Shipment::factory()->create(['shipment_status_id' => $pendingId]);

    // Unknown carrier status string
    $shipment->addTrack([
        'carrier' => 'unknown',
        'status'  => 'warehouse_scan', // not mapped
    ]);

    $shipment->refresh();
    expect($shipment->shipment_status_id)->toBe($pendingId);

    $tracks = DB::table('shipment_tracks')->where('shipment_id', $shipment->id)->count();
    expect($tracks)->toBe(1);
});
