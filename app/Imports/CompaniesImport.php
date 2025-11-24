<?php

namespace App\Imports;

use App\Jobs\SendMail;
use App\Models\Bank;
use App\Models\BankUser;
use App\Models\Company;
use App\Models\CompanyRelationshipManager;
use App\Models\CompanyUploadReport;
use App\Models\User;
use App\Notifications\CompanyCreation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Events\BeforeImport;

class CompaniesImport implements ToCollection, WithMapping, WithHeadingRow, WithEvents, SkipsEmptyRows, SkipsOnFailure
{
  use Importable, SkipsFailures;

  public $data = 0;
  public $total_rows = 0;

  public function __construct(public Bank $bank)
  {
  }

  public function registerEvents(): array
  {
    return [
      BeforeImport::class => function (BeforeImport $event) {
        $this->total_rows = $event->getReader()->getTotalRows();
      },
    ];
  }

  public function map($row): array
  {
    if (
      !array_key_exists('company_name', $row) ||
      !array_key_exists('top_level_borrower_limit_ksh', $row) ||
      !array_key_exists('limit_expiry_date_ddmmyyyy', $row) ||
      !array_key_exists('companys_unique_identification_no', $row) ||
      !array_key_exists('labellabellbl_bank_cus_id', $row) ||
      !array_key_exists('branch_code', $row) ||
      !array_key_exists('organization_type', $row) ||
      !array_key_exists('business_segment', $row) ||
      !array_key_exists('industry', $row) ||
      !array_key_exists('contact_person_name', $row) ||
      !array_key_exists('email', $row) ||
      !array_key_exists('mobile', $row) ||
      !array_key_exists('stateprovince', $row) ||
      !array_key_exists('city', $row) ||
      !array_key_exists('pinzippostal_code', $row) ||
      !array_key_exists('address', $row) ||
      !array_key_exists('created_by', $row) ||
      !array_key_exists('created_at_ddmmyyyy', $row) ||
      !array_key_exists('last_updated_by', $row) ||
      !array_key_exists('last_updated_at_ddmmyyyy', $row)
    ) {
      throw ValidationException::withMessages([
        'Invalid headers or missing column. Download and use the sample template.',
      ]);
    }

    return [
      'name' => $row['company_name'],
      'top_level_borrower_limit' => $row['top_level_borrower_limit_ksh'],
      'unique_identification_no' => $row['companys_unique_identification_no'],
      'cif' => $row['labellabellbl_bank_cus_id'],
      'limit_expiry_date' =>
        gettype($row['limit_expiry_date_ddmmyyyy']) == 'integer'
          ? Date::excelToDateTimeObject($row['limit_expiry_date'])->format('Y-d-m')
          : ($row['limit_expiry_date_ddmmyyyy'] != null
            ? Carbon::createFromFormat('d/m/Y', $row['limit_expiry_date_ddmmyyyy'])->format('Y-m-d')
            : null),
      'unique_identification_number' => $row['companys_unique_identification_no'],
      'branch_code' => $row['branch_code'],
      'organization_type' => $row['organization_type'],
      'business_segment' => $row['business_segment'],
      'industry' => $row['industry'],
      'state' => $row['stateprovince'],
      'address' => $row['address'],
      'city' => $row['city'],
      'contact_person_name' => $row['contact_person_name'],
      'contact_person_email' => $row['email'],
      'contact_person_mobile' => $row['mobile'],
      'postal_code' => $row['pinzippostal_code'],
      'created_by' => $row['created_by'],
      'created_at' =>
        str_replace(' ', '', $row['created_at_ddmmyyyy']) && str_replace(' ', '', $row['created_at_ddmmyyyy']) != ''
          ? (gettype($row['created_at_ddmmyyyy']) == 'integer'
            ? Date::excelToDateTimeObject($row['created_at_ddmmyyyy'])->format('Y-d-m')
            : ($row['created_at_ddmmyyyy'] != null
              ? Carbon::createFromFormat('d/m/Y', explode(' ', $row['created_at_ddmmyyyy'])[0])->format('Y-m-d')
              : null))
          : null,
      'updated_by' => $row['last_updated_by'],
      'updated_at' =>
        str_replace(' ', '', $row['last_updated_at_ddmmyyyy']) &&
        str_replace(' ', '', $row['last_updated_at_ddmmyyyy']) != ''
          ? (gettype($row['last_updated_at_ddmmyyyy']) == 'integer'
            ? Date::excelToDateTimeObject($row['last_updated_at_ddmmyyyy'])->format('Y-d-m')
            : ($row['last_updated_at_ddmmyyyy'] != null
              ? Carbon::createFromFormat('d/m/Y', explode(' ', $row['last_updated_at_ddmmyyyy'])[0])->format('Y-m-d')
              : null))
          : null,
    ];
  }

  /**
   * @param Collection $collection
   */
  public function collection(Collection $collection)
  {
    $latest_batch_id = (int) CompanyUploadReport::where('bank_id', $this->bank->id)
      ->select('batch_id')
      ->latest()
      ->first()?->batch_id;

    if (!$latest_batch_id) {
      $latest_batch_id = 0;
    }

    foreach ($collection as $company_data) {
      if (
        $company_data['name'] &&
        $company_data['organization_type'] &&
        $company_data['branch_code'] &&
        $company_data['top_level_borrower_limit'] &&
        $company_data['limit_expiry_date'] &&
        $company_data['state'] &&
        $company_data['city'] &&
        $company_data['postal_code'] &&
        $company_data['unique_identification_number']
      ) {
        $existing_company = Company::where('bank_id', $this->bank->id)
          ->where('name', $company_data['name'])
          ->where('unique_identification_number', $company_data['unique_identification_number'])
          ->first();

        if (!$existing_company) {
          $latest_id =
            Company::where('bank_id', $this->bank->id)
              ->latest()
              ->where('is_published', true)
              ->first()?->id + 1;

          if (!$latest_id) {
            $latest_id = 1;
          }

          $latest_bank_id =
            Company::where('bank_id', $this->bank->id)
              ->orderBy('company_bank_id', 'DESC')
              ->where('is_published', true)
              ->first()?->company_bank_id + 1;
          if (!$latest_bank_id) {
            $latest_bank_id = 1;
          }

          $created_by = User::where('name', $company_data['created_by'])
            ->orWhere('email', $company_data['created_by'])
            ->first();

          $company = Company::create([
            'bank_id' => $this->bank->id,
            'company_bank_id' => $latest_bank_id,
            'name' => $company_data['name'],
            'top_level_borrower_limit' => Str::replace(',', '', $company_data['top_level_borrower_limit']),
            'limit_expiry_date' => Carbon::parse($company_data['limit_expiry_date']),
            'unique_identification_number' => $company_data['unique_identification_number'],
            'branch_code' => $company_data['branch_code'],
            // 'business_identification_number' => $company_data['business_identification_number'],
            'organization_type' => $company_data['organization_type'],
            'business_segment' => $company_data['business_segment'],
            // 'customer_type' => $company_data['customer_type'],
            'kra_pin' => array_key_exists('kra_pin', $company_data->toArray())
              ? $company_data['kra_pin']
              : $company_data['unique_identification_number'],
            'city' => $company_data['city'],
            'postal_code' => $company_data['postal_code'],
            'address' => $company_data['address'],
            'cif' => $company_data['cif'] && $company_data['cif'] != null ? $company_data['cif'] : 'CIF_' . $latest_id,
            'approval_status' => 'pending',
            'status' => 'inactive',
            'created_by' => $created_by ? $created_by->id : auth()->id(),
          ]);

          if (
            array_key_exists('contact_person_name', $company_data->toArray()) &&
            array_key_exists('contact_person_email', $company_data->toArray()) &&
            array_key_exists('contact_person_mobile', $company_data->toArray())
          ) {
            CompanyRelationshipManager::create([
              'company_id' => $company->id,
              'email' => $company_data['contact_person_email'],
              'name' => $company_data['contact_person_name'],
              'phone_number' => $company_data['contact_person_mobile'],
            ]);
          }

          CompanyUploadReport::create([
            'bank_id' => $this->bank->id,
            'batch_id' => $latest_batch_id + 1,
            'name' => $company_data['name'],
            'top_level_borrower_limit' => $company_data['top_level_borrower_limit'],
            'limit_expiry_date' => $company_data['limit_expiry_date'],
            'unique_identification_number' => $company_data['unique_identification_number'],
            'branch_code' => $company_data['branch_code'],
            // 'business_identification_number' => $company_data['business_identification_number'],
            'organization_type' => $company_data['organization_type'],
            // 'customer_type' => $company_data['customer_type'],
            'state' => $company_data['state'],
            'city' => $company_data['city'],
            'postal_code' => $company_data['postal_code'],
            'address' => $company_data['address'],
            'kra_pin' => array_key_exists('kra_pin', $company_data->toArray()) ? $company_data['kra_pin'] : null,
            'contact_person_name' => array_key_exists('contact_person_name', $company_data->toArray())
              ? $company_data['contact_person_name']
              : null,
            'email' => array_key_exists('contact_person_email', $company_data->toArray())
              ? $company_data['contact_person_email']
              : null,
            'mobile' => array_key_exists('contact_person_mobile', $company_data->toArray())
              ? $company_data['contact_person_mobile']
              : null,
            'created_by' => $company_data['created_by'],
            'created_at' => Carbon::parse($company_data['created_at']),
            'last_updated_by' => $company_data['updated_by'],
            'updated_at' => Carbon::parse($company_data['updated_at']),
          ]);

          activity($this->bank->id)
            ->causedBy(auth()->user())
            ->performedOn($company)
            ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
            ->log('uploaded company');

          // Add counter for notification of how many records were uploaded
          $this->data++;

          $bank_users = BankUser::where('bank_id', $this->bank->id)
            ->whereHas('user', function ($query) {
              $query->whereHas('roles', function ($query) {
                $query->whereHas('permissions', function ($query) {
                  $query->where('name', 'Activate/Deactivate Companies');
                });
              });
            })
            ->where('user_id', '!=', auth()->id())
            ->where('active', true)
            ->get();

          if ($bank_users->count() > 0) {
            foreach ($bank_users as $bank_user) {
              $bank_user->user->notify(new CompanyCreation($company));
              SendMail::dispatchAfterResponse($bank_user->user->email, 'CompanyCreated', ['company' => $company->id]);
            }
          }
        } else {
          CompanyUploadReport::create([
            'bank_id' => $this->bank->id,
            'batch_id' => $latest_batch_id + 1,
            'name' => $company_data['name'],
            'top_level_borrower_limit' => $company_data['top_level_borrower_limit'],
            'limit_expiry_date' => $company_data['limit_expiry_date'],
            'unique_identification_number' => $company_data['unique_identification_number'],
            'branch_code' => $company_data['branch_code'],
            // 'business_identification_number' => $company_data['business_identification_number'],
            'organization_type' => $company_data['organization_type'],
            // 'customer_type' => $company_data['customer_type'],
            'state' => $company_data['state'],
            'city' => $company_data['city'],
            'postal_code' => $company_data['postal_code'],
            'address' => $company_data['address'],
            'kra_pin' => array_key_exists('kra_pin', $company_data->toArray()) ? $company_data['kra_pin'] : null,
            'contact_person_name' => array_key_exists('contact_person_name', $company_data->toArray())
              ? $company_data['contact_person_name']
              : null,
            'email' => array_key_exists('contact_person_email', $company_data->toArray())
              ? $company_data['contact_person_email']
              : null,
            'mobile' => array_key_exists('contact_person_mobile', $company_data->toArray())
              ? $company_data['contact_person_mobile']
              : null,
            'created_by' => $company_data['created_by'],
            'created_at' => Carbon::parse($company_data['created_at']),
            'last_updated_by' => $company_data['updated_by'],
            'updated_at' => Carbon::parse($company_data['updated_at']),
            'status' => 'failed',
            'description' => 'Company with name and unique identification number in already in use',
          ]);
        }
      } else {
        if (!$company_data['name']) {
          CompanyUploadReport::create([
            'bank_id' => $this->bank->id,
            'batch_id' => $latest_batch_id + 1,
            'name' => $company_data['name'],
            'top_level_borrower_limit' => $company_data['top_level_borrower_limit'],
            'limit_expiry_date' => $company_data['limit_expiry_date'],
            'unique_identification_number' => $company_data['unique_identification_number'],
            'branch_code' => $company_data['branch_code'],
            // 'business_identification_number' => $company_data['business_identification_number'],
            'organization_type' => $company_data['organization_type'],
            // 'customer_type' => $company_data['customer_type'],
            'state' => $company_data['state'],
            'city' => $company_data['city'],
            'postal_code' => $company_data['postal_code'],
            'address' => $company_data['address'],
            'kra_pin' => array_key_exists('kra_pin', $company_data->toArray()) ? $company_data['kra_pin'] : null,
            'contact_person_name' => array_key_exists('contact_person_name', $company_data->toArray())
              ? $company_data['contact_person_name']
              : null,
            'email' => array_key_exists('contact_person_email', $company_data->toArray())
              ? $company_data['contact_person_email']
              : null,
            'mobile' => array_key_exists('contact_person_mobile', $company_data->toArray())
              ? $company_data['contact_person_mobile']
              : null,
            'created_by' => $company_data['created_by'],
            'created_at' => Carbon::parse($company_data['created_at']),
            'last_updated_by' => $company_data['updated_by'],
            'updated_at' => Carbon::parse($company_data['updated_at']),
            'status' => 'failed',
            'description' => 'Enter the Company Name',
          ]);
        } elseif (!$company_data['organization_type']) {
          CompanyUploadReport::create([
            'bank_id' => $this->bank->id,
            'batch_id' => $latest_batch_id + 1,
            'name' => $company_data['name'],
            'top_level_borrower_limit' => $company_data['top_level_borrower_limit'],
            'limit_expiry_date' => $company_data['limit_expiry_date'],
            'unique_identification_number' => $company_data['unique_identification_number'],
            'branch_code' => $company_data['branch_code'],
            // 'business_identification_number' => $company_data['business_identification_number'],
            'organization_type' => $company_data['organization_type'],
            // 'customer_type' => $company_data['customer_type'],
            'state' => $company_data['state'],
            'city' => $company_data['city'],
            'postal_code' => $company_data['postal_code'],
            'address' => $company_data['address'],
            'kra_pin' => array_key_exists('kra_pin', $company_data->toArray()) ? $company_data['kra_pin'] : null,
            'contact_person_name' => array_key_exists('contact_person_name', $company_data->toArray())
              ? $company_data['contact_person_name']
              : null,
            'email' => array_key_exists('contact_person_email', $company_data->toArray())
              ? $company_data['contact_person_email']
              : null,
            'mobile' => array_key_exists('contact_person_mobile', $company_data->toArray())
              ? $company_data['contact_person_mobile']
              : null,
            'created_by' => $company_data['created_by'],
            'created_at' => Carbon::parse($company_data['created_at']),
            'last_updated_by' => $company_data['updated_by'],
            'updated_at' => Carbon::parse($company_data['updated_at']),
            'status' => 'failed',
            'description' => 'Enter the Organization Type',
          ]);
        } elseif (!$company_data['branch_code']) {
          CompanyUploadReport::create([
            'bank_id' => $this->bank->id,
            'batch_id' => $latest_batch_id + 1,
            'name' => $company_data['name'],
            'top_level_borrower_limit' => $company_data['top_level_borrower_limit'],
            'limit_expiry_date' => $company_data['limit_expiry_date'],
            'unique_identification_number' => $company_data['unique_identification_number'],
            'branch_code' => $company_data['branch_code'],
            // 'business_identification_number' => $company_data['business_identification_number'],
            'organization_type' => $company_data['organization_type'],
            // 'customer_type' => $company_data['customer_type'],
            'state' => $company_data['state'],
            'city' => $company_data['city'],
            'postal_code' => $company_data['postal_code'],
            'address' => $company_data['address'],
            'kra_pin' => array_key_exists('kra_pin', $company_data->toArray()) ? $company_data['kra_pin'] : null,
            'contact_person_name' => array_key_exists('contact_person_name', $company_data->toArray())
              ? $company_data['contact_person_name']
              : null,
            'email' => array_key_exists('contact_person_email', $company_data->toArray())
              ? $company_data['contact_person_email']
              : null,
            'mobile' => array_key_exists('contact_person_mobile', $company_data->toArray())
              ? $company_data['contact_person_mobile']
              : null,
            'created_by' => $company_data['created_by'],
            'created_at' => Carbon::parse($company_data['created_at']),
            'last_updated_by' => $company_data['updated_by'],
            'updated_at' => Carbon::parse($company_data['updated_at']),
            'status' => 'failed',
            'description' => 'Enter the Company Branch Code',
          ]);
        } elseif (!$company_data['top_level_borrower_limit']) {
          CompanyUploadReport::create([
            'bank_id' => $this->bank->id,
            'batch_id' => $latest_batch_id + 1,
            'name' => $company_data['name'],
            'top_level_borrower_limit' => $company_data['top_level_borrower_limit'],
            'limit_expiry_date' => $company_data['limit_expiry_date'],
            'unique_identification_number' => $company_data['unique_identification_number'],
            'branch_code' => $company_data['branch_code'],
            // 'business_identification_number' => $company_data['business_identification_number'],
            'organization_type' => $company_data['organization_type'],
            // 'customer_type' => $company_data['customer_type'],
            'state' => $company_data['state'],
            'city' => $company_data['city'],
            'postal_code' => $company_data['postal_code'],
            'address' => $company_data['address'],
            'kra_pin' => array_key_exists('kra_pin', $company_data->toArray()) ? $company_data['kra_pin'] : null,
            'contact_person_name' => array_key_exists('contact_person_name', $company_data->toArray())
              ? $company_data['contact_person_name']
              : null,
            'email' => array_key_exists('contact_person_email', $company_data->toArray())
              ? $company_data['contact_person_email']
              : null,
            'mobile' => array_key_exists('contact_person_mobile', $company_data->toArray())
              ? $company_data['contact_person_mobile']
              : null,
            'created_by' => $company_data['created_by'],
            'created_at' => Carbon::parse($company_data['created_at']),
            'last_updated_by' => $company_data['updated_by'],
            'updated_at' => Carbon::parse($company_data['updated_at']),
            'status' => 'failed',
            'description' => 'Enter the Company Top Level Borrower Limit',
          ]);
        } elseif (!$company_data['limit_expiry_date']) {
          CompanyUploadReport::create([
            'bank_id' => $this->bank->id,
            'batch_id' => $latest_batch_id + 1,
            'name' => $company_data['name'],
            'top_level_borrower_limit' => $company_data['top_level_borrower_limit'],
            'limit_expiry_date' => $company_data['limit_expiry_date'],
            'unique_identification_number' => $company_data['unique_identification_number'],
            'branch_code' => $company_data['branch_code'],
            // 'business_identification_number' => $company_data['business_identification_number'],
            'organization_type' => $company_data['organization_type'],
            // 'customer_type' => $company_data['customer_type'],
            'state' => $company_data['state'],
            'city' => $company_data['city'],
            'postal_code' => $company_data['postal_code'],
            'address' => $company_data['address'],
            'kra_pin' => array_key_exists('kra_pin', $company_data->toArray()) ? $company_data['kra_pin'] : null,
            'contact_person_name' => array_key_exists('contact_person_name', $company_data->toArray())
              ? $company_data['contact_person_name']
              : null,
            'email' => array_key_exists('contact_person_email', $company_data->toArray())
              ? $company_data['contact_person_email']
              : null,
            'mobile' => array_key_exists('contact_person_mobile', $company_data->toArray())
              ? $company_data['contact_person_mobile']
              : null,
            'created_by' => $company_data['created_by'],
            'created_at' => Carbon::parse($company_data['created_at']),
            'last_updated_by' => $company_data['updated_by'],
            'updated_at' => Carbon::parse($company_data['updated_at']),
            'status' => 'failed',
            'description' => 'Enter the Company Limit Expiry Date',
          ]);
        } elseif (!$company_data['state']) {
          CompanyUploadReport::create([
            'bank_id' => $this->bank->id,
            'batch_id' => $latest_batch_id + 1,
            'name' => $company_data['name'],
            'top_level_borrower_limit' => $company_data['top_level_borrower_limit'],
            'limit_expiry_date' => $company_data['limit_expiry_date'],
            'unique_identification_number' => $company_data['unique_identification_number'],
            'branch_code' => $company_data['branch_code'],
            // 'business_identification_number' => $company_data['business_identification_number'],
            'organization_type' => $company_data['organization_type'],
            // 'customer_type' => $company_data['customer_type'],
            'city' => $company_data['city'],
            'postal_code' => $company_data['postal_code'],
            'address' => $company_data['address'],
            'kra_pin' => array_key_exists('kra_pin', $company_data->toArray()) ? $company_data['kra_pin'] : null,
            'contact_person_name' => array_key_exists('contact_person_name', $company_data->toArray())
              ? $company_data['contact_person_name']
              : null,
            'email' => array_key_exists('contact_person_email', $company_data->toArray())
              ? $company_data['contact_person_email']
              : null,
            'mobile' => array_key_exists('contact_person_mobile', $company_data->toArray())
              ? $company_data['contact_person_mobile']
              : null,
            'created_by' => $company_data['created_by'],
            'created_at' => Carbon::parse($company_data['created_at']),
            'last_updated_by' => $company_data['updated_by'],
            'updated_at' => Carbon::parse($company_data['updated_at']),
            'status' => 'failed',
            'description' => 'Enter the Company State',
          ]);
        } elseif (!$company_data['city']) {
          CompanyUploadReport::create([
            'bank_id' => $this->bank->id,
            'batch_id' => $latest_batch_id + 1,
            'name' => $company_data['name'],
            'top_level_borrower_limit' => $company_data['top_level_borrower_limit'],
            'limit_expiry_date' => $company_data['limit_expiry_date'],
            'unique_identification_number' => $company_data['unique_identification_number'],
            'branch_code' => $company_data['branch_code'],
            // 'business_identification_number' => $company_data['business_identification_number'],
            'organization_type' => $company_data['organization_type'],
            // 'customer_type' => $company_data['customer_type'],
            'state' => $company_data['state'],
            'city' => $company_data['city'],
            'postal_code' => $company_data['postal_code'],
            'address' => $company_data['address'],
            'kra_pin' => array_key_exists('kra_pin', $company_data->toArray()) ? $company_data['kra_pin'] : null,
            'contact_person_name' => array_key_exists('contact_person_name', $company_data->toArray())
              ? $company_data['contact_person_name']
              : null,
            'email' => array_key_exists('contact_person_email', $company_data->toArray())
              ? $company_data['contact_person_email']
              : null,
            'mobile' => array_key_exists('contact_person_mobile', $company_data->toArray())
              ? $company_data['contact_person_mobile']
              : null,
            'created_by' => $company_data['created_by'],
            'created_at' => Carbon::parse($company_data['created_at']),
            'last_updated_by' => $company_data['updated_by'],
            'updated_at' => Carbon::parse($company_data['updated_at']),
            'status' => 'failed',
            'description' => 'Enter the Company City',
          ]);
        } elseif (!$company_data['address']) {
          CompanyUploadReport::create([
            'bank_id' => $this->bank->id,
            'batch_id' => $latest_batch_id + 1,
            'name' => $company_data['name'],
            'top_level_borrower_limit' => $company_data['top_level_borrower_limit'],
            'limit_expiry_date' => $company_data['limit_expiry_date'],
            'unique_identification_number' => $company_data['unique_identification_number'],
            'branch_code' => $company_data['branch_code'],
            // 'business_identification_number' => $company_data['business_identification_number'],
            'organization_type' => $company_data['organization_type'],
            // 'customer_type' => $company_data['customer_type'],
            'state' => $company_data['state'],
            'city' => $company_data['city'],
            'postal_code' => $company_data['postal_code'],
            'address' => $company_data['address'],
            'kra_pin' => array_key_exists('kra_pin', $company_data->toArray()) ? $company_data['kra_pin'] : null,
            'contact_person_name' => array_key_exists('contact_person_name', $company_data->toArray())
              ? $company_data['contact_person_name']
              : null,
            'email' => array_key_exists('contact_person_email', $company_data->toArray())
              ? $company_data['contact_person_email']
              : null,
            'mobile' => array_key_exists('contact_person_mobile', $company_data->toArray())
              ? $company_data['contact_person_mobile']
              : null,
            'created_by' => $company_data['created_by'],
            'created_at' => Carbon::parse($company_data['created_at']),
            'last_updated_by' => $company_data['updated_by'],
            'updated_at' => Carbon::parse($company_data['updated_at']),
            'status' => 'failed',
            'description' => 'Enter the Company Address',
          ]);
        } elseif (!$company_data['postal_code']) {
          CompanyUploadReport::create([
            'bank_id' => $this->bank->id,
            'batch_id' => $latest_batch_id + 1,
            'name' => $company_data['name'],
            'top_level_borrower_limit' => $company_data['top_level_borrower_limit'],
            'limit_expiry_date' => $company_data['limit_expiry_date'],
            'unique_identification_number' => $company_data['unique_identification_number'],
            'branch_code' => $company_data['branch_code'],
            // 'business_identification_number' => $company_data['business_identification_number'],
            'organization_type' => $company_data['organization_type'],
            // 'customer_type' => $company_data['customer_type'],
            'state' => $company_data['state'],
            'city' => $company_data['city'],
            'postal_code' => $company_data['postal_code'],
            'address' => $company_data['address'],
            'kra_pin' => array_key_exists('kra_pin', $company_data->toArray()) ? $company_data['kra_pin'] : null,
            'contact_person_name' => array_key_exists('contact_person_name', $company_data->toArray())
              ? $company_data['contact_person_name']
              : null,
            'email' => array_key_exists('contact_person_email', $company_data->toArray())
              ? $company_data['contact_person_email']
              : null,
            'mobile' => array_key_exists('contact_person_mobile', $company_data->toArray())
              ? $company_data['contact_person_mobile']
              : null,
            'created_by' => $company_data['created_by'],
            'created_at' => Carbon::parse($company_data['created_at']),
            'last_updated_by' => $company_data['updated_by'],
            'updated_at' => Carbon::parse($company_data['updated_at']),
            'status' => 'failed',
            'description' => 'Enter the Company Postal Code',
          ]);
        }
      }
    }
  }
}
