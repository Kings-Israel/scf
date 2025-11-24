<?php

namespace Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_number' => rand(1000000, 9000000),
            'payment_od_account' => time().'-'.rand(10000, 50000),
            'invoice_date' => Carbon::now()->format('Y-m-d'),
            'due_date' => Carbon::now()->addMonths(2)->format('Y-m-d'),
            'currency' => 'KSH',
        ];
    }
}
