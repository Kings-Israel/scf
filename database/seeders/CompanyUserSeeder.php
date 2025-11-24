<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanyUserSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    // Anchor company user
    $anchor_company = Company::whereHas('roles', function ($query) {
      $query->where('name', 'anchor');
    })->first();

    $anchor_user = User::where('email', 'anchor@yofinvoice.com')->first();
    
    CompanyUser::create([
      'user_id' => $anchor_user->id,
      'company_id' => $anchor_company->id
    ]);

    // Vendor company user
    $vendor_company = Company::whereHas('roles', function ($query) {
        $query->where('name', 'vendor');
    })->first();

    $vendor_user = User::where('email', 'vendor@yofinvoice.com')->first();

    CompanyUser::create([
      'user_id' => $vendor_user->id,
      'company_id' => $vendor_company->id
    ]);
  }
}
