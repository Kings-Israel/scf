<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bank>
 */
class BankFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    $name = ['YoFinvoice'];

    $bank_emails = ['kings.milimo@yopesa.co'];

    return [
      'name' => $name[0],
      'email' => $bank_emails[0],
      'url' => 'yofinvoice',
    ];
  }
}
