<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProgrmVendorConfiguration>
 */
class ProgramVendorConfigurationFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'payment_account_number' => rand(1000000, 99999999),
      'sanctioned_limit' => 1500000,
      'limit_approved_date' => now()->format('Y-m-d'),
      'limit_expiry_date' => now()->addMonths(3)->format('Y-m-d'),
      'limit_review_date' => now()->addMonths(2)->format('Y-m-d'),
      'drawing_power' => 1232312,
      'request_auto_finance' => false,
      'auto_approve_finance' => false,
      'eligibility' => 12,
      'invoice_margin' => 15,
      'schema_code' => 'PRO-'.time(),
      'product_description' => 'Seeded test description',
      'vendor_code' => 'PRO-V-'.time(),
      'gst_number' => 23432342,
      'classification' => 'active',
    ];
  }
}
