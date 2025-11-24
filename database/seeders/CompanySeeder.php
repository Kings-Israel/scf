<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\Company;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    $bank = Bank::first();

    Company::factory(3)->create([
        'bank_id' => $bank->id
    ]);

    $bank_2 = Bank::orderBy('created_at', 'DESC')->first();

    Company::factory(2)->create([
      'bank_id' => $bank_2->id
    ]);
  }
}
