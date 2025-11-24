<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\BankDocument;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    Bank::factory()
        ->has(BankDocument::factory(3), 'requiredDocuments')
        ->create();

    sleep(1);

    Bank::factory()
        ->has(BankDocument::factory(2), 'requiredDocuments')
        ->create([
            'url' => '456789'
        ]);
  }
}
