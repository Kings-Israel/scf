<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProgrmVendorBankDetail>
 */
class ProgramVendorBankDetailFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'name_as_per_bank' => fake()->name(),
      'account_number' => rand(1000, 9999).'-'.rand(10000, 99999),
      'bank_name' => 'KCB',
      'branch' => 'KCB Branch One',
      'swift_code' => rand(10000000, 99999999),
      'account_type' => 'Personal Account',
    ];
  }
}
