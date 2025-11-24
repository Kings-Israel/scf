<?php

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\BankUser;
use App\Models\Company;
use App\Models\CompanyDocument;
use App\Models\User;
use Database\Seeders\RolesAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Str;

class CompanyTest extends TestCase
{
  // use RefreshDatabase;

  protected Bank $bank;
  protected User $bank_admin;

  protected function setUp(): void
  {
    parent::setUp();

    $this->bank = $this->getBank();
    $this->bank_admin = $this->getUser('bank_admin');

    $this->assignBank($this->bank, $this->bank_admin);
  }

  public function test_can_see_create_program_page()
  {
    $response = $this->actingAs($this->bank_admin)->get($this->bank->url . '/companies/create');

    $response->assertStatus(200);
  }

  public function test_can_create_company()
  {
    $response = $this->actingAs($this->bank_admin)->post(
      $this->bank->url . '/companies/store',
      $this->addCompany($this->bank)
    );

    $response->assertStatus(302);

    // $response = $this->actingAs($this->bank_admin)->get($this->bank->url.'/companies', [
    //   'Accept' => 'application/json',
    // ]);

    // $response->assertStatus(200)
    //           ->assertJsonCount(1)
    //           ->assertJsonFragment(['name' => 'Company One']);
  }

  public function test_can_see_company_details()
  {
    $this->actingAs($this->bank_admin)->post($this->bank->url . '/companies/store', $this->addCompany($this->bank));

    $company = Company::first();

    $response = $this->actingAs($this->bank_admin)->get($this->bank->url . '/companies/' . $company->id . '/details');

    $response->assertSee($company->name);
  }

  public function test_can_approve_company()
  {
    $this->actingAs($this->bank_admin)->post($this->bank->url . '/companies/store', $this->addCompany($this->bank));

    $company = Company::first();

    $this->actingAs($this->bank_admin)->get(
      $this->bank->url . '/companies/' . $company->id . '/status/update/approved'
    );

    $response = $this->actingAs($this->bank_admin)->get($this->bank->url . '/companies/' . $company->id . '/details');

    $response->assertStatus(200);
    $response->assertSee($company->approval_status);
  }

  public function test_can_reject_company()
  {
    $this->actingAs($this->bank_admin)->post($this->bank->url . '/companies/store', $this->addCompany($this->bank));

    $company = Company::first();

    $this->actingAs($this->bank_admin)->get(
      $this->bank->url . '/companies/' . $company->id . '/status/update/rejected'
    );

    $response = $this->actingAs($this->bank_admin)->get($this->bank->url . '/companies/' . $company->id . '/details');

    $response->assertStatus(200);
    $response->assertSee($company->approval_status);
  }

  public function test_can_view_company_documents()
  {
    $this->createCompany($this->bank);

    $company = Company::first();

    $response = $this->actingAs($this->bank_admin)->get($this->bank->url . '/companies/' . $company->id . '/details');

    $response->assertStatus(200);

    $response->assertSee($company->documents->first()->name);
    $response->assertSee('Approve');
    $response->assertSee('Reject');
  }

  public function test_can_approve_document()
  {
    $this->createCompany($this->bank);

    $company = Company::first();

    $this->actingAs($this->bank_admin)->get($this->bank->url . '/companies/' . $company->id . '/details');

    $response = $this->actingAs($this->bank_admin)->post(
      $this->bank->url . '/companies/' . $company->id . '/document/status/update',
      [
        'status' => 'approved',
        'document_id' => $company->documents->first()->id,
      ]
    );

    $response->assertStatus(302);

    $response = $this->actingAs($this->bank_admin)->get($this->bank->url . '/companies/' . $company->id . '/details');

    $response->assertDontSee('Approve');
    $response->assertSee('Reject');
  }

  public function test_can_reject_document()
  {
    $this->createCompany($this->bank);

    $company = Company::first();

    $this->actingAs($this->bank_admin)->get($this->bank->url . '/companies/' . $company->id . '/details');

    // Test rejected reason is required
    $response = $this->actingAs($this->bank_admin)->post(
      $this->bank->url . '/companies/' . $company->id . '/document/status/update',
      [
        'status' => 'rejected',
        'document_id' => $company->documents->first()->id,
      ]
    );

    $response->assertSessionHasErrors('rejected_reason');

    // Test successful document update
    $response = $this->actingAs($this->bank_admin)->post(
      $this->bank->url . '/companies/' . $company->id . '/document/status/update',
      [
        'status' => 'rejected',
        'rejected_reason' => 'Test rejected reason',
        'document_id' => $company->documents->first()->id,
      ]
    );

    $response->assertStatus(302);

    $response = $this->actingAs($this->bank_admin)->get($this->bank->url . '/companies/' . $company->id . '/details');

    $response->assertSee('Approve');
    $response->assertDontSee('Reject');
  }

  public function test_can_request_more_company_documents()
  {
    $this->createCompany($this->bank);

    $company = Company::first();

    $response = $this->actingAs($this->bank_admin)->get($this->bank->url . '/companies/' . $company->id . '/details');

    $response->assertSee('Request Documents');

    $response = $this->actingAs($this->bank_admin)->post(
      $this->bank->url . '/companies/' . $company->id . '/documents/request',
      [
        'documents' => '',
      ]
    );

    $response->assertSessionHasErrors('documents');

    $response = $this->actingAs($this->bank_admin)->post(
      $this->bank->url . '/companies/' . $company->id . '/documents/request',
      [
        'documents' => 'Tax Compliance, CR12',
      ]
    );

    $response->assertStatus(302);

    $response = $this->actingAs($this->bank_admin)->get($this->bank->url . '/companies/' . $company->id . '/details');

    $response->assertSee('Requested Documents');
    $response->assertSee('Tax Compliance');
    $response->assertSee('CR12');
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

  private function addCompany(Bank $bank)
  {
    $company = [
      'bank_id' => $bank->id,
      'name' => 'Company One',
      'unique_identification_number' => rand(1, 100),
      'branch_code' => Str::random(3) . '-' . time(),
      'business_identification_number' => 'CO1-' . Str::random(4) . '-' . time(),
      'organization_type' => 'Proprietor',
      'business_segment' => '',
      'customer_type' => 'Bank Customer',
      'kra_pin' => Str::upper(Str::random(12)),
      'city' => 'Nairobi',
      'postal_code' => '23234 00100 Nairobi',
      'address' => 'Westlands',
      'relationship_manager_name' => fake()->name(),
      'relationship_manager_email' => fake()->email(),
      'relationship_manager_phone_number' => fake()->phoneNumber(),
    ];

    return $company;
  }

  public function addCompanyDocuments(Company $company, string $name, bool $has_expiry = false)
  {
    $company_dpcument = [
      'company_id' => $company->id,
      'name' => $name,
      'expiry_date' => $has_expiry
        ? now()
          ->addMonths(rand(9, 12))
          ->format('Y-m-d')
        : null,
      'status' => 'pending',
    ];

    return $company_dpcument;
  }

  public function createCompany(Bank $bank)
  {
    $this->actingAs($this->bank_admin)->post($bank->url . '/companies/store', $this->addCompany($this->bank));

    $company = Company::first();

    CompanyDocument::create($this->addCompanyDocuments($company, 'Business Registration'));
    // CompanyDocument::create($this->addCompanyDocuments($company, 'Tax Compliance', true));
  }
}
