<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProgrmVendorDiscount>
 */
class ProgramVendorDiscountFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'benchmark_title' => 'Benchmark Rate 1',
      'benchmark_rate' => 3,
      'reset_frequency' => 'Monthly',
      'days_frequency_days' => 30,
      'business_strategy_spread' => 6,
      'credit_spread' => 4,
      'total_spread' => 10,
      'total_roi' => 15,
      'anchor_discount_bearing' => 0,
      'vendor_discount_bearing' => 10,
      'penal_discount_on_principle' => 4,
      'grace_period' => 3,
      'grace_period_discount' => 2,
      'maturity_handling_on_holidays' => 'No Effect',
    ];
  }
}
