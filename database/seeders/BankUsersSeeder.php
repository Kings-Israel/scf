<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\BankUser;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BankUsersSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    $banks = Bank::all();

    foreach ($banks as $key => $bank) {
      $bank_admin = User::role('bank_admin')->first();

      BankUser::create([
        'user_id' => $bank_admin->id,
        'bank_id' => $bank->id
      ]);

      if ($bank->id != 1) {
        $bank_admin = User::role('bank_admin')->orderBy('created_at', 'DESC')->first();

        BankUser::create([
          'user_id' => $bank_admin->id,
          'bank_id' => $bank->id
        ]);
      }

      $bank_user = User::role('bank_admin')->first();

      BankUser::create([
        'user_id' => $bank_user->id,
        'bank_id' => $bank->id
      ]);
    }
  }
}
