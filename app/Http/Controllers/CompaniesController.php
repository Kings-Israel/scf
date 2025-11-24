<?php

namespace App\Http\Controllers;

use App\Exports\CompaniesUploadReportExport;
use App\Helpers\ActivityHelper;
use Carbon\Carbon;
use App\Models\Bank;
use App\Models\User;
use App\Jobs\SendMail;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Program;
use App\Models\BankUser;
use App\Models\Document;
use App\Models\Industry;
use App\Models\Pipeline;
use Spatie\PdfToText\Pdf;
use App\Models\BankBranch;
use App\Models\CompanyBank;
use App\Models\CompanyUser;
use App\Models\ProgramRole;
use App\Models\ProgramType;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\CompanyApproved;
use App\Models\CompanyChange;
use App\Models\PipelineStage;
use App\Models\BankMasterList;
use App\Models\CbsTransaction;
use App\Models\PaymentRequest;
use App\Models\PermissionData;
use App\Models\UploadDocument;
use App\Models\CompanyDocument;
use App\Models\RequestDocument;
use App\Mail\PipelineStageUpdated;
use App\Models\BankPaymentAccount;
use App\Models\ProgramBankDetails;
use App\Models\ProgramCompanyRole;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\CompanyRequest;
use App\Http\Resources\CbsTransactionResource;
use App\Models\PaymentRequestAccount;
use App\Notifications\ChangesApproval;
use App\Notifications\CompanyCreation;
use App\Notifications\CompanyUpdation;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\OdAccountsResource;
use App\Imports\CompaniesImport;
use App\Models\AuthorizationMatrixRule;
use App\Models\BankDocument;
use App\Models\City;
use App\Models\CompanyAuthorizationGroup;
use App\Notifications\CompanyUserMapping;
use Illuminate\Support\Facades\Validator;
use App\Models\CompanyAuthorizationMatrix;
use App\Models\CompanyRelationshipManager;
use App\Models\CompanyUploadReport;
use App\Models\ProgramVendorConfiguration;
use App\Models\CompanyUserAuthorizationGroup;
use App\Models\Location;
use App\Models\ProgramVendorBankDetail;
use App\Models\ProposedConfigurationChange;
use App\Models\UserChange;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class CompaniesController extends Controller
{
  public function index(Bank $bank)
  {
    $companies = Company::where('bank_id', $bank->id)->count();

    return view('content.bank.companies.index', [
      'bank' => $bank,
      'companies' => $companies,
    ]);
  }

  public function opportunitiesView(Bank $bank)
  {
    $pipeline_companies = Company::where('pipeline_id', '!=', null)
      ->where('bank_id', $bank->id)
      ->pluck('pipeline_id');

    $opportunities = Pipeline::crmApproved()
      ->where('bank_id', $bank->id)
      ->whereHas('uploadedDocuments', function ($query) {
        $query->whereHas('companyDocuments');
      })
      ->whereNotIn('id', $pipeline_companies)
      ->count();

    return view('content.bank.companies.opportunities', [
      'bank' => $bank,
      'opportunities' => $opportunities,
    ]);
  }

  public function companies(Request $request, Bank $bank)
  {
    $name = $request->query('name');
    $status = $request->query('status');
    $branch_code = $request->query('branch_code');
    $approval_status = $request->query('approval_status');
    $type = $request->query('type');
    $per_page = $request->query('per_page');
    $program_role_type = $request->query('program_role_type');

    $companies = Company::withCount('proposedUpdate')
      ->with('pipeline')
      ->where('bank_id', $bank->id)
      ->when($name && $name != '', function ($query) use ($name) {
        $query->where('name', 'LIKE', '%' . $name . '%');
      })
      ->when($branch_code && $branch_code != '', function ($query) use ($branch_code) {
        $query->where('branch_code', 'LIKE', '%' . $branch_code . '%');
      })
      ->when($status && $status != '', function ($query) use ($status) {
        if (collect($status)->contains('active') || collect($status)->contains('inactive')) {
          $query->whereIn('status', $status);
        }
        if (collect($status)->contains('blocked')) {
          $query->where('is_blocked', true)->whereIn('status', $status);
        }
        if (collect($status)->contains('unblocked')) {
          $query->where('is_blocked', false)->whereIn('status', $status);
        }
      })
      ->when($approval_status && $approval_status != '', function ($query) use ($approval_status) {
        $query->where('approval_status', $approval_status);
      })
      ->when($type && $type != '', function ($query) use ($type) {
        $query->where('organization_type', 'LIKE', '%' . $type . '%');
      })
      ->when($program_role_type && $program_role_type != '', function ($query) use ($program_role_type) {
        if (collect($program_role_type)->contains('unassigned')) {
          $query->whereDoesntHave('roles')->orWhereHas('roles', function ($query) use ($program_role_type) {
            $query->whereIn('name', $program_role_type);
          });
        } else {
          $query->whereHas('roles', function ($query) use ($program_role_type) {
            $query->whereIn('name', $program_role_type);
          });
        }
      })
      ->orderBy('proposed_update_count', 'DESC')
      ->orderBy('approval_status', 'ASC')
      ->orderBy('name', 'ASC')
      ->paginate($per_page);

    $companies = CompanyResource::collection($companies)
      ->response()
      ->getData();

    if (request()->wantsJson()) {
      return response()->json(['data' => ['companies' => $companies]], 200);
    }
  }

  public function pendingCompanies(Request $request, Bank $bank)
  {
    $name = $request->query('name');
    $status = $request->query('status');
    $branch_code = $request->query('branch_code');
    $per_page = $request->query('per_page');

    $companies = Company::where('bank_id', $bank->id)
      ->where('approval_status', 'pending')
      ->when($name && $name != '', function ($query) use ($name) {
        $query->where('name', 'LIKE', '%' . $name . '%');
      })
      ->when($branch_code && $branch_code != '', function ($query) use ($branch_code) {
        $query->where('branch_code', 'LIKE', '%' . $branch_code . '%');
      })
      ->when($status && $status != '', function ($query) use ($status) {
        $query->where('status', $status);
      })
      ->orderBy('name', 'ASC')
      ->paginate($per_page);

    $companies = CompanyResource::collection($companies)
      ->response()
      ->getData();

    if (request()->wantsJson()) {
      return response()->json(['data' => ['companies' => $companies]], 200);
    }
  }

  public function opportunities(Request $request, Bank $bank)
  {
    $pipeline_companies = Company::where('pipeline_id', '!=', null)
      ->where('bank_id', $bank->id)
      ->pluck('pipeline_id');

    $name = $request->query('name');
    $product = $request->query('product');
    $department = $request->query('department');
    $per_page = $request->query('per_page');

    $pending_companies = Pipeline::crmApproved()
      ->where('bank_id', $bank->id)
      ->whereHas('uploadedDocuments', function ($query) {
        $query->whereHas('companyDocuments');
      })
      ->when($name && $name != '', function ($query) use ($name) {
        $query->where('company', 'LIKE', '%' . $name . '%');
      })
      ->when($product && $product != '', function ($query) use ($product) {
        $query->where('product', $product);
      })
      ->when($department && $department != '', function ($query) use ($department) {
        $query->where('region', 'LIKE', '%' . $department . '%');
      })
      ->whereNotIn('id', $pipeline_companies)
      ->orderBy('company', 'ASC')
      ->paginate($per_page);

    if (request()->wantsJson()) {
      return response()->json(['data' => ['pending_companies' => $pending_companies]], 200);
    }
  }

  public function companyUsers(Bank $bank, Company $company, Request $request)
  {
    $search = $request->query('search');
    $per_page = $request->query('per_page');

    $company_users = $company->users->pluck('id');

    $company_roles = $company->roles->pluck('name')->map(function ($role) {
      if ($role == 'buyer') {
        $role_name = Str::title('anchor');
      } else {
        $role_name = Str::title($role);
      }
      return $role_name;
    });
    $permission_data = PermissionData::whereIn('RoleTypeName', $company_roles)->pluck('RoleName');

    $users = User::with([
      'changes',
      'roles' => function ($query) use ($permission_data) {
        $query->whereIn('name', $permission_data);
      },
      'authorizationGroups' => function ($query) use ($company) {
        $query->with('programType')->where('company_authorization_groups.company_id', $company->id);
      },
      'mappedCompanies' => function ($query) use ($company) {
        $query->with('roles')->where('companies.id', $company->id);
      },
    ])
      ->whereIn('id', $company_users)
      ->when($search && $search != '', function ($query) use ($search) {
        $query->where(function ($query) use ($search) {
          $query
            ->where('name', 'LIKE', '%' . $search . '%')
            ->orWhere('phone_number', 'LIKE', '%' . $search . '%')
            ->orWhere('email', 'LIKE', '%' . $search . '%');
        });
      })
      ->paginate($per_page);

    foreach ($users as $user) {
      $company_user_id = CompanyUser::where('user_id', $user->id)
        ->where('company_id', $company->id)
        ->first();

      $proposed_config = ProposedConfigurationChange::where([
        'modeable_type' => Company::class,
        'modeable_id' => $company->id,
        'configurable_type' => CompanyUser::class,
        'configurable_id' => $company_user_id->id,
      ])->first();

      if ($proposed_config) {
        $user['has_pending_config'] = true;
      } else {
        $user['has_pending_config'] = false;
      }

      if ($proposed_config && $proposed_config->user_id === auth()->id()) {
        $user['is_current_user'] = true;
      } else {
        $user['is_current_user'] = false;
      }
    }

    if (request()->wantsJson()) {
      return response()->json(['users' => $users], 200);
    }
  }

  public function addUser(Bank $bank, Company $company, string $mode)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Add/Edit Users')
    ) {
      toastr()->error('', 'You do not have the permission to perform this action');
      return back();
    }

    $users = [];

    $company->load('roles');

    $company_roles = $company->roles->pluck('name')->map(function ($role) {
      if ($role == 'buyer') {
        $role_name = Str::title('anchor');
      } else {
        $role_name = Str::title($role);
      }
      return $role_name;
    });

    // Get Roles
    $roles = PermissionData::whereIn('RoleTypeName', $company_roles)
      ->where(function ($query) use ($bank) {
        $query->where('bank_id', $bank->id)->orWhere('bank_id', null);
      })
      ->select('id', 'RoleName')
      ->where('status', 'approved')
      ->get();

    if ($mode == 'map') {
      $company_users = $company->users->pluck('id')->toArray();
      // $company_programs = ProgramCompanyRole::where('company_id', $company->id)->pluck('program_id');
      // foreach ($company_programs as $company_program) {
      //   $company_ids = ProgramCompanyRole::where('program_id', $company_program)
      //     ->where('company_id', '!=', $company->id)
      //     ->pluck('company_id');
      //   $c_users = CompanyUser::whereIn('company_id', $company_ids)->pluck('user_id');
      //   foreach ($c_users as $c_user) {
      //     array_push($company_users, $c_user);
      //   }
      // }
      $bank_users = BankUser::pluck('user_id');

      $users = User::whereNotIn('id', $company_users)
        ->whereNotIn('id', $bank_users)
        ->whereNotIn('module', ['CRM', 'Admin', 'Bank'])
        ->get();
    }

    $authorization_groups = CompanyAuthorizationGroup::where('company_id', $company->id)
      ->whereHas('programType', fn($query) => $query->where('name', Program::VENDOR_FINANCING))
      ->get();
    $dealer_financing_authorization_groups = CompanyAuthorizationGroup::where('company_id', $company->id)
      ->whereHas('programType', fn($query) => $query->where('name', Program::DEALER_FINANCING))
      ->get();

    $countries = json_decode(file_get_contents(public_path('assets/country-codes.json')));
    $notification_channels = ['email' => 'Email', 'sms' => 'SMS', 'email_and_sms' => 'Email and SMS'];
    return view('content.bank.companies.map-user', [
      'bank' => $bank,
      'company' => $company,
      'users' => $users,
      'mode' => $mode,
      'roles' => $roles,
      'authorization_groups' => $authorization_groups,
      'dealer_financing_authorization_groups' => $dealer_financing_authorization_groups,
      'countries' => $countries,
      'notification_channels' => $notification_channels,
    ]);
  }

  public function storeUser(Request $request, Bank $bank, Company $company)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Add/Edit Users')
    ) {
      toastr()->error('', 'You do not have the permission to perform this action');
      return back();
    }

    // Validator for unique phone numbers when request has country code
    Validator::extend('unique_phone_number', function ($attribute, $value, $parameters, $validator) {
      return !User::where('phone_number', $parameters[0] . '' . substr($value, -9))->exists();
    });

    $request->validate([
      'email' => ['required', 'email'],
      'name' => ['required'],
      'phone_number' => ['required'],
      'country_code' => ['required_if:mode,create'],
      'role' => ['required'],
      'mode' => ['required'],
    ]);

    if ($request->mode == 'create') {
      $request->validate(
        [
          'email' => 'unique:users,email',
          'country_code' => ['required_with:phone_number'],
          'phone_number' => ['required', 'unique_phone_number:' . $request->country_code], // Validation extension for unique phone number
        ],
        [
          'phone_number.unique_phone_number' => 'Phone number is already in use',
          'email.unique' => 'Email is already in use',
        ]
      );
    }

    $user = User::where([
      'email' => $request->email,
    ])->first();

    $bank_users = BankUser::whereNotIn('user_id', [auth()->id()])
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where('name', 'Add/Edit Users');
          });
        });
      })
      ->where('bank_id', $bank->id)
      ->get();

    $link = [];

    // Get latest company mapping
    $program_company_role = ProgramCompanyRole::where('company_id', $company->id)
      ->orderBy('created_at', 'DESC')
      ->first();

    $role = ProgramRole::find($program_company_role->role_id);

    if ($user) {
      $user->update([
        'module' => 'company',
      ]);
      $company_users = [];
      $company_programs = ProgramCompanyRole::where('company_id', $company->id)->pluck('program_id');
      foreach ($company_programs as $company_program) {
        $company_ids = ProgramCompanyRole::where('program_id', $company_program)
          ->where('company_id', '!=', $company->id)
          ->pluck('company_id');
        $c_users = CompanyUser::whereIn('company_id', $company_ids)->pluck('user_id');
        foreach ($c_users as $c_user) {
          array_push($company_users, $c_user);
        }
      }

      $users = User::whereIn('id', $company_users)
        ->where('email', $user->email)
        ->first();

      // if ($users) {
      //   toastr()->error('', 'Cannot Map User to the company. User exists in another company within the same program');
      //   return back();
      // }

      if ($request->has('role') && !empty($request->role)) {
        $permission_data = PermissionData::find($request->role);

        // Assign Role
        $role = Role::where('name', $permission_data->RoleName)
          ->where('guard_name', 'web')
          ->first();

        if ($role) {
          $user->assignRole($role);
        }
      }

      if ($request->has('authorization_group_id') && !empty($request->authorization_group_id)) {
        $authorization_group = CompanyAuthorizationGroup::find($request->authorization_group_id);
        CompanyUserAuthorizationGroup::updateOrCreate(
          [
            'user_id' => $user->id,
            'company_id' => $company->id ?? null,
            'program_type_id' => $authorization_group->program_type_id,
          ],
          [
            'group_id' => $request->authorization_group_id,
          ]
        );
      }

      if (
        $request->has('dealer_financing_authorization_group_id') &&
        !empty($request->dealer_financing_authorization_group_id)
      ) {
        $authorization_group = CompanyAuthorizationGroup::find($request->dealer_financing_authorization_group_id);
        CompanyUserAuthorizationGroup::updateOrCreate(
          [
            'user_id' => $user->id,
            'company_id' => $company->id,
            'program_type_id' => $authorization_group->program_type_id,
          ],
          [
            'group_id' => $request->dealer_financing_authorization_group_id,
          ]
        );
      }

      $company_user = CompanyUser::firstOrCreate([
        'company_id' => $company->id,
        'user_id' => $user->id,
      ]);

      if ($bank_users->count() > 0) {
        $company_user->update([
          'active' => false,
        ]);

        ProposedConfigurationChange::create([
          'user_id' => auth()->id(),
          'modeable_type' => Company::class,
          'modeable_id' => $company->id,
          'configurable_type' => CompanyUser::class,
          'configurable_id' => $company_user->id,
          'old_value' => 0,
          'new_value' => 1,
          'field' => 'active',
          'description' => 'Mapped User ' . $user->name . 'to company ' . $company->name,
        ]);
        $message = 'User mapped. Awaiting approval.';
      } else {
        $user->save();
        $message = 'User mapped successfully';
      }

      $link['Dashboard'] = URL::temporarySignedRoute('auth.reset-password', now()->addHours(24), ['id' => $user->id]);

      SendMail::dispatchAfterResponse($user->email, 'MappedToCompany', [
        'type' => 'Company',
        'data' => ['company' => $company->name, 'name' => $user->name, 'links' => $link],
      ]);
    } else {
      $password = Hash::make('Secret!');
      $phone_number = strlen($request->phone_number) <= 9 ? $request->phone_number : substr($request->phone_number, -9);
      $phone_number = $request->country_code . '' . $phone_number;
      $user = User::create([
        'email' => $request->email,
        'phone_number' => $phone_number,
        'name' => $request->name,
        'password' => $password,
        'receive_notifications' => $request->receive_notifications,
        'module' => 'company',
      ]);

      if ($request->has('role') && !empty($request->role)) {
        $permission_data = PermissionData::find($request->role);

        // Assign Role
        $role = Role::where('name', $permission_data->RoleName)
          ->where('guard_name', 'web')
          ->first();

        if ($role) {
          $user->assignRole($role);
        }
      }

      if ($request->has('authorization_group_id') && !empty($request->authorization_group_id)) {
        $authorization_group = CompanyAuthorizationGroup::find($request->authorization_group_id);
        CompanyUserAuthorizationGroup::updateOrCreate(
          [
            'user_id' => $user->id,
            'company_id' => $company->id,
            'program_type_id' => $authorization_group->program_type_id,
          ],
          [
            'group_id' => $request->authorization_group_id,
          ]
        );
      }

      if (
        $request->has('dealer_financing_authorization_group_id') &&
        !empty($request->dealer_financing_authorization_group_id)
      ) {
        $authorization_group = CompanyAuthorizationGroup::find($request->dealer_financing_authorization_group_id);
        CompanyUserAuthorizationGroup::updateOrCreate(
          [
            'user_id' => $user->id,
            'company_id' => $company->id,
            'program_type_id' => $authorization_group->program_type_id,
          ],
          [
            'group_id' => $request->dealer_financing_authorization_group_id,
          ]
        );
      }

      // $link = URL::temporarySignedRoute('auth.reset-password', now()->addHour(), ['id' => $user->id]);

      // SendMail::dispatchAfterResponse($user->email, 'MappedToCompany', [
      //   'type' => 'Company',
      //   'data' => [
      //     'company' => $company->name,
      //     'name' => $user->name,
      //     'email' => $user->email,
      //     'password' => $password,
      //     'links' => $link,
      //   ],
      // ]);

      $company_user = CompanyUser::firstOrCreate([
        'company_id' => $company->id,
        'user_id' => $user->id,
      ]);

      if ($bank_users->count() > 0) {
        $company_user->update([
          'active' => false,
        ]);

        ProposedConfigurationChange::create([
          'user_id' => auth()->id(),
          'modeable_type' => Company::class,
          'modeable_id' => $company->id,
          'configurable_type' => CompanyUser::class,
          'configurable_id' => $company_user->id,
          'old_value' => 0,
          'new_value' => 1,
          'field' => 'active',
          'description' => 'Added new user ' . $user->name,
        ]);
        $message = 'User added. Awaiting approval.';
      } else {
        $user->save();
        $message = 'User added successfully';
      }
    }

    foreach ($bank_users as $bank_user) {
      SendMail::dispatch($bank_user->user->email, 'CompanyUserCreated', [
        'user_id' => $user->id,
        'company_id' => $company->id,
        'user_name' => auth()->user()->name,
      ])->afterResponse();
      // $bank_user->user->notify(new CompanyUserMapping($user, $company));
    }

    activity($bank->id)
      ->causedBy(auth()->user())
      ->performedOn($company)
      ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
      ->log('mapped user ' . $user->name . ' to ');

    toastr()->success('', $message);

    return redirect()->route('companies.show', ['bank' => $bank, 'company' => $company]);
  }

  public function editUser(Bank $bank, Company $company, User $user)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Add/Edit Users')
    ) {
      toastr()->error('', 'You do not have the permission to perform this action');
      return back();
    }

    // Check if user can edit other users
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Add/Edit Users')
    ) {
      toastr()->error('', 'You don\'t have the permission to perform this action');
      return back();
    }

    // Check if user has pending changes
    if ($user->changes()->exists()) {
      toastr()->error('', 'The user has pending changes awaiting approval');
      return back();
    }

    $company_roles = $company->roles->pluck('name')->map(function ($role) {
      if ($role == 'buyer') {
        $role_name = Str::title('anchor');
      } else {
        $role_name = Str::title($role);
      }
      return $role_name;
    });

    // Get Roles
    $roles = PermissionData::whereIn('RoleTypeName', $company_roles)
      ->where(function ($query) use ($bank) {
        $query->where('bank_id', $bank->id)->orWhere('bank_id', null);
      })
      ->select('id', 'RoleName')
      ->where('status', 'approved')
      ->get();

    $permission_data = PermissionData::whereIn('RoleTypeName', $company_roles)->pluck('RoleName');

    $user->load([
      'roles' => function ($query) use ($permission_data) {
        $query->whereIn('name', $permission_data);
      },
    ]);

    $authorization_groups = CompanyAuthorizationGroup::where('company_id', $company->id)
      ->whereHas('programType', fn($query) => $query->where('name', Program::VENDOR_FINANCING))
      ->get();
    $dealer_financing_authorization_groups = CompanyAuthorizationGroup::where('company_id', $company->id)
      ->whereHas('programType', fn($query) => $query->where('name', 'Dealer Financing'))
      ->get();
    $notification_channels = ['email' => 'Email', 'sms' => 'SMS', 'email_and_sms' => 'Email and SMS'];
    return view(
      'content.bank.companies.edit-user',
      compact(
        'bank',
        'user',
        'company',
        'roles',
        'authorization_groups',
        'dealer_financing_authorization_groups',
        'notification_channels'
      )
    );
  }

  public function updateUser(Request $request, Bank $bank, Company $company, User $user)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Add/Edit Users')
    ) {
      toastr()->error('', 'You do not have the permission to perform this action');
      return back();
    }

    if ($user->roles->count() <= 0) {
      $required = ['required'];
    } else {
      $required = [];
    }

    $request->validate([
      'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
      'name' => ['required'],
      'phone_number' => ['required', 'min:13', 'starts_with:+', Rule::unique('users')->ignore($user->id)],
      'role' => $required,
    ]);

    $bank_users = BankUser::whereNotIn('user_id', [auth()->id(), $user->id])
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where('name', 'Add/Edit Users');
          });
        });
      })
      ->where('bank_id', $bank->id)
      ->get();

    $update_data = [];

    UserChange::where('user_id', $user->id)->delete();

    $message = '';

    if ($bank_users->count() <= 0) {
      $user->update([
        'name' => $request->name,
        'email' => $request->email,
        'phone_number' => $request->phone_number,
        'receive_notifications' => $request->receive_notifications,
      ]);

      if ($request->has('role') && !empty($request->role)) {
        $permission_data = PermissionData::find($request->role);

        // Assign Role
        $role = Role::where('name', $permission_data->RoleName)
          ->where('guard_name', 'web')
          ->first();

        if ($role) {
          $user->assignRole($role);
        }
      }

      if ($request->has('authorization_group_id') && !empty($request->authorization_group_id)) {
        $authorization_group = CompanyAuthorizationGroup::find($request->authorization_group_id);
        CompanyUserAuthorizationGroup::updateOrCreate(
          [
            'user_id' => $user->id,
            'company_id' => $company->id,
            'program_type_id' => $authorization_group->program_type_id,
          ],
          [
            'group_id' => $request->authorization_group_id,
          ]
        );
      }

      if (
        $request->has('dealer_financing_authorization_group_id') &&
        !empty($request->dealer_financing_authorization_group_id)
      ) {
        $authorization_group = CompanyAuthorizationGroup::find($request->dealer_financing_authorization_group_id);
        CompanyUserAuthorizationGroup::updateOrCreate(
          [
            'user_id' => $user->id,
            'company_id' => $company->id,
            'program_type_id' => $authorization_group->program_type_id,
          ],
          [
            'group_id' => $request->dealer_financing_authorization_group_id,
          ]
        );
      }

      if ($request->has('resend_link')) {
        $link['Dashboard'] = URL::temporarySignedRoute('auth.reset-password', now()->addHour(), ['id' => $user->id]);
        SendMail::dispatchAfterResponse($user->email, 'MappedToCompany', [
          'type' => 'Company',
          'data' => ['company' => $company->name, 'name' => $user->name, 'links' => $link],
        ]);
      }
    } else {
      $user->email = $request->email;
      $user->name = $request->name;
      $user->phone_number = $request->phone_number;
      $user->receive_notifications = $request->receive_notifications;
      $update_data['User'] = $user->getDirty();
      if ($request->has('role') && !empty($request->role)) {
        $permission_data = PermissionData::find($request->role)->RoleName;

        // Assign Role
        $role = Role::where('name', $permission_data)
          ->where('guard_name', 'web')
          ->first();

        if ($role) {
          $update_data['Role'] = $permission_data;
        }
      }

      if ($request->has('authorization_group_id') && !empty($request->authorization_group_id)) {
        $authorization_group = CompanyAuthorizationGroup::find($request->authorization_group_id);
        $update_data['Authorization Group'] = [
          'user_id' => $user->id,
          'company_id' => $company->id,
          'program_type_id' => $authorization_group->program_type_id,
          'group_id' => $request->authorization_group_id,
          'name' => $authorization_group->name,
        ];
      }

      if (
        $request->has('dealer_financing_authorization_group_id') &&
        !empty($request->dealer_financing_authorization_group_id)
      ) {
        $authorization_group = CompanyAuthorizationGroup::find($request->dealer_financing_authorization_group_id);
        $update_data['Dealer Authorization Group'] = [
          'user_id' => $user->id,
          'company_id' => $company->id,
          'program_type_id' => $authorization_group->program_type_id,
          'group_id' => $request->dealer_financing_authorization_group_id,
          'name' => $authorization_group->name,
        ];
      }

      if ($request->has('resend_link')) {
        $update_data['Resend Link'] = [
          'company_id' => $company->id,
        ];
      }
    }

    if ($bank_users->count() > 0) {
      UserChange::create([
        'user_id' => $user->id,
        'created_by' => auth()->id(),
        'changes' => $update_data,
      ]);
      $message = 'User edited. Awaiting approval.';

      foreach ($bank_users as $bank_user) {
        SendMail::dispatch($bank_user->user->email, 'CompanyUserChanged', [
          'user_id' => $user->id,
          'company_id' => $company->id,
          'user_name' => auth()->user()->name,
        ])->afterResponse();
      }
    } else {
      $user->save();
      $message = 'User edited successfully';
    }

    CompanyUser::firstOrCreate([
      'company_id' => $company->id,
      'user_id' => $user->id,
    ]);

    activity($bank->id)
      ->causedBy(auth()->user())
      ->performedOn($user)
      ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
      ->log('edited user');

    toastr()->success('', $message);

    return redirect()->route('companies.show', ['bank' => $bank, 'company' => $company]);
  }

  public function authorizationGroups(Bank $bank, Company $company)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Manage Authorization Group')
    ) {
      toastr()->error('', 'You do not have the permission to perform this action');
      return back();
    }

    return view('content.bank.companies.authorization-groups', ['bank' => $bank, 'company' => $company]);
  }

  public function authorizationGroupsData(Bank $bank, Company $company, Request $request)
  {
    $per_page = $request->query('per_page');

    $authorization_groups = CompanyAuthorizationGroup::with('programType')
      ->where('company_id', $company->id)
      ->paginate($per_page);

    $program_types = ProgramType::all();

    return response()->json(['authorization_groups' => $authorization_groups, 'program_types' => $program_types]);
  }

  public function storeAuthorizationGroup(Request $request, Bank $bank, Company $company)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Manage Authorization Group')
    ) {
      toastr()->error('', 'You do not have the permission to perform this action');
      return back();
    }

    $validator = Validator::make($request->all(), [
      'name' => ['required'],
      'level' => ['required'],
      'program_type_id' => ['required'],
      'status' => ['required'],
    ]);

    if ($validator->fails()) {
      return response()->json($validator->messages(), 422);
    }

    $group = CompanyAuthorizationGroup::where('company_id', $company->id)
      ->where('name', $request->name)
      ->first();
    if ($group) {
      return response()->json(['message' => 'Authorization group with the same name is alread set'], 422);
    }

    CompanyAuthorizationGroup::create([
      'company_id' => $company->id,
      'name' => $request->name,
      'level' => $request->level,
      'program_type_id' => $request->program_type_id,
      'status' => $request->status,
    ]);

    return response()->json('Authorization group created successfully');
  }

  public function updateAuthorizationGroup(Request $request, Bank $bank, Company $company)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Manage Authorization Group')
    ) {
      toastr()->error('', 'You do not have the permission to perform this action');
      return back();
    }

    $validator = Validator::make($request->all(), [
      'group_id' => ['required'],
      'name' => ['required'],
      'level' => ['required'],
      'program_type_id' => ['required'],
      'status' => ['required'],
    ]);

    if ($validator->fails()) {
      return response()->json($validator->messages(), 422);
    }

    $group = CompanyAuthorizationGroup::where('company_id', $company->id)
      ->where('name', $request->name)
      ->where('id', '!=', $request->group_id)
      ->first();
    if ($group) {
      return response()->json(['message' => 'Authorization group with the same name is alread set'], 422);
    }

    $group = CompanyAuthorizationGroup::find($request->group_id);

    $group->update([
      'name' => $request->name,
      'level' => $request->level,
      'program_type_id' => $request->program_type_id,
      'status' => $request->status,
    ]);

    return response()->json('Authorization group created successfully');
  }

  public function authorizationMatrices(Bank $bank, Company $company)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Manage Authorization Matrix')
    ) {
      toastr()->error('', 'You do not have the permission to perform this action');
      return back();
    }

    return view('content.bank.companies.authorization-matrix', ['bank' => $bank, 'company' => $company]);
  }

  public function authorizationMatricesData(Bank $bank, Company $company, Request $request)
  {
    $per_page = $request->query('per_page');

    $authorization_matrices = CompanyAuthorizationMatrix::with('programType', 'rules')
      ->where('company_id', $company->id)
      ->paginate($per_page);

    $program_types = ProgramType::all();

    $authorization_groups = CompanyAuthorizationGroup::with('programType')
      ->where('company_id', $company->id)
      ->get();

    return response()->json([
      'authorization_matrices' => $authorization_matrices,
      'program_types' => $program_types,
      'authorization_groups' => $authorization_groups,
    ]);
  }

  public function authorizationMatrixByProgramType(Bank $bank, Company $company, ProgramType $program_type)
  {
    $authorization_matrices = CompanyAuthorizationGroup::with('programType')
      ->where('company_id', $company->id)
      ->where('program_type_id', $program_type->id)
      ->get();

    return response()->json($authorization_matrices);
  }

  public function storeAuthorizationMatrix(Request $request, Bank $bank, Company $company)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Manage Authorization Matrix')
    ) {
      toastr()->error('', 'You do not have the permission to perform this action');
      return back();
    }

    $validator = Validator::make($request->all(), [
      'name' => ['required'],
      'program_type_id' => ['required'],
      'status' => ['required'],
      'min_pi_amount' => ['required'],
      'max_pi_amount' => ['required', 'gt:min_pi_amount'],
    ]);

    if ($validator->fails()) {
      return response()->json($validator->messages(), 422);
    }

    $authorization_matrices = CompanyAuthorizationMatrix::where('company_id', $company->id)
      ->where('name', $request->name)
      ->first();
    if (!$authorization_matrices) {
      $authorization_matrices = CompanyAuthorizationMatrix::where('company_id', $company->id)
        // TODO: Check for min and max amounts for the authorization matrix
        ->where('min_pi_amount', $request->min_pi_amount)
        ->where('program_type_id', $request->program_type_id)
        ->first();
    } else {
      return response()->json(['message' => 'Authorization matrix with the same minimum amount is already set'], 422);
    }

    if ($authorization_matrices) {
      return response()->json(['message' => 'Authorization matrix with the same name is alread set'], 422);
    }

    try {
      DB::beginTransaction();

      $matrix = CompanyAuthorizationMatrix::create([
        'company_id' => $company->id,
        'name' => $request->name,
        'program_type_id' => $request->program_type_id,
        'status' => $request->status,
        'min_pi_amount' => $request->min_pi_amount,
        'max_pi_amount' => $request->max_pi_amount,
      ]);

      if ($request->has('groups') && $request->has('rules_min_approvals')) {
        $groups = json_decode($request->groups);
        $rules_min_approvals = json_decode($request->rules_min_approvals);
        $rules_operators = json_decode($request->rules_operators);
        foreach ($groups as $key => $group) {
          if ($key == 0) {
            if (array_key_exists($key, $rules_min_approvals)) {
              AuthorizationMatrixRule::create([
                'group_id' => $group,
                'matrix_id' => $matrix->id,
                'min_approval' => $rules_min_approvals[$key],
              ]);
            }
          } else {
            if (array_key_exists($key, $rules_min_approvals) && array_key_exists($key, $rules_operators)) {
              AuthorizationMatrixRule::create([
                'group_id' => $group,
                'matrix_id' => $matrix->id,
                'operator' => $rules_operators[$key],
                'min_approval' => $rules_min_approvals[$key],
              ]);
            }
          }
        }
      }

      DB::commit();

      return response()->json('Authorization matrix created successfully');
    } catch (\Throwable $th) {
      info($th);
      DB::rollBack();
      return response()->json('Something went wrong', 422);
    }
  }

  public function updateAuthorizationMatrix(Request $request, Bank $bank, Company $company)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Manage Authorization Matrix')
    ) {
      toastr()->error('', 'You do not have the permission to perform this action');
      return back();
    }

    $validator = Validator::make($request->all(), [
      'matrix_id' => ['required'],
      'name' => ['required'],
      'program_type_id' => ['required'],
      'status' => ['required'],
      'min_pi_amount' => ['required'],
      'max_pi_amount' => ['required', 'gt:min_pi_amount'],
    ]);

    if ($validator->fails()) {
      return response()->json($validator->messages(), 422);
    }

    $matrix = CompanyAuthorizationMatrix::find($request->matrix_id);

    $authorization_matrices = CompanyAuthorizationMatrix::where('company_id', $company->id)
      ->where('name', $request->name)
      ->where('id', '!=', $matrix->id)
      ->first();
    if (!$authorization_matrices) {
      $authorization_matrices = CompanyAuthorizationMatrix::where('company_id', $company->id)
        // TODO: Check for min and max amounts for the existing authorization matrix
        ->where('min_pi_amount', $request->min_pi_amount)
        ->where('id', '!=', $matrix->id)
        ->first();
    } else {
      return response()->json(['message' => 'Authorization matrix with the same name is alread set'], 422);
    }

    if ($authorization_matrices) {
      return response()->json(['message' => 'Authorization matrix with the same minimum amount is alread set'], 422);
    }

    try {
      DB::beginTransaction();

      $matrix->update([
        'name' => $request->name,
        'program_type_id' => $request->program_type_id,
        'status' => $request->status,
        'min_pi_amount' => $request->min_pi_amount,
        'max_pi_amount' => $request->max_pi_amount,
      ]);

      if ($request->has('groups') && $request->has('rules_min_approvals')) {
        AuthorizationMatrixRule::where('matrix_id', $matrix->id)->delete();
        $groups = json_decode($request->groups);
        $rules_min_approvals = json_decode($request->rules_min_approvals);
        $rules_operators = json_decode($request->rules_operators);
        foreach ($groups as $key => $group) {
          if ($key == 0) {
            if (array_key_exists($key, $rules_min_approvals)) {
              AuthorizationMatrixRule::create([
                'group_id' => $group,
                'matrix_id' => $matrix->id,
                'min_approval' => $rules_min_approvals[$key],
              ]);
            }
          } else {
            if (array_key_exists($key, $rules_min_approvals) && array_key_exists($key, $rules_operators)) {
              AuthorizationMatrixRule::create([
                'group_id' => $group,
                'matrix_id' => $matrix->id,
                'operator' => $rules_operators[$key],
                'min_approval' => $rules_min_approvals[$key],
              ]);
            }
          }
        }
      }

      DB::commit();

      return response()->json('Authorization matrix updated successfully');
    } catch (\Throwable $th) {
      info($th);
      DB::rollBack();
      return response()->json('Something went wrong', 422);
    }
  }

  public function updateUserStatus(Bank $bank, Company $company, User $user, $status)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Add/Edit Users')
    ) {
      if (request()->wantsJson()) {
        toastr()->error('', 'You do not have the permission to perform this action');
        return back();
      }

      return response()->json('You do not have the permission to perform this action', 403);
    }

    $company_user = CompanyUser::where('company_id', $company->id)
      ->where('user_id', $user->id)
      ->first();

    $proposed_config_update = ProposedConfigurationChange::where([
      'modeable_type' => Company::class,
      'modeable_id' => $company->id,
      'configurable_type' => CompanyUser::class,
      'configurable_id' => $company_user->id,
    ])->first();

    $updated_status = $status === 'active' ? 'inactive to active' : 'active to inactive';

    if ($proposed_config_update) {
      if ($proposed_config_update->old_value === '1') {
        // Change from active to inactive
        $new_status = true;
      } else {
        // Activate the user in the company
        $new_status = false;
      }

      $proposed_config_update->delete();

      $company_user->update([
        'active' => $new_status,
      ]);

      if ($new_status && $user->last_login == null) {
        // Means the user is new and therefore send an new account email
        $password = Str::random(8);

        $link['Dashboard'] = URL::temporarySignedRoute('auth.reset-password', now()->addHours(24), ['id' => $user->id]);

        SendMail::dispatchAfterResponse($user->email, 'MappedToCompany', [
          'type' => 'Company',
          'data' => [
            'company' => $company->name,
            'name' => $user->name,
            'email' => $user->email,
            'password' => $password,
            'links' => $link,
          ],
        ]);

        activity($bank->id)
          ->causedBy(auth()->user())
          ->performedOn($company)
          ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
          ->log('approved mapping of user, ' . $user->name . ', to ');
      }
    } else {
      $bank_users = BankUser::whereNotIn('user_id', [auth()->id()])
        ->whereHas('user', function ($query) {
          $query->whereHas('roles', function ($query) {
            $query->whereHas('permissions', function ($query) {
              $query->where('name', 'Add/Edit Users');
            });
          });
        })
        ->where('bank_id', $bank->id)
        ->get();

      if ($bank_users->count() > 0) {
        $company_user->update([
          'active' => false,
        ]);

        ProposedConfigurationChange::create([
          'user_id' => auth()->id(),
          'modeable_type' => Company::class,
          'modeable_id' => $company->id,
          'configurable_type' => CompanyUser::class,
          'configurable_id' => $company_user->id,
          'old_value' => $status === 'active' ? 1 : 0,
          'new_value' => $status === 'inactive' ? 0 : 1,
          'field' => 'active',
          'description' => 'Update user, ' . $user->name . ', status from ' . $updated_status,
        ]);
      } else {
        $company_user->update([
          'active' => $status == 'active' ? true : false,
        ]);

        activity($bank->id)
          ->causedBy(auth()->user())
          ->performedOn($company)
          ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
          ->log('updated status of user, ' . $user->name . ', from ' . $updated_status . ' in ');
      }
    }

    if (request()->wantsJson()) {
      return response()->json(['user' => $user], 200);
    }
  }

  public function checkCompanyName(Bank $bank, Company $company = null, $name)
  {
    if ($company) {
      $company = Company::where('bank_id', $bank->id)
        ->where('approval_status', 'approved')
        ->where('id', '!=', $company->id)
        ->where('name', $name)
        ->first();
    } else {
      $company = Company::where('bank_id', $bank->id)
        ->where('approval_status', 'approved')
        ->where('name', $name)
        ->first();
    }

    if ($company) {
      return response()->json(['exists' => true], 400);
    }

    return response()->json(['exists' => false], 200);
  }

  public function create(Bank $bank)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Add/Edit Companies')
    ) {
      toastr()->error('', 'You do not have the permission to perform this action');
      return back();
    }

    // Check if user can edit other users
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Add/Edit Companies')
    ) {
      toastr()->error('', 'You don\'t have the permission to perform this action');
      return back();
    }

    $bank->load('users');
    $pipeline = request()->query('pipeline');
    $company = request()->query('company');

    $company_name = '';
    $company_registration_number = '';
    $kra_pin = '';

    // If pipeline, get documents and extract data
    $parser = new \Smalot\PdfParser\Parser();

    // $file = $parser->parseFile(public_path('storage\COMPANYKRAPIN.pdf'));
    // $document_text = $file->getText();
    // $document_text = preg_replace("/\s+/", " ", str_replace(["  ", "<>", ".."], " ", $document_text));
    // $text_array = explode(' ', $document_text);

    if ($pipeline) {
      $pipeline = Pipeline::find($pipeline);
      try {
        foreach ($pipeline->uploadedDocuments as $uploaded_documents) {
          foreach ($uploaded_documents->companyDocuments as $key => $document) {
            // Get CR12 if uploaded
            $pdf = $parser->parseFile(config('app.backend_url') . '/storage/' . $document->path);
            $document_text = $pdf->getText();

            if ($document_text != '') {
              // Get company name
              $document_text = preg_replace('/\s+/', ' ', str_replace(['  ', '<>', '..'], ' ', $document_text));
              if ($this->get_string_between($document_text, 'COMPANY', 'COMPANY NUMBER') != '') {
                $company_name = trim($this->get_string_between($document_text, 'COMPANY', 'COMPANY NUMBER'));
              }

              // Get Company Registration Number
              if ($this->get_string_between($document_text, 'COMPANY NUMBER', 'NOMINAL SHARE CAPITAL') != '') {
                $company_registration_number = trim(
                  $this->get_string_between($document_text, 'COMPANY NUMBER', 'NOMINAL SHARE CAPITAL')
                );
              }

              $date_and_kra = trim($this->get_string_between($document_text, 'Number', 'For'));

              // Get KRA PIN
              $text_array = explode(' ', $document_text);
              if (count($text_array) > 0) {
                if (count(explode(' ', $date_and_kra)) > 1) {
                  $kra_pin = explode(' ', $date_and_kra)[1];
                }
                if ($kra_pin == '') {
                  foreach ($text_array as $key => $value) {
                    if (Str::startsWith($value, 'P0')) {
                      $kra_pin = $value;
                    } elseif (Str::startsWith($value, 'A00')) {
                      $kra_pin = $value;
                    }
                  }
                }
              }
            }
          }
        }
      } catch (\Throwable $th) {
        // info($th);
      }

      if ($company_name == '') {
        $company_name = $pipeline->company;
      }
    } else {
      $pipeline = null;
    }

    if ($company) {
      $company = Company::find($company);
      if ($company) {
        $company_name = $company->name;
        $kra_pin = $company->kra_pin;
        $company_registration_number = $company->business_identification_number;
      }
    } else {
      $company = null;
    }

    $drafts = Company::where('bank_id', $bank->id)
      ->where('publisher_type', User::class)
      ->where('publisher_id', auth()->id())
      ->onlyDrafts()
      ->get();

    $banks = BankMasterList::all();

    $branches = BankBranch::where('bank_id', $bank->id)->get();

    $industries = Industry::where('status', 'active')->get();

    $users = BankUser::where('bank_id', $bank->id)
      ->where('active', true)
      ->pluck('user_id');

    $bank_users = User::whereIn('id', $users)->get();

    $locations = City::where('status', 'active')->get();

    $latest_id =
      Company::where('bank_id', $bank->id)
        ->latest()
        ->where('is_published', true)
        ->first()?->id + 1;

    if (!$latest_id) {
      $latest_id = 1;
    }

    return view('content.bank.companies.create', [
      'pipeline' => $pipeline,
      'bank' => $bank,
      'bank_users' => $bank_users,
      'company_name' => $company_name,
      'kra_pin' => $kra_pin,
      'company_registration_number' => $company_registration_number,
      'company' => $company,
      'banks' => $banks,
      'drafts' => $drafts,
      'branches' => $branches,
      'industries' => $industries,
      'locations' => $locations,
      'latest_id' => $latest_id,
    ]);
  }

  public function get_string_between($string, $start, $end)
  {
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) {
      return '';
    }
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
  }

  public function drafts(Bank $bank)
  {
    $drafts = Company::where('bank_id', $bank->id)
      ->where('publisher_type', User::class)
      ->onlyDrafts()
      ->latest()
      ->get();

    return view('content.bank.companies.drafts', ['bank' => $bank, 'drafts' => $drafts]);
  }

  public function storeDraft(Request $request, Bank $bank)
  {
    Company::where(['bank_id' => $bank->id, 'name' => $request->name, 'kra_pin' => $request->kra_pin])
      ->onlyDrafts()
      ->delete();
    $company = Company::createDraft([
      'bank_id' => $bank->id,
      'name' => $request->name,
      'top_level_borrower_limit' => Str::replace(',', '', $request->top_level_borrower_limit),
      'limit_expiry_date' => Carbon::parse($request->limit_expiry_date)->format('Y-m-d'),
      'unique_identification_number' => $request->unique_identification_number,
      'branch_code' => $request->branch_code,
      'cif' => $request->cif,
      'business_identification_number' => $request->business_identification_number,
      'organization_type' => $request->organization_type,
      'business_segment' => $request->business_segment,
      'customer_type' => $request->customer_type,
      'kra_pin' => $request->kra_pin,
      'city' => $request->city,
      'postal_code' => $request->postal_code,
      'address' => $request->address,
      'pipeline_id' => $request->pipeline_id,
      'status' => 'inactive',
    ]);

    $company = Company::where(['bank_id' => $bank->id, 'name' => $request->name, 'kra_pin' => $request->kra_pin])
      ->where('publisher_type', User::class)
      ->where('publisher_id', auth()->id())
      ->current()
      ->first();

    if (
      $request->has('manager_names') &&
      count($request->manager_names) > 0 &&
      collect($request->manager_names)->first() != null &&
      $request->has('manager_emails') &&
      count($request->manager_emails) > 0 &&
      collect($request->manager_emails)->first() != null
    ) {
      foreach ($request->manager_names as $key => $manager_name) {
        if (array_key_exists($key, $request->manager_emails)) {
          CompanyRelationshipManager::create([
            'company_id' => $company->id,
            'name' => $manager_name,
            'email' => $request->manager_emails[$key],
            'phone_number' => array_key_exists($key, $request->manager_phone_numbers)
              ? $request->manager_phone_numbers[$key]
              : null,
          ]);
        }
      }
    }

    $drafts = Company::where('bank_id', $bank->id)
      ->where('publisher_type', User::class)
      ->where('publisher_id', auth()->id())
      ->onlyDrafts()
      ->count();

    return response()->json(['message' => 'Draft saved successfully', 'revisions' => $drafts]);
  }

  public function deleteDraft(Bank $bank, Company $company)
  {
    DB::table('companies')
      ->where('id', $company->id)
      ->delete();

    toastr()->success('', 'Draft deleted successfully');

    return redirect()->back();
  }

  public function store(Request $request, Bank $bank)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Add/Edit Companies')
    ) {
      toastr()->error('', 'You do not have the permission to perform this action');
      return back();
    }

    $bank_users = BankUser::where('bank_id', $bank->id)
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

    $company_names = $bank->companies->where('approval_status', 'approved')->pluck('name');
    $kra_pins = $bank->companies->where('approval_status', 'approved')->pluck('kra_pin');
    $unique_identification_numbers = $bank->companies
      ->where('approval_status', 'approved')
      ->pluck('unique_identification_number');

    $request->validate(
      [
        'name' => ['required', 'not_in:' . $company_names],
        'top_level_borrower_limit' => ['required'],
        'limit_expiry_date' => ['required'],
        'kra_pin' => ['required', 'string', 'max:20', 'not_in:' . $kra_pins],
        'unique_identification_number' => ['required', 'not_in:' . $unique_identification_numbers],
        'customer_type' => ['required', 'string'],
        'city' => ['required', 'string'],
        'postal_code' => ['required', 'string'],
        'address' => ['required', 'string'],
      ],
      [
        'name.required' => 'Enter Company Name',
        'name.not_in' => 'The entered name is already in use',
        'kra_pin.not_in' => 'KRA PIN is already in use',
        'unique_identification_number.not_in' => 'The Unique Identification Number is already in use',
        'top_level_borrower_limit.required' => 'Enter the Top Level Limit',
      ]
    );

    try {
      DB::beginTransaction();

      $latest_id =
        Company::where('bank_id', $bank->id)
          ->orderBy('company_bank_id', 'DESC')
          ->where('is_published', true)
          ->first()?->company_bank_id + 1;
      if (!$latest_id) {
        $latest_id = 1;
      }

      $company = $bank->companies()->create([
        'company_bank_id' => $latest_id,
        'name' => $request->name,
        'top_level_borrower_limit' => Str::replace(',', '', $request->top_level_borrower_limit),
        'limit_expiry_date' => Carbon::parse($request->limit_expiry_date)->format('Y-m-d'),
        'unique_identification_number' => $request->unique_identification_number,
        'branch_code' => $request->branch_code,
        'cif' => $request->cif,
        'business_identification_number' => $request->business_identification_number,
        'organization_type' => $request->organization_type,
        'business_segment' => $request->business_segment,
        'customer_type' => $request->customer_type,
        'kra_pin' => $request->kra_pin,
        'city' => $request->city,
        'postal_code' => $request->postal_code,
        'address' => $request->address,
        'pipeline_id' => $request->pipeline_id,
        'approval_status' => 'pending',
        'status' => 'inactive',
        'created_by' => auth()->id(),
        'is_published' => true,
      ]);

      if ($request->hasFile('company_logo')) {
        $company->update([
          'logo' => pathinfo($request->company_logo->store('logo', 'company'), PATHINFO_BASENAME),
        ]);
      }

      if ($request->has('manager_emails') && count($request->manager_emails) > 0) {
        foreach ($request->manager_emails as $key => $manager_email) {
          $user_details = User::where('email', $manager_email)->first();
          if ($user_details) {
            CompanyRelationshipManager::firstOrCreate(
              [
                'company_id' => $company->id,
                'email' => $user_details->email,
              ],
              [
                'name' => $user_details->name,
                'phone_number' => $user_details->phone_number,
              ]
            );
          }
        }
      }

      if (
        $request->has('company_names_as_per_banks') &&
        count($request->company_names_as_per_banks) > 0 &&
        collect($request->company_names_as_per_banks)->first() != null &&
        $request->has('account_numbers') &&
        count($request->account_numbers) > 0 &&
        collect($request->account_numbers)->first() != null &&
        $request->has('bank_names') &&
        count($request->bank_names) > 0 &&
        collect($request->bank_names)->first() != null
      ) {
        foreach ($request->company_names_as_per_banks as $key => $value) {
          $company->bankDetails()->create([
            'name_as_per_bank' => $value,
            'account_number' => array_key_exists($key, $request->account_numbers)
              ? $request->account_numbers[$key]
              : null,
            'bank_name' => array_key_exists($key, $request->bank_names) ? $request->bank_names[$key] : null,
            'branch' => array_key_exists($key, $request->branches) ? $request->branches[$key] : null,
            'swift_code' => array_key_exists($key, $request->swift_codes) ? $request->swift_codes[$key] : null,
            'account_type' => array_key_exists($key, $request->account_types) ? $request->account_types[$key] : null,
          ]);
        }
      }

      if ($request->has('pipeline_id') && !empty($request->pipeline_id)) {
        $pipeline = Pipeline::find($request->pipeline_id);

        if ($pipeline) {
          $pipeline->uploadedDocuments->each(function ($document) use ($company) {
            $document->companyDocuments->each(function ($document) use ($company) {
              // Approve all documents
              $document->update(['status' => 'approved', 'rejected_reason' => null]);
              // Add to company documents
              CompanyDocument::create([
                'company_id' => $company->id,
                'name' => $document->original_name,
                'status' => 'approved',
              ]);
            });
          });

          $previousStage = $pipeline->stage;
          $previousStageUpdatedAt = $pipeline->updated_at;
          $transitionPeriod = $previousStageUpdatedAt->diffInMinutes(now());

          $pipeline->update([
            'stage' => 'Closed',
          ]);

          PipelineStage::create([
            'pipeline_id' => $pipeline->id,
            'initial_stage' => $previousStage,
            'new_stage' => 'Closed',
            'period' => $transitionPeriod,
            'entered_at' => now(),
          ]);

          // Log Activity
          ActivityHelper::logCrmActivity([
            'subject_type' => 'Pipeline Closure',
            'subject_id' => $pipeline->id,
            'stage' => 'Closed',
            'section' => 'Pipeline Update',
            'pipeline_id' => $pipeline->id,
            'user_id' => auth()->id(),
            'description' => 'Converted Pipeline to Company/Pipeline Closure',
            'properties' => $pipeline->toArray(),
            'device_info' => ['ip' => request()->ip(), 'device_info' => request()->userAgent()],
            'ip_address' => $request->ip(),
            'url' => $request->fullUrl(),
            'created_at' => now(),
            'updated_at' => now(),
          ]);

          // Send Email to Pipeline Contact
          SendMail::dispatchAfterResponse($pipeline->email, 'PipelineStageUpdated', [
            'pipeline_id' => $pipeline->id,
            'stage' => 'Closed',
          ]);
          // Send Email to CRM User
          SendMail::dispatchAfterResponse($pipeline->user?->email, 'PipelineStageUpdated', [
            'pipeline_id' => $pipeline->id,
            'stage' => 'Closed',
          ]);
        }
      }

      activity($bank->id)
        ->causedBy(auth()->user())
        ->performedOn($company)
        ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
        ->log('created company');

      DB::commit();

      // Delete Drafts
      DB::table('companies')
        ->where('id', '!=', $company->id)
        ->where('name', $request->name)
        ->where('is_published', false)
        ->delete();

      if ($bank_users->count() > 0) {
        foreach ($bank_users as $bank_user) {
          $bank_user->user->notify(new CompanyCreation($company));
          SendMail::dispatchAfterResponse($bank_user->user->email, 'CompanyCreated', ['company' => $company->id]);
        }
      }

      toastr()->success('', 'Company created successfully');

      return redirect()->route('companies.index', ['bank' => $bank]);
    } catch (\Throwable $th) {
      //throw $th;
      info($th);
      DB::rollBack();

      toastr()->error('', 'Something went wrong while creating the company.');

      return back();
    }
  }

  public function downloadTemplate(Bank $bank)
  {
    if (request()->wantsJson()) {
      return response()->download(public_path('Companies_Template.csv'), 'companies-template.csv');
    }
    return response()->download(public_path('Companies_Template.csv'), 'companies-template.csv');
  }

  public function import(Bank $bank, Request $request)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Add/Edit Companies')
    ) {
      if ($request->wantsJson()) {
        return response()->json(['message' => 'You do not have permission to perform this action'], 403);
      }

      toastr()->error('', 'You do not have the permission to perform this action');
      return back();
    }

    $request->validate([
      'file' => ['required', 'mimetypes:text/plain,text/csv,text/xslx'],
    ]);

    $import = new CompaniesImport($bank);

    Excel::import($import, $request->file('file')->store('public'));

    if ($request->wantsJson()) {
      if ($import->data > 0) {
        return response()->json(
          [
            'message' => 'Companies uploaded successfully',
            'uploaded' => $import->data,
            'total_rows' => collect($import->total_rows)->first() - 1,
          ],
          400
        );
      }

      return response()->json(
        [
          'message' => 'No Companies were uploaded successfully',
          'uploaded' => $import->data,
          'total_rows' => collect($import->total_rows)->first() - 1,
        ],
        400
      );
    }

    toastr()->success('', 'Companies uploaded successfully');

    return back();
  }

  public function downloadErrorReport(Bank $bank)
  {
    $date = now()->format('Y-m-d');

    Excel::store(
      new CompaniesUploadReportExport($bank, 'failed'),
      'Companies_error_report_' . $date . '.xlsx',
      'exports'
    );

    return Storage::disk('exports')->download('Companies_error_report_' . $date . '.xlsx');
  }

  public function viewUploaded(Bank $bank)
  {
    return view('content.bank.companies.uploaded');
  }

  public function uploadedData(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $search = $request->query('search');

    $data = CompanyUploadReport::where('bank_id', $bank->id)
      ->when($search && $search != '', function ($query) use ($search) {
        $query->where('name', 'LIKE', '%' . $search . '%');
      })
      ->latest()
      ->paginate($per_page);

    if ($request->wantsJson()) {
      return response()->json($data, 200);
    }
  }

  public function exportUploadedData(Bank $bank, Request $request)
  {
    $search = $request->query('search');
    $date = now()->format('Y-m-d');

    Excel::store(new CompaniesUploadReportExport($bank, $search), 'Uploaded_Companies_' . $date . '.xlsx', 'exports');

    return Storage::disk('exports')->download('Uploaded_Companies_' . $date . '.xlsx');
  }

  public function show(Bank $bank, Company $company, ProgramVendorConfiguration $program_vendor_configuration = null)
  {
    // Check if user can edit other users
    if (
      !auth()
        ->user()
        ->hasPermissionTo('View Companies')
    ) {
      toastr()->error('', 'You don\'t have the permission to perform this action');
      return back();
    }

    $company->load([
      'documents',
      'pipeline',
      'proposedUpdate',
      'roles',
      'relationshipManagers',
      'bankDetails',
      'programs' => function ($query) use ($program_vendor_configuration) {
        $query->when($program_vendor_configuration, function ($query) use ($program_vendor_configuration) {
          $query->where('program_id', $program_vendor_configuration->program_id);
        });
      },
    ]);

    $bank->load('users');

    if ($program_vendor_configuration) {
      $program_vendor_configuration->load('program.programType', 'program.programCode');
    }

    if (request()->wantsJson()) {
      // Allow for a delay to simulate a real API call
      sleep(1);
      return response()->json(['company' => $company, 'program_vendor_configuration' => $program_vendor_configuration]);
    }

    $program_vendor_configurations = ProgramVendorConfiguration::where('company_id', $company->id)->get();

    return view('content.bank.companies.show', compact('company', 'bank', 'program_vendor_configurations'));
  }

  public function edit(Bank $bank, Company $company)
  {
    // Check if user can edit companies
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Add/Edit Companies')
    ) {
      toastr()->error('', 'You don\'t have the permission to perform this action');
      return back();
    }

    // Check if user has pending changes
    if ($company->proposedUpdate()->exists()) {
      toastr()->error('', 'The company has pending changes awaiting approval');
      return back();
    }

    $bank->load('users');
    $company->load('relationshipManagers', 'bankDetails');
    $banks = BankMasterList::all();
    $locations = City::where('status', 'active')->get();
    $branches = BankBranch::where('bank_id', $bank->id)->get();
    $industries = Industry::where('status', 'active')->get();

    return view(
      'content.bank.companies.edit',
      compact('bank', 'company', 'banks', 'locations', 'branches', 'industries')
    );
  }

  public function update(Request $request, Bank $bank, Company $company)
  {
    $bank_users = BankUser::where('bank_id', $bank->id)
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where('name', 'Company Changes Checker');
          });
        });
      })
      ->where('user_id', '!=', auth()->id())
      ->where('active', true)
      ->get();

    if ($company->approval_status == 'rejected') {
      $company->name = $request->name;
      $company->top_level_borrower_limit = Str::replace(',', '', $request->top_level_borrower_limit);
      $company->limit_expiry_date = Carbon::parse($request->limit_expiry_date)->format('Y-m-d');
      $company->unique_identification_number = $request->unique_identification_number;
      $company->branch_code = $request->branch_code;
      $company->cif = $request->cif;
      $company->business_identification_number = $request->business_identification_number;
      $company->organization_type = $request->organization_type;
      $company->business_segment = $request->business_segment;
      $company->customer_type = $request->customer_type;
      $company->kra_pin = $request->kra_pin;
      $company->city = $request->city;
      $company->postal_code = $request->postal_code;
      $company->address = $request->address;
      $company->approval_status = 'pending';
      $company->created_by = auth()->id();
      $company->save();

      if ($request->hasFile('company_logo')) {
        $company->update([
          'logo' => pathinfo($request->company_logo->store('logo', 'company'), PATHINFO_BASENAME),
        ]);
      }

      CompanyRelationshipManager::where('company_id', $company->id)->delete();

      if (
        $request->has('manager_names') &&
        count($request->manager_names) > 0 &&
        $request->has('manager_emails') &&
        count($request->manager_emails) > 0
      ) {
        foreach ($request->manager_names as $key => $manager_name) {
          if (array_key_exists($key, $request->manager_emails)) {
            CompanyRelationshipManager::create([
              'company_id' => $company->id,
              'name' => $manager_name,
              'email' => $request->manager_emails[$key],
              'phone_number' => array_key_exists($key, $request->manager_phone_numbers)
                ? $request->manager_phone_numbers[$key]
                : null,
            ]);
          }
        }
      }

      $company->bankDetails()->delete();

      if (
        $request->has('company_names_as_per_banks') &&
        count($request->company_names_as_per_banks) > 0 &&
        collect($request->company_names_as_per_banks)->first() != null &&
        $request->has('account_numbers') &&
        count($request->account_numbers) > 0 &&
        collect($request->account_numbers)->first() != null &&
        $request->has('bank_names') &&
        count($request->bank_names) > 0 &&
        collect($request->bank_names)->first() != null
      ) {
        foreach ($request->company_names_as_per_banks as $key => $value) {
          $company->bankDetails()->create([
            'name_as_per_bank' => $value,
            'account_number' => array_key_exists($key, $request->account_numbers)
              ? $request->account_numbers[$key]
              : null,
            'bank_name' => array_key_exists($key, $request->bank_names) ? $request->bank_names[$key] : null,
            'branch' => array_key_exists($key, $request->branches) ? $request->branches[$key] : null,
            'swift_code' => array_key_exists($key, $request->swift_codes) ? $request->swift_codes[$key] : null,
            'account_type' => array_key_exists($key, $request->account_types) ? $request->account_types[$key] : null,
          ]);
        }
      }
    } else {
      $update_data = [];

      CompanyChange::where('company_id', $company->id)->delete();

      $company->name = $request->name;
      $company->top_level_borrower_limit = Str::replace(',', '', $request->top_level_borrower_limit);
      $company->limit_expiry_date = Carbon::parse($request->limit_expiry_date)->format('Y-m-d');
      $company->unique_identification_number = $request->unique_identification_number;
      $company->branch_code = $request->branch_code;
      $company->business_identification_number = $request->business_identification_number;
      $company->organization_type = $request->organization_type;
      $company->business_segment = $request->business_segment;
      $company->customer_type = $request->customer_type;
      $company->cif = $request->cif;
      $company->kra_pin = $request->kra_pin;
      $company->city = $request->city;
      $company->postal_code = $request->postal_code;
      $company->address = $request->address;

      $dirty_count = 0; // Used to determine if changes were made

      if ($bank_users->count() <= 0) {
        $company->save();
      } else {
        if (count($company->getDirty()) > 0) {
          $dirty_count += 1; // Used to determine if changes were made
          $update_data['Company Details'] = $company->getDirty();
        }
      }

      if (
        $request->has('manager_names') &&
        count($request->manager_names) > 0 &&
        collect($request->manager_names)->first() != null &&
        $request->has('manager_emails') &&
        count($request->manager_emails) > 0 &&
        collect($request->manager_emails)->first() != null
      ) {
        foreach ($request->manager_names as $key => $manager_name) {
          if (array_key_exists($key, $request->manager_emails)) {
            if ($request->manager_details[$key] != '-1') {
              $relationship_manager = CompanyRelationshipManager::find($request->manager_details[$key]);
              $relationship_manager->name = $manager_name;
              $relationship_manager->email = $request->manager_emails[$key];
              $relationship_manager->phone_number = array_key_exists($key, $request->manager_phone_numbers)
                ? $request->manager_phone_numbers[$key]
                : null;
              if ($bank_users->count() <= 0) {
                $relationship_manager->save();
              } else {
                if (count($relationship_manager->getDirty()) > 0) {
                  $dirty_count += 1; // Used to determine if changes were made
                  $update_data['Relationship Manager'][$relationship_manager->id] = $relationship_manager->getDirty();
                }
              }
            } else {
              $relationship_manager = new CompanyRelationshipManager();
              $relationship_manager->company_id = $company->id;
              $relationship_manager->name = $manager_name;
              $relationship_manager->email = $request->manager_emails[$key];
              $relationship_manager->phone_number = array_key_exists($key, $request->manager_phone_numbers)
                ? $request->manager_phone_numbers[$key]
                : null;
              if ($bank_users->count() <= 0) {
                $relationship_manager->save();
              } else {
                $dirty_count += 1; // Used to determine if changes were made
                $update_data['Relationship Manager'][$key] = $relationship_manager->getDirty();
              }
            }
          }
        }
      }

      if (
        $request->has('company_names_as_per_banks') &&
        count($request->company_names_as_per_banks) > 0 &&
        collect($request->company_names_as_per_banks)->first() != null &&
        $request->has('account_numbers') &&
        count($request->account_numbers) > 0 &&
        collect($request->account_numbers)->first() != null &&
        $request->has('bank_names') &&
        count($request->bank_names) > 0 &&
        collect($request->bank_names)->first() != null
      ) {
        foreach ($request->company_names_as_per_banks as $key => $value) {
          if ($request->bank_details[$key] != '-1') {
            $bank_account_details = CompanyBank::find($request->bank_details[$key]);
            $bank_account_details->name_as_per_bank = $value;
            $bank_account_details->account_number = $request->account_numbers[$key];
            $bank_account_details->bank_name = $request->bank_names[$key];
            $bank_account_details->branch = $request->branches[$key];
            $bank_account_details->swift_code = $request->swift_codes[$key];
            $bank_account_details->account_type = $request->account_types[$key];
            if ($bank_users->count() <= 0) {
              $bank_account_details->save();
            } else {
              if (count($bank_account_details->getDirty()) > 0) {
                $dirty_count += 1; // Used to determine if changes were made
                $update_data['Bank Details'][$bank_account_details->id] = $bank_account_details->getDirty();
              }
            }
          } else {
            $bank_account_details = new CompanyBank();
            $bank_account_details->company_id = $company->id;
            $bank_account_details->name_as_per_bank = $value;
            $bank_account_details->account_number = $request->account_numbers[$key];
            $bank_account_details->bank_name = $request->bank_names[$key];
            $bank_account_details->branch = $request->branches[$key];
            $bank_account_details->swift_code = $request->swift_codes[$key];
            $bank_account_details->account_type = $request->account_types[$key];
            if ($bank_users->count() <= 0) {
              $bank_account_details->save();
            } else {
              $dirty_count += 1; // Used to determine if changes were made
              $update_data['Bank Details'][$key] = $bank_account_details->getDirty();
            }
          }
        }
      }

      if ($dirty_count == 0) {
        toastr()->error('', 'No Changes were made on the company');

        return back();
      }

      if ($bank_users->count() > 0) {
        CompanyChange::create([
          'user_id' => auth()->id(),
          'company_id' => $company->id,
          'changes' => $update_data,
        ]);
      } else {
        Company::where('bank_id', $bank->id)
          ->where('name', $company->name)
          ->where('kra_pin', $company->kra_pin)
          ->where('is_published', false)
          ->delete();
      }

      if ($request->hasFile('company_logo')) {
        $company->update([
          'logo' => pathinfo($request->company_logo->store('logo', 'company'), PATHINFO_BASENAME),
        ]);
      }
    }

    if ($bank_users->count() > 0) {
      activity($bank->id)
        ->causedBy(auth()->user())
        ->performedOn($company)
        ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
        ->log('proposed update to company');
      foreach ($bank_users as $bank_user) {
        SendMail::dispatchAfterResponse($bank_user->user->email, 'CompanyUpdated', ['company' => $company->id]);
        $bank_user->user->notify(new CompanyUpdation($company));
      }
      toastr()->success('', 'Changes submitted for approval');
    } else {
      activity($bank->id)
        ->causedBy(auth()->user())
        ->performedOn($company)
        ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
        ->log('updated company');
      toastr()->success('', 'Company updated successfully');
    }

    return redirect()->route('companies.show', ['bank' => $bank, 'company' => $company]);
  }

  public function documentsUpload(Bank $bank, Company $company)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Add KYC Documents')
    ) {
      toastr()->error('', 'You do not have the permission to perform this action');
      return back();
    }

    $documents = $bank->requiredDocuments->load('programType', 'programCode')->groupBy('programType.name');

    if ($documents->count() <= 0) {
      toastr()->info('', 'Configure the required documents to upload documents for the company.');
      return redirect()->route('companies.show', ['bank' => $bank, 'company' => $company]);
    }

    $current_uploaded_documents = $company->documents->pluck('name')->toArray();

    return view(
      'content.bank.companies.documents-upload',
      compact('bank', 'company', 'documents', 'current_uploaded_documents')
    );
  }

  public function uploadDocuments(Request $request, Bank $bank, Company $company)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Add KYC Documents')
    ) {
      toastr()->error('', 'You do not have the permission to perform this action');

      return redirect()->route('companies.show', ['bank' => $bank, 'company' => $company]);
    }

    $request->validate([
      'files' => ['required', 'array', 'min:1'],
      'files.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:50000'], // 50MB max size
    ]);

    $documents = [];

    foreach ($request->files as $files) {
      foreach ($files as $key => $file) {
        array_push($documents, $key);

        CompanyDocument::create([
          'company_id' => $company->id,
          'name' => $key,
          'path' => pathinfo($request->file('files')[$key]->store('documents', 'company'), PATHINFO_BASENAME),
          'status' => 'approved',
        ]);
      }
    }

    activity($bank->id)
      ->causedBy(auth()->user())
      ->performedOn($company)
      ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
      ->log('uploaded documents for company');

    // Notify the relationship managers
    foreach ($company->relationshipManagers as $user) {
      SendMail::dispatchAfterResponse($user->email, 'DocumentsUploaded', [
        'documents' => $documents,
        'company_id' => $company->id,
      ]);
    }

    toastr()->success('', 'Documents uploaded successfully');

    return back();
  }

  public function deleteDocument(Bank $bank, Company $company, CompanyDocument $company_document)
  {
    $company_document->delete();

    toastr()->success('', 'Document Deleted Successfully');

    return back();
  }

  public function approveChanges(Bank $bank, Company $company, $status)
  {
    if (
      !auth()
        ->user()
        ->hasAnyPermission(['Approve Product Configurations', 'Company Changes Checker'])
    ) {
      toastr()->error('', 'You are not allowed to perform this action');
    }

    $proposed_updates = $company->proposedUpdate;

    if ($status == 'approve') {
      foreach ($proposed_updates->changes as $key => $update) {
        if ($key == 'Company Details') {
          foreach ($update as $column => $data) {
            if ($column == 'top_level_borrower_limit') {
              $company->update([
                $column => Str::replace(',', '', $data),
              ]);
            } else {
              $company->update([
                $column => $data,
              ]);
            }

            if ($column == 'status') {
              foreach ($company->bank->users as $user) {
                $user->notify(new CompanyUpdation($company));
              }
              // Change associated programs and mapping to active/inactive
              $vendor_configurations = ProgramVendorConfiguration::where('company_id', $company->id)->get();
              foreach ($vendor_configurations as $vendor_configuration) {
                $vendor_configuration->update([
                  'status' => $company->status == 'active' ? 'active' : 'inactive',
                ]);
              }

              $programs = Program::whereHas('anchor', function ($query) use ($company) {
                $query->where('companies.id', $company->id);
              })->get();

              foreach ($programs as $program) {
                $program->update([
                  'account_status' => $company->status == 'active' ? 'active' : 'suspended',
                ]);
              }
            }
          }
        }

        if ($key == 'Relationship Manager') {
          foreach ($update as $id => $rows) {
            $relationship_manager = CompanyRelationshipManager::where('id', $id)
              ->where('company_id', $company->id)
              ->first();

            // Relationship manager already exists for the company
            if ($relationship_manager) {
              foreach ($rows as $column => $data) {
                $relationship_manager[$column] = $data;
              }
              $relationship_manager->save();
            } else {
              $manager_details = ['company_id' => $company->id];
              foreach ($rows as $column => $data) {
                $manager_details[$column] = $data;
              }
              CompanyRelationshipManager::create($manager_details);
            }
          }
        }

        if ($key == 'Bank Details') {
          foreach ($update as $id => $rows) {
            $bank_details = CompanyBank::where('id', $id)
              ->where('company_id', $company->id)
              ->first();
            if ($bank_details) {
              foreach ($rows as $column => $data) {
                $bank_details[$column] = $data;
              }
              $bank_details->save();
            } else {
              $bank_details = ['company_id' => $company->id];
              foreach ($rows as $column => $data) {
                $bank_details[$column] = $data;
              }
              CompanyBank::create($bank_details);
            }
          }
        }
      }

      $proposed_updates->user->notify(new ChangesApproval([$proposed_updates], 'approved'));

      // $company->notify(new CompanyUpdation($company));

      $proposed_updates->delete();

      activity($bank->id)
        ->causedBy(auth()->user())
        ->performedOn($company)
        ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
        ->log('approved update to company');
    } else {
      $proposed_updates->user->notify(new ChangesApproval([$proposed_updates], 'rejected'));
      $proposed_updates->delete();
      activity($bank->id)
        ->causedBy(auth()->user())
        ->performedOn($company)
        ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
        ->log('rejected update to company');
    }

    Company::where('bank_id', $bank->id)
      ->where('name', $company->name)
      ->where('kra_pin', $company->kra_pin)
      ->where('is_published', false)
      ->delete();

    if (request()->wantsJson()) {
      return response()->json(['message' => 'Updated successfully']);
    }

    toastr()->success('', 'Company updated successfully');

    return back();
  }

  public function showPending(Bank $bank, Pipeline $pipeline)
  {
    $pipeline->load('uploadedDocuments.companyDocuments');
    $document = UploadDocument::where('pipeline_id', $pipeline->id)->first();
    $required_documents = explode(
      ',',
      str_replace('"', '', str_replace('\\', '', str_replace(']', '', str_replace('[', '', $document->documents))))
    );
    $required_documents_count = count(
      explode(
        ',',
        str_replace('"', '', str_replace('\\', '', str_replace(']', '', str_replace('[', '', $document->documents))))
      )
    );
    $uploaded_documents_count = Document::where('uuid', $document->slug)->count();

    return view(
      'content.bank.companies.show-pending',
      compact('bank', 'pipeline', 'required_documents', 'required_documents_count', 'uploaded_documents_count')
    );
  }

  public function bulkUpdateStatus(Request $request, Bank $bank)
  {
    $request->validate([
      'companies' => ['required'],
      'status' => ['required'],
      'rejection_reason' => ['required_if:status,rejected'],
    ]);

    foreach ($request->companies as $company) {
      $company = Company::find($company);
      $company->update([
        'approval_status' => $request->status,
        'status' => $request->status == 'approved' ? 'active' : 'inactive',
        'rejection_reason' =>
          $request->has('rejection_reason') && $request->rejection_reason != '' ? $request->rejection_reason : null,
      ]);

      // Delete Drafts
      DB::table('companies')
        ->where('id', '!=', $company->id)
        ->where('name', $company->name)
        ->where('is_published', false)
        ->delete();

      // Send notification to Company RMs and Users
      if ($company->users->count() > 0) {
        foreach ($company->users as $user) {
          SendMail::dispatchAfterResponse($user->email, 'CompanyUpdated', ['company' => $company->id]);
        }
      }

      if ($company->relationshipManagers->count() > 0) {
        foreach ($company->relationshipManagers as $user) {
          SendMail::dispatchAfterResponse($user->email, 'CompanyUpdated', ['company' => $company->id]);
        }
      }
    }

    return response()->json(['message' => 'Companies updated successfully']);
  }

  public function updateStatus(Request $request, Bank $bank, Company $company)
  {
    $company->update([
      'approval_status' => $request->status,
      'rejection_reason' =>
        $request->has('rejection_reason') && !empty($request->rejection_reason) ? $request->rejection_reason : null,
    ]);

    // Remove rejection reason if company is approved
    if ($request->status == 'approved') {
      $company->update([
        'rejection_reason' => null,
        'status' => 'active',
      ]);

      if ($company->pipeline) {
        $company->pipeline->update([
          'stage' => 'Closed',
          'note' => null,
        ]);

        $previousStage = $company->pipeline->stage;
        $previousStageUpdatedAt = $company->pipeline->updated_at;
        $transitionPeriod = $previousStageUpdatedAt->diffInMinutes(now());

        PipelineStage::create([
          'pipeline_id' => $company->pipeline->id,
          'initial_stage' => $previousStage,
          'new_stage' => 'Closed',
          'period' => $transitionPeriod,
          'entered_at' => now(),
        ]);

        // Log Activity
        ActivityHelper::logCrmActivity([
          'subject_type' => 'Pipeline Closure',
          'subject_id' => $company->pipeline->id,
          'stage' => 'Closed',
          'section' => 'Pipeline Update',
          'pipeline_id' => $company->pipeline->id,
          'user_id' => auth()->id(),
          'description' => 'Pipeline Closed',
          'properties' => $company->pipeline->toArray(),
          'device_info' => ['ip' => request()->ip(), 'device_info' => request()->userAgent()],
          'ip_address' => $request->ip(),
          'url' => $request->fullUrl(),
          'created_at' => now(),
          'updated_at' => now(),
        ]);
      }
    }

    // Update Pipeline to rejected if company has pipeline
    if ($request->status != 'approved' && $company->pipeline) {
      $company->pipeline->update([
        'stage' => 'Reject',
        'note' =>
          $request->has('rejection_reason') && !empty($request->rejection_reason) ? $request->rejection_reason : null,
      ]);

      $previousStage = $company->pipeline->stage;
      $previousStageUpdatedAt = $company->pipeline->updated_at;
      $transitionPeriod = $previousStageUpdatedAt->diffInMinutes(now());

      PipelineStage::create([
        'pipeline_id' => $company->pipeline->id,
        'initial_stage' => $previousStage,
        'new_stage' => 'Reject',
        'period' => $transitionPeriod,
        'entered_at' => now(),
      ]);

      // Log Activity
      ActivityHelper::logCrmActivity([
        'subject_type' => 'Pipeline Rejection',
        'subject_id' => $company->pipeline->id,
        'stage' => 'Reject',
        'section' => 'Pipeline Update',
        'pipeline_id' => $company->pipeline->id,
        'user_id' => auth()->id(),
        'description' => 'Pipeline Rejected',
        'properties' => $company->pipeline->toArray(),
        'device_info' => ['ip' => request()->ip(), 'device_info' => request()->userAgent()],
        'ip_address' => $request->ip(),
        'url' => $request->fullUrl(),
        'created_at' => now(),
        'updated_at' => now(),
      ]);

      // Send Email to Pipeline Contact
      SendMail::dispatchAfterResponse($company->pipeline->email, 'PipelineStageUpdated', [
        'pipeline_id' => $company->pipeline->id,
        'stage' => 'Rejected',
      ]);
      // Send Email to CRM User
      SendMail::dispatchAfterResponse($company->pipeline->user?->email, 'PipelineStageUpdated', [
        'pipeline_id' => $company->pipeline->id,
        'stage' => 'Rejected',
      ]);
    }

    if ($company->users->count() > 0) {
      foreach ($company->users as $user) {
        SendMail::dispatchAfterResponse($user->email, 'CompanyUpdated', ['company' => $company->id]);
      }
    }

    if ($company->relationshipManagers->count() > 0) {
      foreach ($company->relationshipManagers as $user) {
        SendMail::dispatchAfterResponse($user->email, 'CompanyUpdated', ['company' => $company->id]);
      }
    }

    foreach ($company->bank->users as $user) {
      $user->notify(new CompanyUpdation($company));
    }

    activity($bank->id)
      ->causedBy(auth()->user())
      ->performedOn($company)
      ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
      ->log('updated status to "' . $request->status . '"');

    if (!request()->wantsJson()) {
      toastr()->success('', 'Company status updated successfully');

      return back();
    }
  }

  public function updateBlockStatus(Bank $bank, Company $company)
  {
    $company->update([
      'is_blocked' => !$company->is_blocked,
    ]);

    if ($company->users->count() > 0) {
      foreach ($company->users as $user) {
        SendMail::dispatchAfterResponse($user->email, 'CompanyUpdated', ['company' => $company->id]);
      }
    }

    if ($company->relationshipManagers->count() > 0) {
      foreach ($company->relationshipManagers as $user) {
        SendMail::dispatchAfterResponse($user->email, 'CompanyUpdated', ['company' => $company->id]);
      }
    }

    foreach ($company->bank->users as $user) {
      $user->notify(new CompanyUpdation($company));
    }

    if ($company->is_blocked) {
      activity($bank->id)
        ->causedBy(auth()->user())
        ->performedOn($company)
        ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
        ->log('blocked');
    } else {
      activity($bank->id)
        ->causedBy(auth()->user())
        ->performedOn($company)
        ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
        ->log('unblocked');
    }

    if (!request()->wantsJson()) {
      toastr()->success('', 'Company status updated successfully');

      return back();
    }

    return response()->json(['message' => 'Company status updated successfully']);
  }

  public function updateActiveStatus(Bank $bank, Company $company, $status)
  {
    $bank_users = BankUser::where('bank_id', $bank->id)
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where('name', 'Company Changes Checker');
          });
        });
      })
      ->where('user_id', '!=', auth()->id())
      ->where('active', true)
      ->get();

    $company->status = $status;

    if ($bank_users->count() <= 0) {
      $company->save();
    } else {
      if (count($company->getDirty()) > 0) {
        $update_data['Company Details'] = $company->getDirty();
      }
    }

    if ($bank_users->count() > 0) {
      CompanyChange::create([
        'user_id' => auth()->id(),
        'company_id' => $company->id,
        'changes' => $update_data,
      ]);

      foreach ($bank_users as $bank_user) {
        SendMail::dispatchAfterResponse($bank_user->user->email, 'CompanyUpdated', ['company' => $company->id]);
        $bank_user->user->notify(new CompanyUpdation($company));
      }

      $message = 'Company Change sent for approval';
    } else {
      Company::where('bank_id', $bank->id)
        ->where('name', $company->name)
        ->where('kra_pin', $company->kra_pin)
        ->where('is_published', false)
        ->delete();
      $message = 'Company updated successfully';
    }

    if (!request()->wantsJson()) {
      if ($bank_users->count() > 0) {
        toastr()->success('', 'Company status change sent for approval');
      } else {
        toastr()->success('', 'Company status updated successfully');
      }

      return back();
    }

    return response()->json(['message' => $message], 200);
  }

  public function updateDocumentStatus(Bank $bank, Request $request)
  {
    $request->validate([
      'document_id' => ['required'],
      'status' => ['required'],
      'rejected_reason' => ['required_if:status,rejected'],
    ]);

    $document = CompanyDocument::find($request->document_id);

    $document->update([
      'status' => $request->status,
      'rejected_reason' =>
        $request->has('rejected_reason') && !empty($request->rejected_reason) ? $request->rejected_reason : null,
    ]);

    activity($bank->id)
      ->causedBy(auth()->user())
      ->performedOn($document)
      ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
      ->log('updated document status to "' . $request->status . '"');

    if ($request->status == 'rejected') {
      RequestDocument::where('company_id', $document->company->id)
        ->where('name', $document->name)
        ->first()
        ->update(['status' => 'pending']);
    }

    $url = route('company-documents-upload', ['bank' => $bank, 'company_id' => encrypt($document->company->id)]);

    // Send email notification to company user
    if ($document->company->users->count() > 0) {
      foreach ($document->company->users as $user) {
        SendMail::dispatchAfterResponse($user->email, 'DocumentUpdated', [
          'user_name' => $user->name,
          'document_id' => $document->id,
          'status' => $document->status,
          'url' => $url,
        ]);
      }
    }

    // Send email notification to company relationship managers
    if ($document->company->relationshipManagers->count() > 0) {
      foreach ($document->company->relationshipManagers as $user) {
        SendMail::dispatchAfterResponse($user->email, 'DocumentUpdated', [
          'user_name' => $user->name,
          'document_id' => $document->id,
          'status' => $document->status,
          'url' => $url,
        ]);
      }
    }

    toastr()->success('', 'Successfully updated document');

    return back();
  }

  public function requestDocuments(Bank $bank, Company $company, Request $request)
  {
    $request->validate([
      'documents' => ['required'],
      'send_to_email' => ['required', 'email'],
    ]);

    if (gettype($request->documents) == 'array') {
      $requested_documents = $request->documents;
    } else {
      $requested_documents = explode(',', $request->documents);
    }

    $company->requestedDocuments()->delete();

    collect($requested_documents)->each(function ($doc) use ($company) {
      $company->requestedDocuments()->create([
        'name' => $doc,
      ]);
    });

    // Send email notification to company user
    if ($request->has('send_to_email') && !empty($request->send_to_email)) {
      $link = route('company-documents-upload', ['bank' => $bank, 'company_id' => encrypt($company->id)]);
      SendMail::dispatchAfterResponse($request->send_to_email, 'RequestDocuments', [
        'link' => $link,
        'documents' => $requested_documents,
      ]);
    }

    activity($bank->id)
      ->causedBy(auth()->user())
      ->performedOn($company)
      ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
      ->log('requested documents');

    toastr()->success('', 'Request sent successfully');

    return back();
  }

  public function updatePendingDocumentStatus(Bank $bank, Request $request)
  {
    $request->validate([
      'document_id' => ['required'],
      'status' => ['required'],
      'rejected_reason' => ['required_if:status,rejected'],
    ]);

    $url = '';

    $document = Document::with('uploadedDocument.pipeline')->find($request->document_id);

    $document->update([
      'status' => $request->status,
      'rejected_reason' =>
        $request->has('rejected_reason') && !empty($request->rejected_reason) ? $request->rejected_reason : null,
    ]);

    if ($document->status == 'rejected') {
      $url = env('APP_CRM_URL') . '/documents/' . $document->uploadedDocument->slug;
    }

    // Log Activity
    ActivityHelper::logCrmActivity([
      'subject_type' => 'Pipeline Document Update',
      'subject_id' => $document->pipeline_id,
      'stage' => 'Opportunity',
      'section' => 'Pipeline Document Update',
      'pipeline_id' => $document->pipeline_id,
      'user_id' => auth()->id(),
      'description' => 'Uploaded Pipeline Document, ' . $document->document_name . ', was ' . $request->status,
      'properties' => $document->pipeline->toArray(),
      'device_info' => ['ip' => request()->ip(), 'device_info' => request()->userAgent()],
      'ip_address' => $request->ip(),
      'url' => $url,
      'created_at' => now(),
      'updated_at' => now(),
    ]);

    // Send email notification to company user
    SendMail::dispatchAfterResponse($document->uploadedDocument->email, 'DocumentUpdated', [
      'user_name' => $document->uploadedDocument->pipeline->name,
      'document_id' => $document->id,
      'status' => $document->status,
      'url' => $url,
    ]);

    toastr()->success('', 'Successfully updated document');

    return back();
  }

  public function updatePipelineCompanyStatus(Bank $bank, Request $request)
  {
    $request->validate([
      'pipeline_id' => ['required'],
      'status' => ['required'],
    ]);

    $pipeline = Pipeline::find($request->pipeline_id);

    // Create company from pipeline
    $company = $bank->companies()->create([
      'name' => $pipeline->company,
      'business_segment' => $pipeline->department,
      'city' => $pipeline->region,
      'approval_status' => 'approved',
      'status' => 'active',
      'relationship_manager_name' => $pipeline->point_of_contact,
      'relationship_manager_email' => $pipeline->email,
      'relationship_manager_phone_number' => $pipeline->phone_number,
      'approval_status' => 'approved',
      'pipeline_id' => $pipeline->id,
    ]);

    $pipeline->uploadedDocuments->each(function ($document) use ($company) {
      $document->companyDocuments->each(function ($document) use ($company) {
        // Approve all documents
        $document->update(['status' => 'approved', 'rejected_reason' => null]);
        // Add to company documents
        CompanyDocument::create([
          'company_id' => $company->id,
          'name' => $document->original_name,
          'status' => 'approved',
        ]);
      });
    });

    SendMail::dispatchAfterResponse($company->relationship_manager_email, 'CompanyApproved', [
      'company' => $company,
      'name' => $company->relationship_manager_name,
    ]);

    toastr()->success('', 'Company updated successfully');

    return redirect()->route('companies.index', ['bank' => $bank]);
  }

  public function odAccounts(Bank $bank)
  {
    return view('content.bank.companies.od-accounts');
  }

  public function odAccountsData(Request $request, Bank $bank)
  {
    $payment_account_number = $request->query('payment_account_number');
    $anchor = $request->query('anchor');
    $dealer = $request->query('dealer');
    $per_page = $request->query('per_page');

    $programs = OdAccountsResource::collection(
      ProgramVendorConfiguration::with([
        'invoices' => fn($invoice) => $invoice
          ->where('financing_status', 'financed')
          ->whereDate('due_date', '>', now()),
      ])
        ->whereHas('program', function ($query) use ($bank) {
          $query->where('bank_id', $bank->id)->whereHas('programType', function ($query) {
            $query->where('name', 'Dealer Financing');
          });
        })
        ->when($payment_account_number && $payment_account_number != '', function ($query) use (
          $payment_account_number
        ) {
          $query->where('payment_account_number', 'LIKE', '%' . $payment_account_number . '%');
        })
        ->when($anchor && $anchor != '', function ($query) use ($anchor) {
          $query->whereHas('program', function ($query) use ($anchor) {
            $query->whereHas('anchor', function ($query) use ($anchor) {
              $query->where('name', 'LIKE', '%' . $anchor . '%');
            });
          });
        })
        ->when($dealer && $dealer != '', function ($query) use ($dealer) {
          $query->whereHas('company', function ($query) use ($dealer) {
            $query->where('name', 'LIKE', '%' . $dealer . '%');
          });
        })
        ->latest()
        ->paginate($per_page)
    )
      ->response()
      ->getData();

    return response()->json([
      'programs' => $programs,
      'can_update' => auth()
        ->user()
        ->hasPermissionTo('Add/Debit Funds in OD Accounts'),
    ]);
  }

  public function odAccountDetails(Bank $bank, ProgramVendorConfiguration $program_vendor_configuration)
  {
    $program_vendor_configuration->load('program.anchor', 'company');

    $program = $program_vendor_configuration->program;

    $cbs_transactions = CbsTransactionResource::collection(
      CbsTransaction::whereHas('paymentRequest', function ($query) use ($program) {
        $query->whereHas('invoice', function ($query) use ($program) {
          $query->where('program_id', $program->id);
        });
      })
        ->orWhereHas('creditAccountRequest', function ($query) use ($program) {
          $query->where('program_id', $program->id);
        })
        ->latest()
        ->take(10)
        ->get()
    );

    return view(
      'content.bank.companies.od-account-details',
      compact('program_vendor_configuration', 'bank', 'cbs_transactions')
    );
  }

  public function discountAccountDetails(Bank $bank, ProgramVendorConfiguration $program_vendor_configuration)
  {
    return view('content.bank.companies.discount-account-details', compact('program_vendor_configuration', 'bank'));
  }

  public function totalOutstandingPayments(Bank $bank, ProgramVendorConfiguration $program_vendor_configuration)
  {
    $invoices = InvoiceResource::collection(
      Invoice::with('program.anchor', 'company')
        ->whereHas('paymentRequests')
        ->whereIn('financing_status', ['submitted', 'pending'])
        ->where('program_id', $program_vendor_configuration->program_id)
        ->latest()
        ->whereDate('due_date', '>', now()->format('Y-m-d'))
        ->get()
    );

    return view(
      'content.bank.companies.total-outstanding-payments',
      compact('program_vendor_configuration', 'bank', 'invoices')
    );
  }

  public function odDailyInterest(Bank $bank, ProgramVendorConfiguration $program_vendor_configuration)
  {
    $overdue_invoices = Invoice::where('program_id', $program_vendor_configuration->program_id)
      ->where('financing_status', 'financed')
      ->whereDate('due_date', '<', now()->format('Y-m-d'))
      ->pluck('id');

    $cbs_transactions = CbsTransactionResource::collection(
      CbsTransaction::with('paymentRequest.invoice.company')
        ->whereHas('paymentRequest', function ($query) use ($overdue_invoices) {
          $query->whereIn('invoice_id', $overdue_invoices);
        })
        ->where('transaction_type', 'Overdue Account')
        ->get()
    );

    return view(
      'content.bank.companies.od-daily-interest',
      compact('cbs_transactions', 'bank', 'program_vendor_configuration')
    );
  }

  public function creditAccount(Bank $bank, Request $request)
  {
    $validator = Validator::make(
      $request->all(),
      [
        'debit_from_account' => ['required'],
        'credit_to_account' => ['required'],
        'amount' => ['required', 'min:1', 'integer'],
        'credit_date' => ['required'],
      ],
      [
        'debit_from_account.required' => 'Enter the Debit From Account',
        'credit_to_account.required' => 'Enter Credit to Account Information',
        'amount.required' => 'Enter the amount',
        'amount.min' => 'Amount cannot be less than 1',
        'credit_date.required' => 'Enter the credited date',
      ]
    );

    if ($validator->fails()) {
      return response()->json($validator->messages(), 400);
    }

    try {
      DB::beginTransaction();
      // User has made a repayment on their invoices
      if ($request->has('invoice_id') && !empty($request->invoice_id) && $request->invoice_id != '') {
        $invoice = Invoice::find($request->invoice_id);

        // Check if entered amount is more than invoice balance
        if ($request->amount > $invoice->balance) {
          return response()->json(['message' => ['The amount is more than balance of invoice']], 400);
        }

        // Check repayments transactions made for the invoice
        $repayment_transactions_amount = CbsTransaction::whereHas('paymentRequest', function ($query) use ($invoice) {
          $query->where('invoice_id', $invoice->id);
        })
          ->whereIn('status', ['Created', 'Successful'])
          ->whereIn('transaction_type', ['Overdue Account', 'Repayment'])
          ->sum('amount');

        if ($invoice->disbursed_amount < $invoice->invoice_total_amount) {
          // Front ended
          $amount = $invoice->invoice_total_amount + $invoice->paid_amount;
        } else {
          // Rear ended
          $amount = $invoice->disbursed_amount + $invoice->paid_amount;
        }

        if ($repayment_transactions_amount > $invoice->balance) {
          return response()->json(['message' => ['Clear the repayment transactions to proceed']], 400);
        }

        $program = ProgramVendorConfiguration::where('payment_account_number', $request->credit_to_account)->first();

        $payment_request = PaymentRequest::create([
          'reference_number' => 'DF' . $bank->id . '' . $program->payment_account_number . '000' . $invoice->id,
          'invoice_id' => $invoice->id,
          'amount' => $request->amount,
          'payment_request_date' => Carbon::parse($request->credit_date)->format('Y-m-d'),
          'status' => 'approved',
          'approval_status' => 'approved',
        ]);

        // Principle repayments
        $payment_request->paymentAccounts()->create([
          'account' => $program->payment_account_number,
          'account_name' => $invoice->program->name,
          'amount' => $request->amount,
          'type' => 'principle_repayment',
          'description' => 'Dealer Financing Repayment',
        ]);

        $cbs_transaction = CbsTransaction::create([
          'bank_id' => $payment_request->invoice?->program?->bank?->id,
          'payment_request_id' => $payment_request->id,
          'debit_from_account' => $request->debit_from_account,
          'credit_to_account' => $program->payment_account_number,
          'amount' => $request->amount,
          'transaction_created_date' => now()->format('Y-m-d'),
          'pay_date' => Carbon::parse($request->credit_date)->format('Y-m-d'),
          'status' => 'Created',
          'transaction_type' => 'Repayment',
          'product' => 'Dealer Financing',
        ]);

        activity($bank->id)
          ->causedBy(auth()->user())
          ->performedOn($cbs_transaction)
          ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
          ->log('created CBS Transaction (Credit OD Account)');
      } else {
        $program = ProgramVendorConfiguration::where('payment_account_number', $request->credit_to_account)->first();

        $invoices = Invoice::where('program_id', $program->program->id)
          ->where('financing_status', 'financed')
          ->get();

        if ($invoices->count() > 0) {
          $balance = $request->amount;

          $invoice = Invoice::where('program_id', $program->program_id)
            ->whereHas('program', function ($query) {
              $query->whereHas('programType', function ($query) {
                $query->where('name', 'Dealer Financing');
              });
            })
            ->where('financing_status', 'financed')
            ->orderBy('created_at', 'ASC')
            ->first();

          do {
            $program = ProgramVendorConfiguration::where('company_id', $invoice->company_id)
              ->where('program_id', $invoice->program_id)
              ->first();

            $payment_request = PaymentRequest::create([
              'reference_number' => 'DF' . $bank->id . '' . $program->payment_account_number . '000' . $invoice->id,
              'invoice_id' => $invoice->id,
              'amount' => $balance,
              'payment_request_date' => Carbon::parse($request->credit_date)->format('Y-m-d'),
              'status' => 'approved',
              'approval_status' => 'approved',
            ]);

            // Principle repayments
            $payment_request->paymentAccounts()->create([
              'account' => $program->payment_account_number,
              'account_name' => $invoice->program->name,
              'amount' => $balance,
              'type' => 'principle_repayment',
              'description' => 'Dealer Financing Repayment',
            ]);

            CbsTransaction::create([
              'bank_id' => $payment_request->invoice?->program?->bank?->id,
              'payment_request_id' => $payment_request->id,
              'debit_from_account' => $request->debit_from_account,
              'credit_to_account' => $program->payment_account_number,
              'amount' => $balance,
              'transaction_created_date' => now()->format('Y-m-d'),
              'pay_date' => Carbon::parse($request->credit_date)->format('Y-m-d'),
              'status' => 'Created',
              'transaction_type' => 'Repayment',
              'product' => 'Dealer Financing',
            ]);

            $balance -= $invoice->balance;

            if ($balance > 0) {
              $invoice = Invoice::where('program_id', $program->program_id)
                ->where('company_id', $payment_request->invoice->company_id)
                ->whereHas('program', function ($query) {
                  $query->whereHas('programType', function ($query) {
                    $query->where('name', Program::DEALER_FINANCING);
                  });
                })
                ->where('financing_status', 'financed')
                ->orderBy('created_at', 'ASC')
                ->first();
            } else {
              $invoice = null;
            }
          } while ($invoice && $balance > 0);
        }
      }

      DB::commit();

      if (request()->wantsJson()) {
        return response()->json(['message' => 'Payment added successfully']);
      }
    } catch (\Throwable $e) {
      info($e);
      DB::rollBack();
      if (request()->wantsJson()) {
        return response()->json(['message' => 'Something went wrong'], 500);
      }
    }
  }

  public function debitAccount(Bank $bank, Request $request)
  {
    $validator = Validator::make($request->all(), [
      'debit_from_account' => ['required'],
      'credit_to_account' => ['required'],
      'amount' => ['required'],
      'credit_date' => ['required'],
    ]);

    if ($validator->fails()) {
      return response()->json($validator->messages(), 400);
    }

    // Check if amount is greater than what can be debited
    $program_vendor_configuration = ProgramVendorConfiguration::where(
      'payment_account_number',
      $request->debit_from_account
    )->first();
    if ($program_vendor_configuration) {
      if (
        $request->amount >
        $program_vendor_configuration->paidAmount() - $program_vendor_configuration->sanctioned_limit
      ) {
        return response()->json(['message' => 'Amount cannot be greater than excess credit'], 422);
      }
    }
    if (!$program_vendor_configuration) {
      return response()->json(['message' => 'Invalid Debit From Account number'], 422);
    }

    try {
      DB::beginTransaction();

      CbsTransaction::create([
        'bank_id' => $program_vendor_configuration->program?->bank?->id,
        'debit_from_account' => $request->debit_from_account,
        'credit_to_account' => $request->credit_to_account,
        'amount' => $request->amount,
        'transaction_created_date' => now()->format('Y-m-d'),
        'pay_date' => Carbon::parse($request->credit_date)->format('Y-m-d'),
        'transaction_date' => Carbon::parse($request->credit_date)->format('Y-m-d'),
        'status' => 'Created',
        'transaction_type' => 'Fees/Charges',
        'product' => 'Dealer Financing',
      ]);

      DB::commit();

      if (request()->wantsJson()) {
        return response()->json(['message' => 'Payment added successfully']);
      }
    } catch (\Throwable $e) {
      info($e);
      DB::rollBack();
      if (request()->wantsJson()) {
        return response()->json(['message' => 'Something went wrong'], 500);
      }
    }
  }

  public function reversal(Bank $bank, Request $request)
  {
    $validator = Validator::make($request->all(), [
      'debit_from_account' => ['required'],
      'credit_to_account' => ['required'],
      'amount' => ['required'],
      'credit_date' => ['required'],
      'particulars' => ['required'],
    ]);

    if ($validator->fails()) {
      return response()->json($validator->messages(), 400);
    }

    try {
      DB::beginTransaction();
      // TODO: Implement reversal of transactions
      DB::commit();

      if (request()->wantsJson()) {
        return response()->json(['message' => 'Payment reversed successfully']);
      }
    } catch (\Throwable $e) {
      info($e);
      DB::rollBack();
      if (request()->wantsJson()) {
        return response()->json(['message' => 'Something went wrong'], 500);
      }
    }
  }

  public function getAccounts(Bank $bank, ProgramVendorConfiguration $program_vendor_configuration, $type)
  {
    $bank_account_details = [];

    switch ($type) {
      case 'Refund':
        // Get Dealer's Bank Accounts
        $bank_accounts = ProgramVendorBankDetail::where('company_id', $program_vendor_configuration->company_id)
          ->where('program_id', $program_vendor_configuration->program_id)
          ->select('account_number')
          ->get();
        foreach ($bank_accounts as $account) {
          array_push($bank_account_details, $account->account_number);
        }
        break;

      default:
        # code...
        break;
    }

    return response()->json([
      'accounts' => $bank_account_details,
    ]);
  }
}
