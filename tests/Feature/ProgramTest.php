<?php

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\BankUser;
use App\Models\Company;
use App\Models\Program;
use App\Models\ProgramCode;
use App\Models\ProgramType;
use App\Models\User;
use Database\Seeders\ProgramCodeSeeder;
use Database\Seeders\ProgramCompanyRoleSeeder;
use Database\Seeders\ProgramRoleSeeder;
use Database\Seeders\ProgramTypeSeeder;
use Database\Seeders\RolesAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProgramTest extends TestCase
{
  // use RefreshDatabase;

  public Bank $bank;
  public User $bank_admin;

  protected function setUp(): void
  {
    parent::setUp();

    $this->bank = $this->getBank();
    $this->bank_admin = $this->getUser('bank_admin');

    $this->assignBank($this->bank, $this->bank_admin);
  }

  public function test_bank_user_can_see_program_creation_form()
  {
    $response = $this->actingAs($this->bank_admin)->get($this->bank->url . '/programs/create');

    $response->assertStatus(200);
    $response->assertSee('Add Program');
  }

  public function test_bank_user_can_create_program()
  {
    $status = 'active';

    $response = $this->actingAs($this->bank_admin)->post(
      $this->bank->url . '/programs/store',
      $this->addProgram($this->bank, $status)
    );

    $response->assertStatus(302)->assertRedirectToRoute('programs.index', ['bank' => $this->bank]);

    $response = $this->actingAs($this->bank_admin)->get($this->bank->url . '/programs', [
      'Accept' => 'application/json',
    ]);

    $response
      ->assertStatus(200)
      ->assertJsonCount(1)
      ->assertJsonFragment(['name' => 'Program 001']);
  }

  public function test_bank_user_can_approve_program()
  {
    $status = 'pending';

    $response = $this->actingAs($this->bank_admin)->post(
      $this->bank->url . '/programs/store',
      $this->addProgram($this->bank, $status)
    );

    $response->assertStatus(302);

    $response = $this->actingAs($this->bank_admin)->get($this->bank->url . '/programs', [
      'Accept' => 'application/json',
    ]);

    $response
      ->assertStatus(200)
      ->assertJsonCount(1)
      ->assertJsonFragment(['account_status' => 'pending']);

    $program = $this->bank->programs->first();

    $response = $this->actingAs($this->bank_admin)->get(
      $this->bank->url . '/programs/' . $program->id . '/update/status/active'
    );

    $response->assertStatus(302);

    $response = $this->actingAs($this->bank_admin)->get($this->bank->url . '/programs', [
      'Accept' => 'application/json',
    ]);

    $response
      ->assertStatus(200)
      ->assertJsonCount(1)
      ->assertJsonFragment(['account_status' => 'active']);
  }

  public function test_bank_can_map_vendors_to_program()
  {
    $status = 'active';

    // Create a new program
    $response = $this->actingAs($this->bank_admin)->post(
      $this->bank->url . '/programs/store',
      $this->addProgram($this->bank, $status)
    );

    $response->assertStatus(302);

    $response = $this->actingAs($this->bank_admin)->get($this->bank->url . '/programs', [
      'Accept' => 'application/json',
    ]);

    $response->assertStatus(200)->assertJsonFragment(['account_status' => 'active']);

    // Create a new vendor company
    $company = $this->mapVendor($this->bank)['company'];

    $response = $this->actingAs($this->bank_admin)->post(
      $this->bank->url . '/companies/store',
      $this->mapVendor($this->bank)['company']
    );

    $response->assertStatus(302);

    $response = $this->actingAs($this->bank_admin)->get($this->bank->url . '/companies', [
      'Accept' => 'application/json',
    ]);

    $response->assertStatus(200)->assertJsonFragment(['name' => $company['name']]);

    // Get Program
    $program = $this->bank->programs->first();

    $company = Company::where('bank_id', $this->bank->id)
      ->orderBy('id', 'DESC')
      ->first();

    $vendor_details = [
      'vendor_id' => $company->id,
    ];

    $vendor_details = collect($vendor_details)->merge($this->mapVendor($this->bank)['configuration']);
    $vendor_details = $vendor_details->merge($this->mapVendor($this->bank)['discount']);
    $vendor_details = $vendor_details->merge($this->mapVendor($this->bank)['fees']);
    $vendor_details = $vendor_details->merge($this->mapVendor($this->bank)['contact_details']);
    $vendor_details = $vendor_details->merge($this->mapVendor($this->bank)['bank_details']);

    // Map Vendor to program with configurations
    $response = $this->actingAs($this->bank_admin)->post(
      $this->bank->url . '/programs/' . $program->id . '/vendor/map',
      $vendor_details->toArray()
    );

    $response
      ->assertStatus(302)
      ->assertRedirectToRoute('programs.show', ['bank' => $this->bank, 'program' => $program]);
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

  private function addProgram(Bank $bank, $status)
  {
    $this->actingAs($this->bank_admin)->post($this->bank->url . '/companies/store', $this->addCompany($this->bank));

    $company = Company::first();

    $program_type = ProgramType::where('name', Program::VENDOR_FINANCING)->first();
    $program_code = ProgramCode::where('name', Program::FACTORING_WITH_RECOURSE)->first();

    $program = [
      'anchor_id' => $company->id,
      'program_type_id' => $program_type->id,
      'program_code_id' => $program_code->id,
      'name' => 'Program 001',
      'code' => 'P001-' . time(),
      'eligibility' => 80,
      'invoice_margin' => 20,
      'program_limit' => 1000000000,
      'approved_date' => now()->format('Y-m-d'),
      'limit_expiry_date' => now()
        ->addMonths(8)
        ->format('Y-m-d'),
      'max_limit_per_account' => 20000000,
      'collection_account' => 'CO-001-' . $bank->name,
      'request_auto_finance' => false,
      'stale_invoice_period' => 20,
      'min_financing_days' => 5,
      'max_financing_days' => 20,
      'auto_debit_anchor_financed_invoices' => false,
      'auto_debit_anchor_non_financed_invoices' => false,
      'anchor_can_change_due_date' => true,
      'max_days_due_date_extension' => 10,
      'days_limit_for_due_date_change' => false,
      'default_payment_terms' => 10,
      'anchor_can_change_payment_term' => false,
      'mandatory_invoice_attachment' => false,
      'recourse' => 'Without Recourse',
      'due_date_calculated_from' => 'Dibursement Date',
      'account_status' => $status,
      // 'segment' => ,
      // 'partner' => ,
      // 'noa' => ,

      // Discount
      'benchmark_title' => 'Base Rate 1',
      'benchmark_rate' => 15,
      'reset_frequency' => 'Monthly',
      'days_frequency_days' => 20,
      'business_strategy_spread' => 12,
      'credit_spread' => 15,
      'total_spread' => 15,
      'total_roi' => 25,
      'anchor_discount_bearing' => 0,
      'vendor_discount_bearing' => 100,
      'discount_type' => 'Front Ended',
      'penal_discount_on_principle' => 10,
      'anchor_fee_recovery' => 'Beginning of Tenor',
      'grace_period' => 6,
      'grace_period_discount' => 12,
      'maturity_handling_on_holidays' => 'No Effect',

      // Fees
      'fee_names' => ['Service Fee', 'Convenience Fee', 'Service Fee'],
      'fee_types' => ['percentage', 'amount', 'percentage'],
      'fee_values' => [15, 65, 10],
      'fee_anchor_bearing_discount' => [6, 20, 2],
      'fee_vendor_bearing_discount' => [6, 20, 2],
      'taxes' => ['VAT', 'WHT', 'Business'],

      // Program Anchor Details
      'anchor_emails' => ['anchor@yofinvoice.com', 'anchor2@yofinvoice.com', 'anchor3@yofinvoice.com'],
      'anchor_phone_numbers' => ['0707126364', '0707122344', '0707122234'],

      // Bank Details
      'bank_names_as_per_banks' => [Str::random(rand(4, 8)), Str::random(rand(4, 8)), Str::random(rand(4, 8))],
      'account_numbers' => [rand(5, 9), rand(5, 9), rand(5, 9)],
      'bank_names' => ['KCB', 'Ecobank', 'Equity'],
      'branches' => ['Nairobi', 'Nairobi', 'Nairobi'],
      'swift_codes' => [
        Str::random(4) . '-' . Str::random(6),
        Str::random(4) . '-' . Str::random(6),
        Str::random(4) . '-' . Str::random(6),
      ],
      'account_types' => ['Credit', 'Debit', 'Credit'],
    ];

    return $program;
  }

  private function mapVendor(Bank $bank)
  {
    $company = [
      'bank_id' => $bank->id,
      'name' => 'Vendor One',
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

    $configuration = [
      'payment_account_number' => 'VEN-' . time(),
      'sanctioned_limit' => 10000000,
      'limit_approved_date' => now()
        ->addDays(2)
        ->format('Y-m-d'),
      'limit_expiry_date' => now()
        ->addDays(10)
        ->format('Y-m-d'),
      'limit_review_date' => now()
        ->addMonths(4)
        ->format('Y-m-d'),
      'drawing_power' => 1500000,
      'request_auto_finance' => false,
      'auto_approve_finance' => false,
      'eligibility' => 12,
      'invoice_margin' => 8,
      'schema_code' => null,
      'product_description' => 'Test product description',
      'vendor_code' => null,
      'gst_number' => 0021512201,
      'classification' => 'Unsecured',
      'tds' => 'Not Applicable',
      'status' => 'active',
    ];

    $discount = [
      'benchmark_title' => 'Base Rate 1',
      'benchmark_rate' => 15,
      'reset_frequency' => 'Monthly',
      'days_frequency_days' => 20,
      'business_strategy_spread' => 12,
      'credit_spread' => 15,
      'total_spread' => 15,
      'total_roi' => 25,
      'anchor_discount_bearing' => 0,
      'vendor_discount_bearing' => 100,
      'penal_discount_on_principle' => 10,
      'grace_period' => 6,
      'grace_period_discount' => 12,
      'maturity_handling_on_holidays' => 'No Effect',
    ];

    // Fees
    $fees = [
      'fee_names' => ['Service Fee', 'Convenience Fee', 'Service Fee'],
      'fee_types' => ['percentage', 'amount', 'percentage'],
      'fee_values' => [15, 65, 10],
      'fee_anchor_bearing_discount' => [6, 20, 2],
      'fee_vendor_bearing_discount' => [6, 20, 2],
      'taxes' => ['VAT', 'WHT', 'Business'],
    ];

    // Vendor Contact Details
    $contact_details = [
      'vendor_emails' => ['vendor@yofinvoice.com', 'vendor2@yofinvoice.com', 'vendor3@yofinvoice.com'],
      'vendor_phone_numbers' => ['0707126364', '0707122344', '0707122234'],
    ];

    // Bank Details
    $bank_details = [
      'bank_names_as_per_banks' => [Str::random(rand(4, 8)), Str::random(rand(4, 8)), Str::random(rand(4, 8))],
      'account_numbers' => [rand(5, 9), rand(5, 9), rand(5, 9)],
      'bank_names' => ['KCB', 'Ecobank', 'Equity'],
      'branches' => ['Nairobi', 'Nairobi', 'Nairobi'],
      'swift_codes' => [
        Str::random(4) . '-' . Str::random(6),
        Str::random(4) . '-' . Str::random(6),
        Str::random(4) . '-' . Str::random(6),
      ],
      'account_types' => ['Credit', 'Debit', 'Credit'],
    ];

    return [
      'company' => $company,
      'configuration' => $configuration,
      'discount' => $discount,
      'fees' => $fees,
      'contact_details' => $contact_details,
      'bank_details' => $bank_details,
    ];
  }
}
