<?php

namespace Database\Seeders;

use App\Models\ProgramRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProgramRoleSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    $roles = ['anchor', 'vendor', 'dealer', 'buyer'];

    collect($roles)->each(fn ($role) => ProgramRole::create(['name' => $role]));
  }
}
