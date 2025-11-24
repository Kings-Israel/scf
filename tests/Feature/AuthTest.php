<?php

namespace Tests\Feature;

use App\Models\AccessRightGroup;
use App\Models\Bank;
use App\Models\BankUser;
use App\Models\PermissionData;
use App\Models\RoleType;
use App\Models\TargetRole;
use App\Models\User;
use Database\Seeders\BankSeeder;
use Database\Seeders\RolesAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthTest extends TestCase
{
  // use RefreshDatabase;

  protected Bank $bank;
  protected User $bank_admin;

  protected function setUp(): void
  {
    parent::setUp();

    $bank_admin_role = Role::where('name', 'bank_admin')->first();
    if (!$bank_admin_role) {
      $this->createBankRole('bank_admin');
    }

    // if (!$this->bank) {
    // }
    $this->bank = $this->getBank();

    $this->bank_admin = $this->getUser('bank_admin');
  }

  public function test_unauthenticated_user_cannot_see_dashboard()
  {
    $response = $this->get('/' . $this->bank->url);

    $response->assertStatus(302);
    $response->assertRedirect('/');
  }

  public function test_authenticated_user_can_login_to_bank()
  {
    $user = $this->getUser('bank_admin');

    $this->assignBank($this->bank, $user);

    $response = $this->post('/login', [
      'email' => $user->email,
      'password' => 'password',
    ]);

    $response->assertStatus(302);
    $response->assertRedirect($this->bank->url . '/');
  }

  public function test_authenticated_user_cannot_login_to_another_bank()
  {
    $user = $this->getUser('bank_admin');

    $this->assignBank($this->bank, $user);

    // Create new bank
    $new_bank = Bank::factory()->create([
      'url' => '123456',
    ]);

    $response = $this->post('/login', [
      'email' => $user->email,
      'password' => 'password',
    ]);

    $response->assertStatus(302);
    $response->assertRedirect('/login');
  }

  private function getUser($role)
  {
    $user = User::factory()->create();

    $user->assignRole($role);

    return $user;
  }

  private function getBank()
  {
    $bank = Bank::factory()->create();

    return $bank;
  }

  private function assignBank(Bank $bank, User $user)
  {
    // Assign User to Bank
    BankUser::create([
      'bank_id' => $bank->id,
      'user_id' => $user->id,
    ]);
  }

  private function createBankRole($role_name)
  {
    $bank_role_type = RoleType::where('name', 'Bank')->first();
    $permission_data = TargetRole::where('role_type_id', $bank_role_type->id)->pluck('id');
    $permissions = AccessRightGroup::whereIn('target_role_id', $permission_data)->pluck('name');
    // Create Bank Role
    $bank_user_role = Role::create([
      'name' => $role_name,
      'guard_name' => 'web',
    ]);

    // Assign Bank Permissions to role
    foreach ($permissions as $permission) {
      $bank_user_role->givePermissionTo($permission);
    }
  }
}
