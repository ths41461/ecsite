<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Shipment>
 */
class ShipmentFactory extends Factory
{
    protected $model = \App\Models\Shipment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id'         => \App\Models\Order::factory(),
            'carrier'          => fake()->optional()->randomElement(['yamato','sagawa','jp_post','dhl','fedex']),
            'tracking_number'  => null, // keep nullable to avoid unique collisions by default
            'status'           => 'label',
            'shipped_at'       => null,
            'delivered_at'     => null,
            'timeline_json'    => null,
            'shipment_status_id' => null, // set in configure() using lookup
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (\App\Models\Shipment $shipment) {
            if (is_null($shipment->shipment_status_id)) {
                $pendingId = (int) DB::table('shipment_statuses')->where('code', 'pending')->value('id');
                if ($pendingId) {
                    $shipment->shipment_status_id = $pendingId;
                    $shipment->save();
                }
            }
        });
    }
}
