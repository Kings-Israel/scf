<?php

namespace Database\Seeders;

use App\Models\ProgramCode;
use App\Models\ProgramType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProgramCodeSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    $program_types = ProgramType::all();

    $vendor_financing = ['Vendor Financing Receivable' => 'VFR', 'Factoring With Recourse' => 'FR', 'Factoring Without Recourse' => 'FWR'];

    collect($vendor_financing)->each(function ($abbrev, $vendor) use ($program_types) {
      ProgramCode::create([
        'program_type_id' => $program_types->where('name', 'Vendor Financing')->first()->id,
        'name' => $vendor,
        'abbrev' => $abbrev,
      ]);
    });
  }
}
