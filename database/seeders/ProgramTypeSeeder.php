<?php

namespace Database\Seeders;

use App\Models\ProgramType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProgramTypeSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    $types = ['Vendor Financing', 'Dealer Financing'];

    collect($types)->each(fn ($type) => ProgramType::create(['name' => $type]));
  }
}
