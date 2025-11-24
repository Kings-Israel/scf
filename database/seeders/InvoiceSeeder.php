<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Program;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    $program = Program::first();
    $company = Company::where('id', 2)->first();

    Invoice::factory()->create([
      'program_id' => $program->id,
      'company_id' => $company->id
    ]);
  }
}
