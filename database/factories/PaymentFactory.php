<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // keep your current fields (order_id, amount, etc.)
        return [
            // ...
            'payment_status_id' => null, // set in configure()
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (\App\Models\Payment $payment) {
            if (is_null($payment->payment_status_id)) {
                $pendingId = (int) DB::table('payment_statuses')->where('code', 'pending')->value('id');
                if ($pendingId) {
                    $payment->payment_status_id = $pendingId;
                    $payment->save();
                }
            }
        });
    }
}
