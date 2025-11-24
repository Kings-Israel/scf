<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Program;
use App\Models\ProgramCompanyRole;
use App\Models\ProgramRole;
use App\Models\ProgramVendorBankDetail;
use App\Models\ProgramVendorConfiguration;
use App\Models\ProgramVendorContactDetail;
use App\Models\ProgramVendorDiscount;
use App\Models\ProgramVendorFee;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProgramCompanyRoleSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    $program = Program::first();

    // Anchor
    $anchor = Company::where('id', 1)->first();
    $anchor_role = ProgramRole::where('name', 'anchor')->first();

    ProgramCompanyRole::create([
      'program_id' => $program->id,
      'company_id' => $anchor->id,
      'role_id' => $anchor_role->id
    ]);

    // Vendor
    $vendor = Company::where('id', 2)->first();
    $vendor_role = ProgramRole::where('name', 'vendor')->first();

    ProgramCompanyRole::create([
      'program_id' => $program->id,
      'company_id' => $vendor->id,
      'role_id' => $vendor_role->id
    ]);

    // Vendor Configuration
    ProgramVendorConfiguration::factory()->create([
      'company_id' => $vendor->id,
      'program_id' => $program->id,
    ]);

    // Vendor Discount Configurations
    ProgramVendorDiscount::factory()->create([
      'company_id' => $vendor->id,
      'program_id' => $program->id,
    ]);

    // Vendor Fees
    ProgramVendorFee::factory()->create([
      'company_id' => $vendor->id,
      'program_id' => $program->id,
    ]);

    // Vendor Bank Details
    ProgramVendorBankDetail::factory()->create([
      'company_id' => $vendor->id,
      'program_id' => $program->id,
    ]);

    // Vendor Contact Details
    ProgramVendorContactDetail::factory()->create([
      'company_id' => $vendor->id,
      'program_id' => $program->id,
    ]);
  }
}
