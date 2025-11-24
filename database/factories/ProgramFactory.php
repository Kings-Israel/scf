<?php

namespace Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Program>
 */
class ProgramFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word().'-'.Carbon::now()->format('d-m-H-i-s'),
            'code' => mt_rand(33333, 99999),
            'eligibility' => mt_rand(50, 90),
            // 'invoice_margin' => fake(),
            'program_limit' => fake()->numberBetween(100000000, 9000000000),
            'approved_date' => Carbon::now()->format('Y-m-d'),
            'limit_expiry_date' => Carbon::now()->addMonths(rand(6, 8))->format('Y-m-d'),
            'max_limit_per_account' => rand(1000000, 30000000),
            // 'collection_account' => fake(),
            'stale_invoice_period' => 8,
            'min_financing_days' => 4,
            'max_financing_days' => 30,
            'segment' => 'LOC',
            'max_days_due_date_extension' => 4,
            'days_limit_for_due_date_change' => 10,
            'default_payment_terms' => 6,
            'repayment_appropriation' => 'FIFO',
            'recourse' => 'With Recourse',
            'due_date_calculated_from' => 'Disbursement Date',
            'noa' => 'One Time',
            'account_status' => 'Active',
            // 'board_resolution_attachment' => fake()->file(),
        ];
    }
}
