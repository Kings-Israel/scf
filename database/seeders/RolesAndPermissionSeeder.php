<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesAndPermissionSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    $roles = ['admin', 'bank_admin', 'bank_user', 'anchor', 'vendor', 'company_user'];

    collect($roles)->each(fn ($role) => Role::firstOrCreate(['name' => $role]));
  }
}
