<?php

namespace Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'purchase_order_number' => mt_rand(111111111, 999999999),
            'currency' => 'KSH',
            'duration_from' => Carbon::now(),
            'duration_to' => Carbon::now()->addDays(rand(4, 10)),
            'delivery_date' => Carbon::now()->addDays(rand(11, 15)),
            'delivery_address' => 'Westland Office Park',
            'invoice_payment_terms' => rand(10, 20),
        ];
    }
}
