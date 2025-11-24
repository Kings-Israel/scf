<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BankDocument>
 */
class BankDocumentFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition()
  {
    $documents = [['name' => 'Business Registration', 'requires_expiry_date' => true], ['name' => 'KRA PIN', 'requires_expiry_date' => false], ['name' => 'Tax Compliance', 'requires_expiry_date' => true], ['name' => 'CR12', 'requires_expiry_date' => true]];
    $document = rand(0, count($documents) - 1);
    return [
      'name' => $documents[$document]['name'],
      'requires_expiry_date' => $documents[$document]['requires_expiry_date']
    ];
  }
}
