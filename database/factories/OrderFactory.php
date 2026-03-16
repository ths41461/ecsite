<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = \App\Models\Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(1000, 20000);
        $tax = (int) round($subtotal * 0.1);
        $shipping = fake()->randomElement([0, 500, 800]);
        $discount = fake()->randomElement([0, 200, 500]);
        $total = $subtotal + $tax + $shipping - $discount;

        return [
            'order_number'   => fake()->unique()->bothify(strtoupper(Str::random(8)).'################'), // 24 chars
            'user_id'        => null,
            'email'          => fake()->safeEmail(),
            'name'           => fake()->name(),
            'phone'          => fake()->optional()->phoneNumber(),
            'address_line1'  => fake()->streetAddress(),
            'address_line2'  => fake()->optional()->secondaryAddress(),
            'city'           => fake()->optional()->city(),
            'state'          => fake()->optional()->state(),
            'zip'            => fake()->optional()->postcode(),
            'subtotal_yen'   => $subtotal,
            'tax_yen'        => $tax,
            'shipping_yen'   => $shipping,
            'discount_yen'   => $discount,
            'total_yen'      => $total,
            'payment_mode'   => 'mock',
            'status'         => 'ordered',
            'ordered_at'     => now(),
            'shipped_at'     => null,
            'delivered_at'   => null,
            'canceled_at'    => null,
            // set in configure() once we know lookup ids
            'order_status_id'=> null,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (\App\Models\Order $order) {
            if (is_null($order->order_status_id)) {
                $pendingId = (int) DB::table('order_statuses')->where('code', 'pending')->value('id');
                if ($pendingId) {
                    $order->order_status_id = $pendingId;
                    $order->save();
                }
            }
        });
    }
}
