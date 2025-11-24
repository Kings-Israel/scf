<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Bank;
use App\Models\User;
use App\Jobs\SendMail;
use App\Models\Invoice;
use App\Models\BankUser;
use App\Models\RoleType;
use App\Models\BankBranch;
use App\Models\UserChange;
use App\Models\BankHoliday;
use App\Models\BankTaxRate;
use App\Models\NoaTemplate;
use App\Models\ProgramType;
use Illuminate\Support\Str;
use App\Models\BankBaseRate;
use App\Models\BankDocument;
use Illuminate\Http\Request;
use App\Models\BankFeesMaster;
use App\Models\PermissionData;
use App\Imports\HolidaysImport;
use Illuminate\Validation\Rule;
use App\Models\AccessRightGroup;
use App\Models\AdminBankConfiguration;
use App\Models\BankConfigChange;
use App\Models\BankConvertionRate;
use App\Models\BankPaymentAccount;
use App\Notifications\TaxesUpdate;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Models\BankRejectionReason;
use Maatwebsite\Excel\Facades\Excel;
use App\Notifications\BranchesUpdate;
use App\Notifications\BankUserMapping;
use App\Notifications\ChangesApproval;
use App\Notifications\FeesMasterUpdate;
use Illuminate\Support\Facades\Storage;
use App\Notifications\HolidayListUpdate;
use Spatie\Permission\Models\Permission;
use App\Models\BankProductsConfiguration;
use App\Notifications\BankUserActivation;
use Illuminate\Support\Facades\Validator;
use App\Models\ProposedConfigurationChange;
use App\Models\BankProductRepaymentPriority;
use App\Models\BankGeneralProductConfiguration;
use App\Models\BankWithholdingTaxConfiguration;
use App\Models\Company;
use App\Models\CompanyAuthorizationGroup;
use App\Models\CompanyUserAuthorizationGroup;
use App\Models\Currency;
use App\Models\Program;
use App\Models\RoleChange;
use App\Models\TermsConditionsConfig;
use App\Models\UserCurrentBank;
use App\Notifications\ProductConfigurationUpdation;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;

class ConfigurationsController extends Controller
{
  public function index(Bank $bank)
  {
    $documents = $bank->requiredDocuments->load('programType', 'programCode')->groupBy('programType.name');

    $bank_payment_accounts = $bank->paymentAccounts;
    $latest_bank_payment_account_id = BankPaymentAccount::where('bank_id', $bank->id)
      ->orderBy('id', 'DESC')
      ->first()?->id;
    $withholding_tax_configurations = $bank->withholdingTaxConfigurations;
    $products_configurations = $bank->productsConfigurations->load('productType', 'productCode');
    $repayment_priorities = $bank->repaymentPriorities->load('productType');
    $general_configurations = $bank->generalConfigurations->load('productType');
    $admin_configurations = $bank->adminConfiguration;

    $user_roles = auth()->user()->roles;
    $user_permissions = [];
    foreach ($user_roles as $role) {
      foreach ($role->permissions as $permission) {
        array_push($user_permissions, $permission->name);
      }
    }

    $roles = PermissionData::where('RoleTypeName', 'Bank')
      ->where(function ($query) use ($bank) {
        $query->where('bank_id', $bank->id)->orWhere('bank_id', null);
      })
      ->select('id', 'RoleName')
      ->get();

    $role_types = RoleType::with('Groups.AccessGroups')
      ->where('name', '!=', 'Admin')
      ->where('name', '!=', 'CRM')
      ->get();

    $bank_roles = PermissionData::with('roleIDs')
      ->where('bank_id', $bank->id)
      ->select('id', 'RoleName', 'RoleDescription', 'RoleTypeName')
      ->get();

    $countries = json_decode(file_get_contents(public_path('assets/country-codes.json')));

    $rejection_reasons = $bank->rejectionReasons;

    $date_formats = [
      'DD/MM/YYYY (Ex. 12/02/2020)',
      'MM/DD/YYYY (Ex. 02/12/2020)',
      'DD-MM-YYYY (Ex. 12-02-2020)',
      'MM-DD-YYYY (Ex. 02-12-2020)',
      'DD-MMM-YYYY (Ex. 12-Feb-2020)',
      'DD MMM YYYY (Ex. 02 Feb 2020)',
    ];

    // $bank_selected_products = explode(',', $admin_configurations->product_type);

    // $bank_products = [];

    // foreach ($bank_selected_products as $bank_product) {
    //   if ($bank_product == 'factoring') {
    //     array_push($bank_products, 'Factoring With Recourse');
    //     array_push($bank_products, 'Factoring Without Recourse');
    //   } else {
    //     array_push($bank_products, Str::headline(Str::replace('_', ' ', $bank_product)));
    //   }
    // }

    $product_types = ProgramType::with('programCodes')->get();

    return view('content.bank.configurations.index', [
      'bank' => $bank,
      'documents' => $documents,
      'bank_payment_accounts' => $bank_payment_accounts,
      'latest_payment_account_bank_id' => $latest_bank_payment_account_id ? $latest_bank_payment_account_id : 1,
      'withholding_tax_configurations' => $withholding_tax_configurations,
      'product_configurations' => $products_configurations,
      'general_configurations' => $general_configurations,
      'repayment_priorities' => $repayment_priorities->sortBy('premature_priority'),
      'admin_configurations' => $admin_configurations,
      'roles' => $roles,
      'role_types' => $role_types,
      'bank_roles' => $bank_roles,
      'user_permissions' => $user_permissions,
      'countries' => $countries,
      'rejection_reasons' => $rejection_reasons,
      'date_formats' => $date_formats,
      'product_types' => $product_types,
      // 'bank_products' => $bank_products,
    ]);
  }

  public function users(Bank $bank, Request $request)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('View Users')
    ) {
      return response()->json(['message' => 'You do not have permission to perform this action'], 403);
    }

    $per_page = $request->query('per_page');
    $role = $request->query('role');
    $status = $request->query('status');
    $search = $request->query('search');

    $bank_users_ids = $bank->users->pluck('id');

    $users = User::with([
      'roles',
      'changes',
      'banks' => function ($query) use ($bank) {
        $query->where('banks.id', $bank->id);
      },
    ])
      ->whereIn('users.id', $bank_users_ids)
      ->when($search && $search != '', function ($query) use ($search) {
        $query->where(function ($query) use ($search) {
          $query
            ->where('name', 'LIKE', '%' . $search . '%')
            ->orWhere('email', 'LIKE', '%' . $search . '%')
            ->orWhere('phone_number', 'LIKE', '%' . $search . '%');
        });
      })
      ->when($status && $status != '', function ($query) use ($status, $bank) {
        if ($status == 'active') {
          $status = true;
        } else {
          $status = false;
        }
        $query->whereHas('bankUser', function ($query) use ($status, $bank) {
          $query->where('active', $status)->where('bank_id', $bank->id);
        });
      })
      ->when($role && $role != '', function ($query) use ($role) {
        $query->whereHas('roles', function ($query) use ($role) {
          $query->where('name', 'LIKE', '%' . $role . '%');
        });
      })
      ->orderBy('users.created_at', 'DESC')
      ->paginate($per_page);

    foreach ($users as $user) {
      $bank_user_id = BankUser::where('user_id', $user->id)
        ->where('bank_id', $bank->id)
        ->first();

      $proposed_config = ProposedConfigurationChange::where([
        'modeable_type' => Bank::class,
        'modeable_id' => $bank->id,
        'configurable_type' => BankUser::class,
        'configurable_id' => $bank_user_id->id,
      ])->first();

      if ($proposed_config) {
        $user['has_pending_config'] = true;
      } else {
        $user['has_pending_config'] = false;
      }

      if ($user->id == auth()->id()) {
        $user['is_current_user'] = true;
      } else {
        $user['is_current_user'] = false;
      }
    }

    // Get logged in user permissions names
    $user_permissions = auth()
      ->user()
      ->roles->map(fn($role) => $role->permissions->pluck('name'));

    $roles = PermissionData::where('RoleTypeName', 'Bank')
      ->where(function ($query) use ($bank) {
        $query->where('bank_id', $bank->id)->orWhere('bank_id', null);
      })
      // Ensure Roles is not more powerful than what the logged in user can assign.
      ->whereHas('roleIDs', function ($query) use ($user_permissions) {
        $query->whereHas('AccessRightGroup', function ($query) use ($user_permissions) {
          $query->whereIn('name', $user_permissions[0]);
        });
      })
      ->select('id', 'RoleName')
      ->get();

    return response()->json(['users' => $users, 'roles' => $roles]);
  }

  public function addUser(Bank $bank)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Add/Edit Users')
    ) {
      if (request()->wantsJson()) {
        return response()->json(['message' => 'You do not have the permission to perform this action'], 403);
      }

      toastr()->error('', 'You do not have the permission to perform this action');
      return back();
    }

    $roles = PermissionData::where('RoleTypeName', 'Bank')
      ->where(function ($query) use ($bank) {
        $query->where('bank_id', $bank->id)->orWhere('bank_id', null);
      })
      ->select('id', 'RoleName')
      ->where('status', 'approved')
      ->get();

    $countries = json_decode(file_get_contents(public_path('assets/country-codes.json')));

    $products = ['vendor_financing' => 'Vendor Financing', 'dealer_financing' => 'Dealer Financing'];
    $reporting_managers = User::where('is_active', true)
      ->whereIn('id', $bank->users->pluck('id'))
      ->get();
    $visibilities = ['All Records', 'My Records', 'My and My Reportee Records'];
    $location_types = ['Country', 'City', 'Branch'];
    $notification_channels = ['email' => 'Email', 'sms' => 'SMS', 'email_and_sms' => 'Email and SMS'];
    $branches = BankBranch::where('status', 'active')
      ->where('bank_id', $bank->id)
      ->get();

    return view('content.bank.configurations.add-user', [
      'bank' => $bank,
      'roles' => $roles,
      'countries' => $countries,
      'products' => $products,
      'reporting_managers' => $reporting_managers,
      'visibilities' => $visibilities,
      'location_types' => $location_types,
      'notification_channels' => $notification_channels,
      'branches' => $branches,
    ]);
  }

  public function storeUser(Bank $bank, Request $request)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Add/Edit Users')
    ) {
      if ($request->wantsJson()) {
        return response()->json(['message' => 'You do not have the permission to perform this action'], 403);
      }

      toastr()->error('', 'You do not have the permission to perform this action');
      return back();
    }

    // Validator for unique phone numbers when request has country code
    Validator::extend('unique_phone_number', function ($attribute, $value, $parameters, $validator) {
      return !User::where('phone_number', $parameters[0] . '' . substr($value, -9))->exists();
    });

    $request->validate(
      [
        'email' => ['required', 'email', 'unique:users,email'],
        'name' => ['required'],
        'country_code' => ['required_with:phone_number'],
        'phone_number' => ['required', 'unique_phone_number:' . $request->country_code],
        'role' => ['required'],
      ],
      [
        'email.unique' => 'The email has already been taken.',
        'phone_number.unique_phone_number' => 'The phone number has already been taken.',
        'role.required' => 'Select the user\'s role',
      ]
    );

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

    $password = Hash::make('Secret!');

    $user = User::where('email', $request->email)->first();

    if (!$user) {
      $phone_number = strlen($request->phone_number) <= 9 ? $request->phone_number : substr($request->phone_number, -9);
      $phone_number = $request->country_code . '' . $phone_number;
      $user = User::create([
        'email' => $request->email,
        'name' => $request->name,
        'phone_number' => $phone_number,
        'password' => $password,
        'receive_notifications' => $request->receive_notifications,
        'reporting_manager' => $request->reporting_manager,
        'record_visibility' => $request->record_visibility,
        'location_type' => $request->location_type,
        'location' => $request->location,
        'applicable_product' => $request->applicable_product,
        'module' => 'bank',
      ]);
    }

    if ($request->has('role') && !empty($request->role)) {
      $role_name = PermissionData::find($request->role)->RoleName;
      // Assign Role
      $role = Role::where('name', $role_name)
        ->where('guard_name', 'web')
        ->first();

      if ($role) {
        $user->assignRole($role);
      }
    }

    $bank_user = BankUser::firstOrCreate([
      'bank_id' => $bank->id,
      'user_id' => $user->id,
      'active' => $bank_users->count() > 0 ? false : true,
    ]);

    $message = '';

    if ($bank_users->count() > 0) {
      ProposedConfigurationChange::create([
        'user_id' => auth()->id(),
        'modeable_type' => Bank::class,
        'modeable_id' => $bank->id,
        'configurable_type' => BankUser::class,
        'configurable_id' => $bank_user->id,
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

    if ($bank_users->count() <= 0) {
      $link['Dashboard'] = URL::temporarySignedRoute('auth.reset-password', now()->addHours(24), ['id' => $user->id]);
      SendMail::dispatchAfterResponse($user->email, 'MappedToCompany', [
        'type' => 'Bank',
        'data' => [
          'bank' => $bank->name,
          'name' => $user->name,
          'email' => $user->email,
          'password' => $password,
          'links' => $link,
        ],
      ]);
    }

    activity($bank->id)
      ->causedBy(auth()->user())
      ->performedOn($bank)
      ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
      ->log('mapped user ' . $user->name . ' to the bank');

    toastr()->success('', $message);

    return redirect()->route('configurations.users', ['bank' => $bank]);
  }

  public function editUser(Bank $bank, User $user)
  {
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

    // Get logged in user permissions names
    $user_permissions = auth()
      ->user()
      ->roles->map(fn($role) => $role->permissions->pluck('name'));

    $roles = PermissionData::where('RoleTypeName', 'Bank')
      ->where(function ($query) use ($bank) {
        $query->where('bank_id', $bank->id)->orWhere('bank_id', null);
      })
      // Ensure Roles is not more powerful than what the logged in user can assign.
      ->whereHas('roleIDs', function ($query) use ($user_permissions) {
        $query->whereHas('AccessRightGroup', function ($query) use ($user_permissions) {
          $query->whereIn('name', $user_permissions[0]);
        });
      })
      ->select('id', 'RoleName')
      ->get();

    $products = ['vendor_financing' => 'Vendor Financing', 'dealer_financing' => 'Dealer Financing'];
    $reporting_managers = User::whereNotIn('id', [auth()->id(), $user->id])
      ->where('is_active', true)
      ->whereIn('id', $bank->users->pluck('id'))
      ->get();
    $visibilities = ['All Records', 'My Records', 'My and My Reportee Records'];
    $location_types = ['Country', 'City', 'Branch'];
    $notification_channels = ['email' => 'Email', 'sms' => 'SMS', 'email_and_sms' => 'Email and SMS'];
    $branches = BankBranch::where('status', 'active')
      ->where('bank_id', $bank->id)
      ->get();

    return view('content.bank.configurations.edit-user', [
      'bank' => $bank,
      'user' => $user,
      'roles' => $roles,
      'products' => $products,
      'reporting_managers' => $reporting_managers,
      'visibilities' => $visibilities,
      'location_types' => $location_types,
      'notification_channels' => $notification_channels,
      'branches' => $branches,
    ]);
  }

  public function updateUser(Bank $bank, Request $request, User $user)
  {
    // Check if user can edit other users
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Add/Edit Users')
    ) {
      toastr()->error('', 'You don\'t have the permission to perform this action');
      return back();
    }

    $request->validate([
      'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
      'name' => ['required'],
      'phone_number' => ['required'],
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

    if ($bank_users->count() <= 0) {
      $user->update([
        'email' => $request->email,
        'name' => $request->name,
        'phone_number' => $request->phone_number,
        'receive_notifications' => $request->receive_notifications,
        'reporting_manager' => $request->reporting_manager,
        'record_visibility' => $request->record_visibility,
        'location_type' => $request->location_type,
        'location' => $request->location,
        'applicable_product' => $request->applicable_product,
      ]);

      if ($request->has('role') && !empty($request->role)) {
        $role_name = PermissionData::find($request->role)->RoleName;
        // Assign Role
        $role = Role::where('name', $role_name)
          ->where('guard_name', 'web')
          ->first();

        if ($role) {
          $user->assignRole($role);
        }
      }
    } else {
      $user->email = $request->email;
      $user->name = $request->name;
      $user->phone_number = $request->phone_number;
      $user->receive_notifications = $request->receive_notifications;
      $user->reporting_manager = $request->reporting_manager;
      $user->record_visibility = $request->record_visibility;
      $user->location_type = $request->location_type;
      $user->location = $request->location;
      $user->applicable_product = $request->applicable_product;
      $update_data['User'] = $user->getDirty();
      if ($request->has('role') && !empty($request->role)) {
        $role_name = PermissionData::find($request->role)->RoleName;
        // Assign Role
        $role = Role::where('name', $role_name)
          ->where('guard_name', 'web')
          ->first();

        if ($role) {
          $update_data['Role'] = $role_name;
        }
      }
    }

    BankUser::firstOrCreate([
      'bank_id' => $bank->id,
      'user_id' => $user->id,
      'active' => $bank_users->count() > 0 ? false : true,
    ]);

    $message = '';

    if ($bank_users->count() > 0) {
      UserChange::create([
        'user_id' => $user->id,
        'created_by' => auth()->id(),
        'changes' => $update_data,
      ]);
      $message = 'User edited. Awaiting approval.';
    } else {
      $user->save();
      $message = 'User edited successfully';
    }

    if ($bank_users->count() > 0) {
      foreach ($bank_users as $user) {
        if ($user->user->id != $user->id) {
          // Notify other bank users
          if ($user->id != $user->id) {
            $user->user->notify(new BankUserActivation($user->user));
          }
        }
      }
    }

    if ($bank_users->count() <= 0) {
      $link['Bank Dashboard'] = URL::temporarySignedRoute('auth.reset-password', now()->addHour(), ['id' => $user->id]);
      SendMail::dispatchAfterResponse($user->email, 'MappedToCompany', [
        'type' => 'Bank',
        'data' => [
          'bank' => $bank->name,
          'name' => $user->name,
          'email' => $user->email,
          'password' => '',
          'links' => $link,
        ],
      ]);
    }

    activity($bank->id)
      ->causedBy(auth()->user())
      ->performedOn($bank)
      ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
      ->log('edited bank user ' . $user->name);

    if ($request->wantsJson()) {
      return response()->json(['message' => $message]);
    }

    toastr()->success('', $message);

    return redirect()->route('configurations.users', ['bank' => $bank]);
  }

  public function approveChange(Bank $bank, Request $request, User $user)
  {
    $request->validate([
      'status' => ['required', 'in:approved,rejected'],
    ]);

    $user_change = UserChange::where('user_id', $user->id)->first();

    if ($request->status == 'approved') {
      foreach ($user_change->changes as $key => $changes) {
        if ($key == 'User') {
          foreach ($changes as $user_key => $change) {
            $user->update([
              $user_key => $change,
            ]);
          }
        }
        if ($key == 'Role') {
          // Delete Current Role
          $current_roles = $user->roles->pluck('name');
          foreach ($current_roles as $role) {
            $user->removeRole($role);
          }
          $role_name = PermissionData::where('RoleName', $changes)->first()->RoleName;
          // Assign Role
          $role = Role::where('name', $role_name)
            ->where('guard_name', 'web')
            ->first();

          if ($role) {
            $user->assignRole($role);
          }
        }
        // For company users
        // Authorization Group
        if ($key == 'Authorization Group') {
          CompanyUserAuthorizationGroup::updateOrCreate(
            [
              'user_id' => $changes['user_id'],
              'company_id' => $changes['company_id'],
              'program_type_id' => $changes['program_type_id'],
            ],
            [
              'group_id' => $changes['group_id'],
            ]
          );
        }
        // Dealer Authorization Group
        if ($key == 'Dealer Authorization Group') {
          CompanyUserAuthorizationGroup::updateOrCreate(
            [
              'user_id' => $changes['user_id'],
              'company_id' => $changes['company_id'],
              'program_type_id' => $changes['program_type_id'],
            ],
            [
              'group_id' => $changes['group_id'],
            ]
          );
        }
        // Resending Password Reset link
        if ($key == 'Resend Link') {
          $company = Company::find($changes['company_id']);
          if ($company) {
            $link['Dashboard'] = URL::temporarySignedRoute('auth.reset-password', now()->addHours(24), [
              'id' => $user->id,
            ]);
            SendMail::dispatchAfterResponse($user->email, 'MappedToCompany', [
              'type' => 'Company',
              'data' => ['company' => $company->name, 'name' => $user->name, 'links' => $link],
            ]);
          }
        }
      }
      $message = 'User updated';
    } else {
      $message = 'Changes discarded';
    }

    $user_change->delete();

    return response()->json(['message' => $message]);
  }

  public function updateUserStatus(Bank $bank, User $user, $status)
  {
    $bank_user = BankUser::where('user_id', $user->id)
      ->where('bank_id', $bank->id)
      ->first();

    $bank_users = BankUser::whereNotIn('user_id', [$user->id, auth()->id()])
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query
              ->where('name', 'Manage Platform Configurations')
              ->orWhere('name', 'Manage Product Configurations')
              ->orWhere('name', 'Approve Product Configurations');
          });
        });
      })
      ->where('bank_id', $bank->id)
      ->get();

    $bank_user->active = !$bank_user->active;

    $message = '';

    if ($bank_users->count() > 0) {
      ProposedConfigurationChange::create([
        'user_id' => auth()->id(),
        'modeable_type' => Bank::class,
        'modeable_id' => $bank->id,
        'configurable_type' => BankUser::class,
        'configurable_id' => $bank_user->id,
        'old_value' => $bank_user->active ? 0 : 1,
        'new_value' => $bank_user->active ? 1 : 0,
        'field' => 'active',
        'description' => $bank_user->active
          ? 'Change user ' . $bank_user->user->name . ' status from inactive to active'
          : 'Change user ' . $bank_user->user->name . ' status from active to inactive',
      ]);
      $message = 'Change in configuration awaiting approval';
    } else {
      $bank_user->save();
      $message = 'User status changed';
    }

    if ($bank_users->count() > 0) {
      foreach ($bank_users as $user) {
        if ($user->user->id != $bank_user->id) {
          // Notify other bank users
          if ($user->id != $bank_user->id) {
            $user->user->notify(new BankUserActivation($user->user));
          }
        }
      }
    }

    if (request()->wantsJson()) {
      return response()->json(['user' => $bank_user, 'message' => $message], 200);
    }
  }

  public function storeRole(Request $request, Bank $bank)
  {
    $request->validate([
      'role_name' => ['required', 'unique:roles,name'],
      'role_type' => ['required'],
      'role_description' => ['required'],
    ]);

    $bank_users = BankUser::whereNotIn('user_id', [auth()->id()])
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where('name', 'Manage Roles');
          });
        });
      })
      ->where('bank_id', $bank->id)
      ->get();

    try {
      DB::beginTransaction();

      $role = PermissionData::create([
        'RoleName' => $request->input('role_name'),
        'RoleTypeName' => $request->input('role_type'),
        'RoleDescription' => $request->input('role_description'),
        'bank_id' => $bank->id,
        'user_id' => auth()->id(),
        'status' => $bank_users->count() > 0 ? 'pending' : 'approved',
      ]);

      foreach ($request->input('permission_ids') as $roleID) {
        $role->roleIDs()->create([
          'permission_data_id' => $role->id,
          'access_right_group_id' => $roleID,
        ]);
      }

      $created_role = Role::create([
        'name' => $request->role_name,
        'guard_name' => 'web',
      ]);

      foreach ($request->permission_ids as $role_id) {
        $access_right_group = AccessRightGroup::find($role_id);
        $permission = Permission::updateOrCreate([
          'name' => $access_right_group->name,
          'guard_name' => 'web',
        ]);

        $created_role->givePermissionTo($permission);
      }

      DB::commit();

      toastr()->success('', 'Role created successfully');
    } catch (\Throwable $th) {
      info($th);
      DB::rollback();
      toastr()->error('', 'Failed to create role');
    }

    return back();
  }

  public function updateRole(Request $request, Bank $bank, PermissionData $role)
  {
    $request->validate([
      'role_name' => ['required'],
      'role_type' => ['required'],
      'role_description' => ['required'],
    ]);

    // Delete current changes
    RoleChange::where('permission_data_id', $role->id)->delete();

    $update_data = [];

    if ($role->RoleName !== $request->input('role_name')) {
      $update_data['RoleName'] = $request->input('role_name');
    }

    if ($role->RoleDescription !== $request->input('role_description')) {
      $update_data['RoleDescription'] = $request->input('role_description');
    }

    $permission_changes = [];
    foreach ($request->input('permission_ids') as $roleID) {
      // Check if the roleID already exists in the roleIDs
      $existingRoleID = $role
        ->roleIDs()
        ->where('access_right_group_id', $roleID)
        ->first();

      if (!$existingRoleID) {
        // If it doesn't exist, add it to the permission_changes array
        array_push($permission_changes, $roleID);
      }
    }

    if (count($permission_changes) > 0) {
      $update_data['additional_permissions'] = $permission_changes;
    }

    $removed_permissions = $role
      ->roleIDs()
      ->whereNotIn('access_right_group_id', $request->input('permission_ids'))
      ->pluck('access_right_group_id');

    if ($removed_permissions->count() > 0) {
      $update_data['removed_permissions'] = $removed_permissions->toArray();
    }

    RoleChange::create([
      'permission_data_id' => $role->id,
      'created_by' => auth()->id(),
      'changes' => json_encode($update_data),
    ]);

    toastr()->success('', 'Role Update Sent for Approval');

    return back();
  }

  public function updateRoleStatus(Request $request, Bank $bank, PermissionData $role)
  {
    $role->update([
      'status' => $request->status,
    ]);

    toastr()->success('', 'Role Updated Successfully');

    return back();
  }

  public function approveRoleChanges(Bank $bank, PermissionData $role, $status)
  {
    $current_role_name = $role->RoleName;

    if ($status == 'approve') {
      // Approve the role
      $changes = $role->change;
      if ($changes) {
        if (array_key_exists('RoleName', $changes->changes)) {
          // Update the role name
          $role->RoleName = $changes->changes['RoleName'];
        }
        if (array_key_exists('RoleDescription', $changes->changes)) {
          // Update the role description
          $role->RoleDescription = $changes->changes['RoleDescription'];
        }

        $role->save();

        // Update the role IDs
        // Add the added permissions changes to roleIDs
        if (array_key_exists('additional_permissions', $changes->changes)) {
          foreach ($changes->changes['additional_permissions'] as $permission_id) {
            $role->roleIDs()->create([
              'permission_data_id' => $role->id,
              'access_right_group_id' => $permission_id,
            ]);
          }
        }
        // Remove the removed permissions changes from roleIDs
        if (array_key_exists('removed_permissions', $changes->changes)) {
          foreach ($changes->changes['removed_permissions'] as $permission_id) {
            $role
              ->roleIDs()
              ->where('access_right_group_id', $permission_id)
              ->delete();
          }
        }

        $existingRole = Role::where(['name' => $current_role_name, 'guard_name' => 'web'])->first();

        if (!$existingRole) {
          $existingRole = Role::create(['name' => $current_role_name, 'guard_name' => 'web']);
        }

        if ($existingRole->name !== $role->RoleName) {
          $existingRole->name = $role->RoleName;
          $existingRole->save();
        }

        $permissions = [];

        // Update the permissions based on RoleIDs
        foreach ($role->roleIDs as $role_id) {
          $access_right_group = AccessRightGroup::find($role_id->access_right_group_id);

          if ($access_right_group) {
            $permission = Permission::firstOrCreate([
              'name' => $access_right_group->name,
              'guard_name' => 'web',
            ]);

            if ($permission) {
              array_push($permissions, $permission->name);
            }
          }
        }

        // Sync permissions to the existing role
        $existingRole->syncPermissions($permissions);

        // Reset the permission cache
        // This is necessary to ensure the new permissions are recognized by the application
        Artisan::call('permission:cache-reset');

        // Notify the users about the role changes
        $bank_users = BankUser::where('bank_id', $bank->id)
          ->whereHas('user', function ($query) {
            $query->whereHas('roles', function ($query) {
              $query->whereHas('permissions', function ($query) {
                $query->where('name', 'Manage Roles');
              });
            });
          })
          ->get();
        foreach ($bank_users as $user) {
          $user->user->notify(new ChangesApproval([$changes], 'approved'));
        }
        // Notify the user who made the changes
        $changes->user?->notify(new ChangesApproval([$role], 'approved'));

        // Delete the change record
        $changes->delete();

        // Log the activity
        activity($bank->id)
          ->causedBy(auth()->user())
          ->performedOn($role)
          ->withProperties([
            'ip' => request()->ip(),
            'device_info' => request()->userAgent(),
            'user_type' => 'Bank',
          ])
          ->log('approved role changes for ' . $role->RoleName);
      }

      // Show success message
      toastr()->success('', 'Role Changes Approved Successfully');
    } else {
      // Notify the user who made the changes
      $role->change?->user?->notify(new ChangesApproval([$role], 'rejected'));
      // Reject the role
      $role->change?->delete();

      // Log the activity
      activity($bank->id)
        ->causedBy(auth()->user())
        ->performedOn($role)
        ->withProperties([
          'ip' => request()->ip(),
          'device_info' => request()->userAgent(),
          'user_type' => 'Bank',
        ])
        ->log('rejected role changes for ' . $role->RoleName);

      toastr()->error('', 'Role Changes Rejected and Discarded');
    }

    return back();
  }

  public function updatetWithholdingAndGls(Bank $bank, Request $request)
  {
    $bank_user = BankUser::where('bank_id', $bank->id)
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query
              ->where('name', 'Manage Platform Configurations')
              ->orWhere('name', 'Manage Product Configurations')
              ->orWhere('name', 'Approve Product Configurations');
          });
        });
      })
      ->where('user_id', '!=', auth()->id())
      ->where('active', true)
      ->get();

    foreach ($request->bank_gl_account_name as $key => $bank_account_name) {
      $bank_payment_account = BankPaymentAccount::where('id', $key)
        ->where('bank_id', $bank->id)
        ->first();
      if ($bank_payment_account) {
        $current_account_name = $bank_payment_account->account_name;
        $current_account_number = $bank_payment_account->account_number;

        $bank_payment_account->account_name = $bank_account_name;
        $bank_payment_account->account_number = $request->bank_gl_account_number[$key];

        if ($bank_payment_account->isDirty('account_name') || $bank_payment_account->isDirty('account_number')) {
          if ($bank_user->count() > 0) {
            if ($bank_payment_account->isDirty('account_name')) {
              ProposedConfigurationChange::create([
                'user_id' => auth()->id(),
                'modeable_type' => Bank::class,
                'modeable_id' => $bank->id,
                'configurable_type' => BankPaymentAccount::class,
                'configurable_id' => $bank_payment_account->id,
                'old_value' => $current_account_name,
                'new_value' => $bank_account_name,
                'field' => 'account_name',
                'description' => 'Changed account name from ' . $current_account_name . ' to ' . $bank_account_name,
              ]);
            }
            if ($bank_payment_account->isDirty('account_number')) {
              ProposedConfigurationChange::create([
                'user_id' => auth()->id(),
                'modeable_type' => Bank::class,
                'modeable_id' => $bank->id,
                'configurable_type' => BankPaymentAccount::class,
                'configurable_id' => $bank_payment_account->id,
                'old_value' => $current_account_number,
                'new_value' => $request->bank_gl_account_number[$key],
                'field' => 'account_number',
                'description' =>
                  'Changed account number from ' .
                  $current_account_number .
                  ' to ' .
                  $request->bank_gl_account_number[$key] .
                  ' for ' .
                  $bank_payment_account->account_name,
              ]);
            }
          } else {
            $bank_payment_account->save();
          }
        }
      } else {
        $bank_payment_account = $bank->paymentAccounts()->create([
          'account_name' => $bank_account_name,
          'account_number' => $request->bank_gl_account_number[$key],
          'is_active' => false,
        ]);

        if ($bank_user->count() > 0) {
          ProposedConfigurationChange::create([
            'user_id' => auth()->id(),
            'modeable_type' => Bank::class,
            'modeable_id' => $bank->id,
            'configurable_type' => BankPaymentAccount::class,
            'configurable_id' => $bank_payment_account->id,
            'old_value' => 0,
            'new_value' => 1,
            'field' => 'is_active',
            'description' =>
              'Created a new account, account name ' .
              $bank_account_name .
              ', account number ' .
              $request->bank_gl_account_number[$key],
          ]);
        } else {
          $bank_payment_account->update([
            'is_active' => true,
          ]);
        }
      }
    }

    if ($bank_user->count() > 0) {
      foreach ($bank_user as $user) {
        $user->user->notify(new ProductConfigurationUpdation());
      }
      toastr()->success('', 'Configurations sent for approval successfully');
    } else {
      toastr()->success('', 'Configurations updated successfully');
    }

    return back();
  }

  public function updateSpecificConfigurations(Bank $bank, Request $request)
  {
    // Check if user has more than one user
    $bank_user = BankUser::where('bank_id', $bank->id)
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query
              ->where('name', 'Manage Platform Configurations')
              ->orWhere('name', 'Manage Product Configurations')
              ->orWhere('name', 'Approve Product Configurations');
          });
        });
      })
      ->where('user_id', '!=', auth()->id())
      ->where('active', true)
      ->get();

    switch ($request->product) {
      case 'vendor financing':
        $vendor_financing = ProgramType::where('name', Program::VENDOR_FINANCING)->first();

        $bank_configs = BankProductsConfiguration::where('bank_id', $bank->id)
          ->where('product_type_id', $vendor_financing->id)
          ->get();
        foreach ($bank_configs as $config) {
          $current_value = $config->branch_specific ? 'true' : 'false';
          if (
            $request->has('branch_specific_configuration') &&
            array_key_exists($config->id, $request->branch_specific_configuration)
          ) {
            $config->branch_specific = true;
          } else {
            $config->branch_specific = false;
          }
          if ($config->isDirty('branch_specific')) {
            if ($bank_user->count() > 0) {
              ProposedConfigurationChange::create([
                'user_id' => auth()->id(),
                'modeable_type' => Bank::class,
                'modeable_id' => $bank->id,
                'configurable_type' => BankProductsConfiguration::class,
                'configurable_id' => $config->id,
                'old_value' => $current_value,
                'new_value' => $config->branch_specific ? 1 : 0,
                'field' => 'branch_specific',
                'description' => $config->branch_specific
                  ? Str::headline($config->name) . ' changed from not branch specific to branch specific'
                  : Str::headline($config->name) . ' changed from branch specific to not branch specific',
              ]);
            } else {
              $config->save();
            }
          }
        }

        $bank_repayment_priorities = BankProductRepaymentPriority::where('bank_id', $bank->id)
          ->where('product_type_id', $vendor_financing->id)
          ->get();
        foreach ($bank_repayment_priorities as $key => $repayment_priority) {
          $current_value = $repayment_priority->premature_priority;
          $repayment_priority->premature_priority = $request->premature_priority[$repayment_priority->id];
          if ($repayment_priority->isDirty('premature_priority')) {
            if ($bank_user->count() > 0) {
              ProposedConfigurationChange::create([
                'user_id' => auth()->id(),
                'modeable_type' => Bank::class,
                'modeable_id' => $bank->id,
                'configurable_type' => BankProductRepaymentPriority::class,
                'configurable_id' => $repayment_priority->id,
                'old_value' => $current_value,
                'new_value' => $request->premature_priority[$repayment_priority->id],
                'field' => 'premature_priority',
                'description' =>
                  Str::headline($repayment_priority->particulars) .
                  ' changed from ' .
                  $current_value .
                  ' to ' .
                  $request->premature_priority[$repayment_priority->id],
              ]);
            } else {
              $repayment_priority->save();
            }
          }
          $current_value = $repayment_priority->discount_indicator;
          if (
            $request->has('discount_indicator') &&
            array_key_exists($repayment_priority->id, $request->discount_indicator)
          ) {
            $repayment_priority->discount_indicator = 'discount bearing';
          } else {
            $repayment_priority->discount_indicator = 'non discount bearing';
          }
          if ($repayment_priority->isDirty('discount_indicator')) {
            if ($bank_user->count() > 0) {
              ProposedConfigurationChange::create([
                'user_id' => auth()->id(),
                'modeable_type' => Bank::class,
                'modeable_id' => $bank->id,
                'configurable_type' => BankProductRepaymentPriority::class,
                'configurable_id' => $repayment_priority->id,
                'old_value' => $current_value,
                'new_value' => $current_value == 'discount bearing' ? 'non discount bearing' : 'discount bearing',
                'field' => 'discount_indicator',
                'description' =>
                  $current_value == 'discount bearing'
                    ? Str::headline($repayment_priority->particulars) .
                      ' discount indicator changed from discount bearing to non discount bearing'
                    : Str::headline($repayment_priority->particulars) .
                      ' discount indicator changed from non discount bearing to discount bearing',
              ]);
            } else {
              $repayment_priority->save();
            }
          }
        }

        $bank_general_configurations = BankGeneralProductConfiguration::where('bank_id', $bank->id)
          ->where('product_type_id', $vendor_financing->id)
          ->get();
        foreach ($bank_general_configurations as $general_configuration) {
          switch ($general_configuration->input_type) {
            case 'text':
              $current_value = $general_configuration->value;
              $general_configuration->value = $request->general_configuration[$general_configuration->id];
              if ($general_configuration->isDirty('value')) {
                if ($bank_user->count() > 0) {
                  ProposedConfigurationChange::create([
                    'user_id' => auth()->id(),
                    'modeable_type' => Bank::class,
                    'modeable_id' => $bank->id,
                    'configurable_type' => BankGeneralProductConfiguration::class,
                    'configurable_id' => $general_configuration->id,
                    'old_value' => $current_value,
                    'new_value' => $request->general_configuration[$general_configuration->id],
                    'field' => 'value',
                    'description' =>
                      Str::headline($general_configuration->name) .
                      ' changed from ' .
                      $current_value .
                      ' to ' .
                      $request->general_configuration[$general_configuration->id],
                  ]);
                } else {
                  $general_configuration->save();
                }
              }
              break;
            case 'number':
              $current_value = $general_configuration->value;
              $general_configuration->value = $request->general_configuration[$general_configuration->id];
              if ($general_configuration->isDirty('value')) {
                if ($bank_user->count() > 0) {
                  ProposedConfigurationChange::create([
                    'user_id' => auth()->id(),
                    'modeable_type' => Bank::class,
                    'modeable_id' => $bank->id,
                    'configurable_type' => BankGeneralProductConfiguration::class,
                    'configurable_id' => $general_configuration->id,
                    'old_value' => $current_value,
                    'new_value' => $request->general_configuration[$general_configuration->id],
                    'field' => 'value',
                    'description' =>
                      Str::headline($general_configuration->name) .
                      ' changed from ' .
                      $current_value .
                      ' to ' .
                      $request->general_configuration[$general_configuration->id],
                  ]);
                } else {
                  $general_configuration->save();
                }
              }
              break;
            case 'select':
              $current_value = $general_configuration->value;
              $general_configuration->value = $request->general_configuration[$general_configuration->id];
              if ($general_configuration->isDirty('value')) {
                if ($bank_user->count() > 0) {
                  ProposedConfigurationChange::create([
                    'user_id' => auth()->id(),
                    'modeable_type' => Bank::class,
                    'modeable_id' => $bank->id,
                    'configurable_type' => BankGeneralProductConfiguration::class,
                    'configurable_id' => $general_configuration->id,
                    'old_value' => $current_value,
                    'new_value' => $request->general_configuration[$general_configuration->id],
                    'field' => 'value',
                    'description' =>
                      Str::headline($general_configuration->name) .
                      ' changed from ' .
                      $current_value .
                      ' to ' .
                      $request->general_configuration[$general_configuration->id],
                  ]);
                } else {
                  $general_configuration->save();
                }
              }
              break;
            case 'select-multiple':
              $current_value = $general_configuration->value;
              $old_value = explode(
                ',',
                str_replace('"', '', str_replace(']', '', str_replace('[', '', $general_configuration->value)))
              );
              $general_configuration->value = $request->general_configuration[$general_configuration->id];
              if ($request->general_configuration[$general_configuration->id] != $old_value) {
                if ($bank_user->count() > 0) {
                  ProposedConfigurationChange::create([
                    'user_id' => auth()->id(),
                    'modeable_type' => Bank::class,
                    'modeable_id' => $bank->id,
                    'configurable_type' => BankGeneralProductConfiguration::class,
                    'configurable_id' => $general_configuration->id,
                    'old_value' => $current_value,
                    'new_value' => json_encode($request->general_configuration[$general_configuration->id]),
                    'field' => 'value',
                    'description' =>
                      Str::headline($general_configuration->name) .
                      ' changed from ' .
                      str_replace(']', '', str_replace('[', '', str_replace("\"", '', $current_value))) .
                      ' days to ' .
                      str_replace(
                        ']',
                        '',
                        str_replace(
                          '[',
                          '',
                          str_replace(
                            "\"",
                            '',
                            json_encode($request->general_configuration[$general_configuration->id])
                          )
                        )
                      ) .
                      ' days',
                  ]);
                } else {
                  $general_configuration->save();
                }
              }
              break;
            case 'checkbox':
              $current_value = $general_configuration->value;
              $options = $general_configuration->input_options;
              if (
                $request->has('general_configuration') &&
                array_key_exists($general_configuration->id, $request->general_configuration)
              ) {
                $general_configuration->value = $options[0];
              } else {
                $general_configuration->value = $options[1];
              }
              if ($general_configuration->isDirty('value')) {
                if ($bank_user->count() > 0) {
                  ProposedConfigurationChange::create([
                    'user_id' => auth()->id(),
                    'modeable_type' => Bank::class,
                    'modeable_id' => $bank->id,
                    'configurable_type' => BankGeneralProductConfiguration::class,
                    'configurable_id' => $general_configuration->id,
                    'old_value' => $current_value,
                    'new_value' =>
                      $request->has('general_configuration') &&
                      array_key_exists($general_configuration->id, $request->general_configuration) &&
                      $general_configuration->value == $options[0]
                        ? $options[0]
                        : $options[1],
                    'field' => 'value',
                    'description' =>
                      $request->has('general_configuration') &&
                      array_key_exists($general_configuration->id, $request->general_configuration) &&
                      $general_configuration->value == $options[0]
                        ? Str::headline($general_configuration->name) .
                          ' changed from ' .
                          $options[1] .
                          ' to ' .
                          $options[0]
                        : Str::headline($general_configuration->name) .
                          ' changed from ' .
                          $options[0] .
                          ' to ' .
                          $options[1],
                  ]);
                } else {
                  $general_configuration->save();
                }
              }
              break;
            default:
              # code...
              break;
          }
        }
        break;
      case 'dealer financing':
        $dealer_financing = ProgramType::where('name', Program::DEALER_FINANCING)->first();

        $bank_configs = BankProductsConfiguration::where('bank_id', $bank->id)
          ->where('product_type_id', $dealer_financing->id)
          ->get();
        foreach ($bank_configs as $config) {
          $current_value = $config->branch_specific;
          if (
            $request->has('branch_specific_configuration') &&
            array_key_exists($config->id, $request->branch_specific_configuration)
          ) {
            $config->branch_specific = true;
          } else {
            $config->branch_specific = false;
          }
          if ($config->isDirty('branch_specific')) {
            if ($bank_user->count() > 0) {
              ProposedConfigurationChange::create([
                'user_id' => auth()->id(),
                'modeable_type' => Bank::class,
                'modeable_id' => $bank->id,
                'configurable_type' => BankProductsConfiguration::class,
                'configurable_id' => $config->id,
                'old_value' => $current_value,
                'new_value' => $config->branch_specific ? 1 : 0,
                'field' => 'branch_specific',
                'description' => $config->branch_specific
                  ? Str::headline($config->name) . ' changed from not branch specific to branch specific'
                  : Str::headline($config->name) . ' changed from branch specific to not branch specific',
              ]);
            } else {
              $config->save();
            }
          }
        }

        $bank_repayment_priorities = BankProductRepaymentPriority::where('bank_id', $bank->id)
          ->where('product_type_id', $dealer_financing->id)
          ->get();
        foreach ($bank_repayment_priorities as $key => $repayment_priority) {
          $current_premature_priority_value = $repayment_priority->premature_priority;
          $current_npa_priority_value = $repayment_priority->npa_priority;
          $repayment_priority->premature_priority = $request->premature_priority[$repayment_priority->id];
          $repayment_priority->npa_priority = $request->npa_priority[$repayment_priority->id];

          if ($repayment_priority->isDirty('premature_priority') || $repayment_priority->isDirty('npa_priority')) {
            if ($bank_user->count() > 0) {
              if ($repayment_priority->isDirty('premature_priority')) {
                ProposedConfigurationChange::create([
                  'user_id' => auth()->id(),
                  'modeable_type' => Bank::class,
                  'modeable_id' => $bank->id,
                  'configurable_type' => BankProductRepaymentPriority::class,
                  'configurable_id' => $repayment_priority->id,
                  'old_value' => $current_premature_priority_value,
                  'new_value' => $request->premature_priority[$repayment_priority->id],
                  'field' => 'premature_priority',
                  'description' =>
                    Str::headline($repayment_priority->particulars) .
                    ' changed from ' .
                    $current_premature_priority_value .
                    ' to ' .
                    $request->premature_priority[$repayment_priority->id],
                ]);
              }
              if ($repayment_priority->isDirty('npa_priority')) {
                ProposedConfigurationChange::create([
                  'user_id' => auth()->id(),
                  'modeable_type' => Bank::class,
                  'modeable_id' => $bank->id,
                  'configurable_type' => BankProductRepaymentPriority::class,
                  'configurable_id' => $repayment_priority->id,
                  'old_value' => $current_npa_priority_value,
                  'new_value' => $request->npa_priority[$repayment_priority->id],
                  'field' => 'npa_priority',
                  'description' =>
                    Str::headline($repayment_priority->particulars) .
                    ' changed from ' .
                    $repayment_priority->npa_priority .
                    ' to ' .
                    $request->npa_priority[$repayment_priority->id],
                ]);
              }
            } else {
              $repayment_priority->save();
            }
          }

          $current_value = $repayment_priority->discount_indicator;
          if (
            $request->has('discount_indicator') &&
            array_key_exists($repayment_priority->id, $request->discount_indicator)
          ) {
            $repayment_priority->discount_indicator = 'discount bearing';
          } else {
            $repayment_priority->discount_indicator = 'non discount bearing';
          }
          if ($repayment_priority->isDirty('discount_indicator')) {
            if ($bank_user->count() > 0) {
              ProposedConfigurationChange::create([
                'user_id' => auth()->id(),
                'modeable_type' => Bank::class,
                'modeable_id' => $bank->id,
                'configurable_type' => BankProductRepaymentPriority::class,
                'configurable_id' => $repayment_priority->id,
                'old_value' => $current_value,
                'new_value' => $current_value == 'discount bearing' ? 'non discount bearing' : 'discount bearing',
                'field' => 'discount_indicator',
                'description' =>
                  $current_value == 'discount bearing'
                    ? Str::headline($repayment_priority->particulars) .
                      ' discount indicator changed from discount bearing to non discount bearing'
                    : Str::headline($repayment_priority->particulars) .
                      ' discount indicator changed from non discount bearing to discount bearing',
              ]);
            } else {
              $repayment_priority->save();
            }
          }
        }

        $bank_general_configurations = BankGeneralProductConfiguration::where('bank_id', $bank->id)
          ->where('product_type_id', $dealer_financing->id)
          ->get();
        foreach ($bank_general_configurations as $general_configuration) {
          switch ($general_configuration->input_type) {
            case 'text':
              $current_value = $general_configuration->value;
              $general_configuration->value = $request->general_configuration[$general_configuration->id];
              if ($general_configuration->isDirty('value')) {
                if ($bank_user->count() > 0) {
                  ProposedConfigurationChange::create([
                    'user_id' => auth()->id(),
                    'modeable_type' => Bank::class,
                    'modeable_id' => $bank->id,
                    'configurable_type' => BankGeneralProductConfiguration::class,
                    'configurable_id' => $general_configuration->id,
                    'old_value' => $current_value,
                    'new_value' => $request->general_configuration[$general_configuration->id],
                    'field' => 'value',
                    'description' =>
                      Str::headline($general_configuration->name) .
                      ' changed from ' .
                      $current_value .
                      ' to ' .
                      $request->general_configuration[$general_configuration->id],
                  ]);
                } else {
                  $general_configuration->save();
                }
              }
              break;
            case 'number':
              $current_value = $general_configuration->value;
              $general_configuration->value = $request->general_configuration[$general_configuration->id];
              if ($general_configuration->isDirty('value')) {
                if ($bank_user->count() > 0) {
                  ProposedConfigurationChange::create([
                    'user_id' => auth()->id(),
                    'modeable_type' => Bank::class,
                    'modeable_id' => $bank->id,
                    'configurable_type' => BankGeneralProductConfiguration::class,
                    'configurable_id' => $general_configuration->id,
                    'old_value' => $current_value,
                    'new_value' => $request->general_configuration[$general_configuration->id],
                    'field' => 'value',
                    'description' =>
                      Str::headline($general_configuration->name) .
                      ' changed from ' .
                      $current_value .
                      ' to ' .
                      $request->general_configuration[$general_configuration->id],
                  ]);
                } else {
                  $general_configuration->save();
                }
              }
              break;
            case 'select':
              $current_value = $general_configuration->value;
              $general_configuration->value = $request->general_configuration[$general_configuration->id];
              if ($general_configuration->isDirty('value')) {
                if ($bank_user->count() > 0) {
                  ProposedConfigurationChange::create([
                    'user_id' => auth()->id(),
                    'modeable_type' => Bank::class,
                    'modeable_id' => $bank->id,
                    'configurable_type' => BankGeneralProductConfiguration::class,
                    'configurable_id' => $general_configuration->id,
                    'old_value' => $current_value,
                    'new_value' => $request->general_configuration[$general_configuration->id],
                    'field' => 'value',
                    'description' =>
                      Str::headline($general_configuration->name) .
                      ' changed from ' .
                      $current_value .
                      ' to ' .
                      $request->general_configuration[$general_configuration->id],
                  ]);
                } else {
                  $general_configuration->save();
                }
              }
              break;
            case 'select-multiple':
              $current_value = $general_configuration->value;
              $old_value = explode(
                ',',
                str_replace('"', '', str_replace(']', '', str_replace('[', '', $general_configuration->value)))
              );
              $general_configuration->value = $request->general_configuration[$general_configuration->id];
              if ($request->general_configuration[$general_configuration->id] != $old_value) {
                if ($bank_user->count() > 0) {
                  ProposedConfigurationChange::create([
                    'user_id' => auth()->id(),
                    'modeable_type' => Bank::class,
                    'modeable_id' => $bank->id,
                    'configurable_type' => BankGeneralProductConfiguration::class,
                    'configurable_id' => $general_configuration->id,
                    'old_value' => $current_value,
                    'new_value' => json_encode($request->general_configuration[$general_configuration->id]),
                    'field' => 'value',
                    'description' =>
                      Str::headline($general_configuration->name) .
                      ' changed from ' .
                      str_replace(']', '', str_replace('[', '', str_replace("\"", '', $current_value))) .
                      ' days to ' .
                      str_replace(
                        ']',
                        '',
                        str_replace(
                          '[',
                          '',
                          str_replace(
                            "\"",
                            '',
                            json_encode($request->general_configuration[$general_configuration->id])
                          )
                        )
                      ) .
                      ' days',
                  ]);
                } else {
                  $general_configuration->save();
                }
              }
              break;
            case 'checkbox':
              $current_value = $general_configuration->value;
              $options = $general_configuration->input_options;
              if (
                $request->has('general_configuration') &&
                array_key_exists($general_configuration->id, $request->general_configuration)
              ) {
                $general_configuration->value = $options[0];
              } else {
                $general_configuration->value = $options[1];
              }
              if ($general_configuration->isDirty('value')) {
                if ($bank_user->count() > 0) {
                  ProposedConfigurationChange::create([
                    'user_id' => auth()->id(),
                    'modeable_type' => Bank::class,
                    'modeable_id' => $bank->id,
                    'configurable_type' => BankGeneralProductConfiguration::class,
                    'configurable_id' => $general_configuration->id,
                    'old_value' => $current_value,
                    'new_value' =>
                      $request->has('general_configuration') &&
                      array_key_exists($general_configuration->id, $request->general_configuration) &&
                      $general_configuration->value == $options[0]
                        ? $options[0]
                        : $options[1],
                    'field' => 'value',
                    'description' =>
                      $request->has('general_configuration') &&
                      array_key_exists($general_configuration->id, $request->general_configuration) &&
                      $general_configuration->value == $options[0]
                        ? Str::headline($general_configuration->name) .
                          ' changed from ' .
                          $options[1] .
                          ' to ' .
                          $options[0]
                        : Str::headline($general_configuration->name) .
                          ' changed from ' .
                          $options[0] .
                          ' to ' .
                          $options[1],
                  ]);
                } else {
                  $general_configuration->save();
                }
              }
              break;
            default:
              # code...
              break;
          }
        }
        break;
      default:
        # code...
        break;
    }

    if (count($request->configuration_id) > 0) {
      foreach ($request->configuration_id as $key => $config_id) {
        if ($config_id) {
          $config = BankProductsConfiguration::find($key);
          $current_value = $config->value;
          $config->value = $config_id;

          if ($config->isDirty('value')) {
            if ($bank_user->count() > 0) {
              ProposedConfigurationChange::create([
                'user_id' => auth()->id(),
                'modeable_type' => Bank::class,
                'modeable_id' => $bank->id,
                'configurable_type' => BankProductsConfiguration::class,
                'configurable_id' => $config->id,
                'old_value' => $current_value,
                'new_value' => $config_id,
                'field' => 'value',
                'description' => Str::headline($config->name) . ' changed from ' . $current_value . ' to ' . $config_id,
              ]);
            } else {
              $config->save();
            }
          }
        }
      }
    }

    if ($bank_user->count() > 0) {
      foreach ($bank_user as $user) {
        SendMail::dispatchAfterResponse($user->user->email, 'ProductConfigurationsApproval', [
          'user_name' => auth()->user()->name,
        ]);
        $user->user->notify(new ProductConfigurationUpdation());
      }
      toastr()->success('', 'Configurations sent for approval successfully');
    } else {
      toastr()->success('', 'Configurations updated successfully');
    }

    return back();
  }

  public function updatePlatformConfigurations(Bank $bank, Request $request)
  {
    $request->validate([
      'logo' => ['nullable', 'max:6000'],
      'favicon' => ['nullable', 'max:6000'],
    ]);

    if ($bank->adminConfiguration) {
      $bank_users = BankUser::where('bank_id', $bank->id)
        ->whereHas('user', function ($query) {
          $query->whereHas('roles', function ($query) {
            $query->whereHas('permissions', function ($query) {
              $query
                ->where('name', 'Manage Platform Configurations')
                ->orWhere('name', 'Manage Product Configurations')
                ->orWhere('name', 'Approve Product Configurations');
            });
          });
        })
        ->where('user_id', '!=', auth()->id())
        ->where('active', true)
        ->get();

      $admin_configurations = $bank->adminConfiguration;

      if ($bank_users->count() > 0) {
        if ($request->hasFile('logo')) {
          $new_logo = pathinfo($request->logo->store('', 'public'), PATHINFO_BASENAME);
          ProposedConfigurationChange::create([
            'user_id' => auth()->id(),
            'modeable_type' => Bank::class,
            'modeable_id' => $bank->id,
            'configurable_type' => AdminBankConfiguration::class,
            'configurable_id' => $admin_configurations->id,
            'old_value' => $admin_configurations->logo,
            'new_value' => $new_logo,
            'field' => 'logo',
            'description' =>
              'Changed Logo to <a class="text-primary" target="_blank" href="' .
              config('app.url') .
              '/storage/' .
              $new_logo .
              '' .
              '">View Logo</a>',
          ]);
        }

        if ($request->hasFile('favicon')) {
          $new_favicon = pathinfo($request->favicon->store('', 'public'), PATHINFO_BASENAME);
          ProposedConfigurationChange::create([
            'user_id' => auth()->id(),
            'modeable_type' => Bank::class,
            'modeable_id' => $bank->id,
            'configurable_type' => AdminBankConfiguration::class,
            'configurable_id' => $admin_configurations->id,
            'new_value' => $admin_configurations->favicon,
            'new_value' => $new_favicon,
            'field' => 'favicon',
            'description' =>
              'Changed Favicon to <a class="text-primary" target="_blank" href="' .
              config('app.url') .
              '/storage/' .
              $new_favicon .
              '' .
              '">View Favicon</a>',
          ]);
        }

        if (
          $request->has('primary_color') &&
          !empty($request->primary_color) &&
          $request->primary_color != '' &&
          $request->primary_color != $admin_configurations->primary_color
        ) {
          ProposedConfigurationChange::create([
            'user_id' => auth()->id(),
            'modeable_type' => Bank::class,
            'modeable_id' => $bank->id,
            'configurable_type' => AdminBankConfiguration::class,
            'configurable_id' => $admin_configurations->id,
            'old_value' => $admin_configurations->primary_color,
            'new_value' => $request->primary_color,
            'field' => 'primary_color',
            'description' => $admin_configurations->primary_color
              ? 'Changed Primary Color from ' . $admin_configurations->primary_color . ' to ' . $request->primary_color
              : 'Changed Primary Color to ' . $request->primary_color,
          ]);
        }

        if (
          $request->has('secondary_color') &&
          !empty($request->secondary_color) &&
          $request->secondary_color != '' &&
          $request->secondary_color != $admin_configurations->secondary_color
        ) {
          ProposedConfigurationChange::create([
            'user_id' => auth()->id(),
            'modeable_type' => Bank::class,
            'modeable_id' => $bank->id,
            'configurable_type' => AdminBankConfiguration::class,
            'configurable_id' => $admin_configurations->id,
            'old_value' => $admin_configurations->secondary_color,
            'new_value' => $request->secondary_color,
            'field' => 'secondary_color',
            'description' => $admin_configurations->secondary
              ? 'Changed Secondary Color from ' .
                $admin_configurations->secondary_color .
                ' to ' .
                $request->secondary_color
              : 'Changed Secondary Color to ' . $request->secondary_color,
          ]);
        }

        if (
          $request->has('page_title') &&
          !empty($request->page_title) &&
          $request->page_title != '' &&
          $request->page_title != $admin_configurations->page_title
        ) {
          ProposedConfigurationChange::create([
            'user_id' => auth()->id(),
            'modeable_type' => Bank::class,
            'modeable_id' => $bank->id,
            'configurable_type' => AdminBankConfiguration::class,
            'configurable_id' => $admin_configurations->id,
            'old_value' => $admin_configurations->page_title,
            'new_value' => $request->page_title,
            'field' => 'page_title',
            'description' => $admin_configurations->page_title
              ? 'Changed Page Title from ' . $admin_configurations->page_title . ' to ' . $request->page_title
              : 'Changed Page Title To ' . $request->page_title,
          ]);
        }

        if (
          $request->has('date_format') &&
          !empty($request->date_format) &&
          $request->date_format != '' &&
          // Use getRawOriginal to get unformatted data as is on DB
          $request->date_format != $admin_configurations->getRawOriginal('date_format')
        ) {
          ProposedConfigurationChange::create([
            'user_id' => auth()->id(),
            'modeable_type' => Bank::class,
            'modeable_id' => $bank->id,
            'configurable_type' => AdminBankConfiguration::class,
            'configurable_id' => $admin_configurations->id,
            'old_value' => $admin_configurations->date_format,
            'new_value' => $request->date_format,
            'field' => 'date_format',
            'description' => $admin_configurations->date_format
              ? 'Changed Date Format from ' .
                $admin_configurations->getRawOriginal('date_format') .
                ' to ' .
                $request->date_format
              : 'Changed Date Format To ' . $request->date_format,
          ]);
        }
      } else {
        $admin_configurations->primary_color =
          $request->has('primary_color') && !empty($request->primary_color) && $request->primary_color != ''
            ? $request->primary_color
            : $bank->adminConfiguration->primary_color;
        $admin_configurations->secondary_color =
          $request->has('secondary_color') && !empty($request->secondary_color) && $request->secondary_color != ''
            ? $request->secondary_color
            : $bank->adminConfiguration->secondary_color;
        $admin_configurations->logo = $request->hasFile('logo')
          ? config('app.url') . '/storage/' . pathinfo($request->logo->store('', 'public'), PATHINFO_BASENAME)
          : $bank->adminConfiguration->logo;
        $admin_configurations->favicon = $request->hasFile('favicon')
          ? config('app.url') . '/storage/' . pathinfo($request->favicon->store('', 'public'), PATHINFO_BASENAME)
          : $bank->adminConfiguration->favicon;
        $admin_configurations->page_title =
          $request->has('page_title') && !empty($request->page_title)
            ? $request->page_title
            : $bank->adminConfiguration->page_title;
        $admin_configurations->date_format =
          $request->has('date_format') && !empty($request->date_format)
            ? $request->date_format
            : $bank->adminConfiguration->date_format;

        $admin_configurations->save();
      }
    } else {
      $bank->adminConfiguration()->create([
        'primary_color' =>
          $request->has('primary_color') && !empty($request->primary_color) && $request->primary_color != ''
            ? $request->primary_color
            : null,
        'secondary_color' =>
          $request->has('secondary_color') && !empty($request->secondary_color) && $request->secondary_color != ''
            ? $request->secondary_color
            : null,
        'logo' => $request->hasFile('logo')
          ? config('app.url') . '/storage/' . pathinfo($request->logo->store('', 'public'), PATHINFO_BASENAME)
          : null,
        'favicon' => $request->hasFile('favicon')
          ? config('app.url') . '/storage/' . pathinfo($request->favicon->store('', 'public'), PATHINFO_BASENAME)
          : null,
        'page_title' => $request->has('page_title') && !empty($request->page_title) ? $request->page_title : null,
        'date_format' => $request->has('date_format') && !empty($request->date_format) ? $request->date_format : null,
      ]);
    }

    activity()
      ->causedBy(auth()->user())
      ->performedOn($bank)
      ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent()])
      ->log('updated theme details');

    toastr()->success('', 'Configurations updated successfully');

    return back();
  }

  public function baseRates(Bank $bank)
  {
    $base_rates = $bank->baseRates;

    return view('content.bank.configurations.base-rate', ['bank' => $bank, 'base_rates' => $base_rates]);
  }

  public function storeBaseRates(Bank $bank, Request $request)
  {
    $request->validate([
      'name' => ['required'],
      'rate' => ['required'],
    ]);

    $bank_users = BankUser::whereNotIn('user_id', [auth()->id()])
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where('name', 'Approve Product Configurations');
          });
        });
      })
      ->where('bank_id', $bank->id)
      ->get();

    $new_base_rate = BankBaseRate::create([
      'bank_id' => $bank->id,
      'name' => $request->name,
      'rate' => $request->rate,
      'effective_date' =>
        $request->has('effective_date') && $request->effective_date != ''
          ? Carbon::parse($request->effective_date)->format('Y-m-d')
          : now()->format('Y-m-d'),
      'is_default' => $request->has('is_default') && $request->is_default ? true : false,
      'status' => $bank_users->count() > 0 ? 'inactive' : 'active',
    ]);

    if ($request->has('is_default') && $request->is_default && $bank_users->count() > 0) {
      $base_rates = BankBaseRate::where('bank_id', $bank->id)
        ->where('id', '!=', $new_base_rate->id)
        ->get();

      foreach ($base_rates as $base_rate) {
        $base_rate->is_default = false;
        $base_rate->save();
      }
    }

    if ($bank_users->count() > 0) {
      ProposedConfigurationChange::create([
        'user_id' => auth()->id(),
        'modeable_type' => Bank::class,
        'modeable_id' => $bank->id,
        'configurable_type' => BankBaseRate::class,
        'configurable_id' => $new_base_rate->id,
        'old_value' => 'inactive',
        'new_value' => 'active',
        'field' => 'status',
        'description' => 'Added new base rate ' . $new_base_rate->name,
      ]);

      $message = 'Rate added. Awaiting approval.';
    } else {
      $message = 'Rate added successfully.';
    }

    activity($bank->id)
      ->causedBy(auth()->user())
      ->performedOn($new_base_rate)
      ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
      ->log('added base rate ' . $new_base_rate->name);

    return response()->json(['message' => $message]);
  }

  public function baseRatesData(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $name = $request->query('name');
    $status = $request->query('status');

    $base_rates = BankBaseRate::with('change', 'proposedUpdate')
      ->where('bank_id', $bank->id)
      ->when($name && $name != '', function ($query) use ($name) {
        $query->where('name', 'LIKE', '%' . $name . '%');
      })
      ->when($status && $status != '', function ($query) use ($status) {
        $query->where('status', $status);
      })
      ->paginate($per_page);

    return response()->json($base_rates);
  }

  public function updateBaseRate(Bank $bank, Request $request)
  {
    $request->validate([
      'base_rate_id' => ['required', 'exists:bank_base_rates,id'],
      'name' => ['required'],
      'rate' => ['required'],
    ]);

    $base_rate = BankBaseRate::find($request->base_rate_id);

    // Get checker users
    $bank_users = BankUser::where('bank_id', $bank->id)
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where('name', 'Approve Product Configurations');
          });
        });
      })
      ->where('user_id', '!=', auth()->id())
      ->where('active', true)
      ->count();

    $base_rate->name = $request->name;
    $base_rate->rate = $request->rate;
    $base_rate->effective_date =
      $request->has('effective_date') && $request->effective_date != ''
        ? $request->effective_date
        : $base_rate->effective_rate;
    $base_rate->is_default = $request->has('is_default') && $request->is_default ? true : false;

    if ($bank_users > 0) {
      BankConfigChange::create([
        'configurable_id' => $base_rate->id,
        'configurable_type' => BankBaseRate::class,
        'changes' => $base_rate->getDirty(),
        'created_by' => auth()->id(),
      ]);

      $message = 'Update sent for approval';
    } else {
      $base_rate->save();

      if ($request->has('is_default') && $request->is_default) {
        $base_rates = BankBaseRate::where('bank_id', $bank->id)
          ->where('id', '!=', $base_rate->id)
          ->get();
        foreach ($base_rates as $base_rate) {
          $base_rate->is_default = false;
          $base_rate->save();
        }
      }

      activity($bank->id)
        ->causedBy(auth()->user())
        ->performedOn($base_rate)
        ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
        ->log('updated base rates');

      $message = 'Base rate updated successfully';
    }

    return response()->json(['message' => $message]);
  }

  public function updateActiveStatus(Bank $bank, BankBaseRate $base_rate, $status)
  {
    $base_rate->update([
      'status' => $status,
    ]);

    activity($bank->id)
      ->causedBy(auth()->user())
      ->performedOn($base_rate)
      ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
      ->log('updated base rate status to "' . $status . '"');

    if (!request()->wantsJson()) {
      toastr()->success('', 'Status updated successfully');

      return back();
    }

    return response()->json(['base_rate' => $base_rate], 200);
  }

  public function taxRates(Bank $bank)
  {
    $tax_rates = $bank->tax_rates;

    return view('content.bank.configurations.taxes', ['bank' => $bank, 'tax_rates' => $tax_rates]);
  }

  public function storeTaxRates(Bank $bank, Request $request)
  {
    $request->validate([
      'tax_name' => ['required'],
      'value' => ['required'],
      'account_no' => ['required'],
    ]);

    $bank_users = BankUser::whereNotIn('user_id', [auth()->id()])
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where('name', 'Approve Product Configurations');
          });
        });
      })
      ->where('bank_id', $bank->id)
      ->get();

    $new_tax_rate = BankTaxRate::create([
      'bank_id' => $bank->id,
      'tax_name' => $request->tax_name,
      'value' => $request->value,
      'account_no' => $request->account_no,
      'is_default' => $request->has('is_default') && $request->is_default ? true : false,
      'status' => $bank_users->count() > 0 ? 'inactive' : 'active',
    ]);

    if ($request->has('is_default') && $request->is_default) {
      $tax_rates = BankTaxRate::where('bank_id', $bank->id)
        ->where('id', '!=', $new_tax_rate->id)
        ->get();
      foreach ($tax_rates as $tax_rate) {
        $tax_rate->is_default = false;
        $tax_rate->save();
      }
    }

    if ($bank_users->count() > 0) {
      ProposedConfigurationChange::create([
        'user_id' => auth()->id(),
        'modeable_type' => Bank::class,
        'modeable_id' => $bank->id,
        'configurable_type' => BankTaxRate::class,
        'configurable_id' => $new_tax_rate->id,
        'old_value' => 'inactive',
        'new_value' => 'active',
        'field' => 'status',
        'description' => 'Added new tax rate ' . $new_tax_rate->tax_name,
      ]);

      $message = 'Rate added. Awaiting approval.';
    } else {
      $message = 'Rate added successfully.';
    }

    foreach ($bank_users as $bank_user) {
      if ($bank_user->user->id != auth()->id()) {
        $bank_user->user->notify(new TaxesUpdate($new_tax_rate, 'created'));
      }
    }

    activity($bank->id)
      ->causedBy(auth()->user())
      ->performedOn($new_tax_rate)
      ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
      ->log('added tax rate ' . $new_tax_rate->tax_name);

    return response()->json(['message' => $message]);
  }

  public function taxRatesData(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $name = $request->query('name');
    $status = $request->query('status');

    $tax_rates = BankTaxRate::with('change', 'proposedUpdate')
      ->where('bank_id', $bank->id)
      ->when($name && $name != '', function ($query) use ($name) {
        $query->where('name', 'LIKE', '%' . $name . '%');
      })
      ->when($status && $status != '', function ($query) use ($status) {
        $query->where('status', $status);
      })
      ->paginate($per_page);

    return response()->json($tax_rates);
  }

  public function updateTaxRate(Bank $bank, Request $request)
  {
    $request->validate([
      'tax_rate_id' => ['required', 'exists:bank_tax_rates,id'],
      'tax_name' => ['required'],
      'value' => ['required'],
      'account_no' => ['required'],
    ]);

    $tax_rate = BankTaxRate::find($request->tax_rate_id);

    // Get checker users
    $bank_users = BankUser::where('bank_id', $bank->id)
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where('name', 'Approve Product Configurations');
          });
        });
      })
      ->where('user_id', '!=', auth()->id())
      ->where('active', true)
      ->count();

    $tax_rate->tax_name = $request->tax_name;
    $tax_rate->value = $request->value;
    $tax_rate->account_no = $request->account_no;
    $tax_rate->is_default = $request->has('is_default') && $request->is_default ? true : false;

    if ($bank_users > 0) {
      BankConfigChange::create([
        'configurable_id' => $tax_rate->id,
        'configurable_type' => BankTaxRate::class,
        'changes' => $tax_rate->getDirty(),
        'created_by' => auth()->id(),
      ]);

      $message = 'Update sent for approval';
    } else {
      $tax_rate->save();

      $message = 'Tax rate updated successfully';

      if ($request->has('is_default') && $request->is_default) {
        $tax_rates = BankTaxRate::where('bank_id', $bank->id)
          ->where('id', '!=', $tax_rate->id)
          ->get();
        foreach ($tax_rates as $tax_rate) {
          $tax_rate->is_default = false;
          $tax_rate->save();
        }
      }

      foreach ($bank->users as $user) {
        if ($user->id != auth()->id()) {
          $user->notify(new TaxesUpdate($tax_rate, 'updated'));
        }
      }

      activity($bank->id)
        ->causedBy(auth()->user())
        ->performedOn($tax_rate)
        ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
        ->log('updated tax rates');
    }

    return response()->json(['message' => $message]);
  }

  public function updateTaxRateActiveStatus(Bank $bank, BankTaxRate $tax_rate, $status)
  {
    $tax_rate->update([
      'status' => $status,
    ]);

    activity($bank->id)
      ->causedBy(auth()->user())
      ->performedOn($tax_rate)
      ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
      ->log('updated tax rate status to "' . $status . '"');

    foreach ($bank->users as $user) {
      if ($user->id != auth()->id()) {
        $user->notify(new TaxesUpdate($tax_rate, 'updated'));
      }
    }

    if (!request()->wantsJson()) {
      toastr()->success('', 'Status updated successfully');

      return back();
    }

    return response()->json(['tax_rate' => $tax_rate], 200);
  }

  public function pending(Bank $bank)
  {
    $pending_configurations = ProposedConfigurationChange::with('user')
      ->where('modeable_type', Bank::class)
      ->where('modeable_id', $bank->id)
      ->count();

    return view('content.bank.configurations.pending', [
      'bank' => $bank,
      'pending_configurations' => $pending_configurations,
    ]);
  }

  public function pendingData(Request $request, Bank $bank)
  {
    $per_page = $request->query('per_page');

    $pending_configurations = ProposedConfigurationChange::with('user')
      ->where('modeable_type', Bank::class)
      ->where('modeable_id', $bank->id)
      ->paginate($per_page);

    return response()->json(['data' => $pending_configurations]);
  }

  public function approveConfiguration(Bank $bank, ProposedConfigurationChange $proposed_configuration_change, $status)
  {
    if (
      !auth()
        ->user()
        ->hasAnyPermission([
          'Manage Platform Configurations',
          'Manage Product Configurations',
          'Approve Product Configurations',
        ])
    ) {
      toastr()->error('', 'You are not allowed to perform this action');

      return back();
    }

    $config = $proposed_configuration_change->configurable_type::find($proposed_configuration_change->configurable_id);

    if ($status == 'approve') {
      if ($proposed_configuration_change->field == 'logo') {
        $config->update([
          $proposed_configuration_change->field =>
            config('app.url') . '/storage/' . $proposed_configuration_change->new_value,
        ]);
      } elseif ($proposed_configuration_change->field == 'favicon') {
        $config->update([
          $proposed_configuration_change->field =>
            config('app.url') . '/storage/' . $proposed_configuration_change->new_value,
        ]);
      } else {
        $config->update([
          $proposed_configuration_change->field => $proposed_configuration_change->new_value,
        ]);
      }

      $proposed_configuration_change->user->notify(new ChangesApproval([$config], 'approved'));

      switch ($proposed_configuration_change->configurable_type) {
        case 'App\\Models\\BankUser':
          $bank_user = BankUser::find($proposed_configuration_change->configurable_id);

          $user = User::find($bank_user->user_id);

          $link['Bank Dashboard'] = URL::temporarySignedRoute('auth.reset-password', now()->addHour(), [
            'id' => $user->id,
          ]);

          SendMail::dispatchAfterResponse($user->email, 'MappedToCompany', [
            'type' => 'Bank',
            'data' => ['bank' => $bank->name, 'name' => $user->name, 'email' => $user->email, 'links' => $link],
          ]);

          break;
        case 'App\\Models\\TermsConditionsConfig':
          // If configuration is for terms and conditions, check similar terms and conditions in the bank and make them inactive
          $terms_and_conditions_config = TermsConditionsConfig::find($proposed_configuration_change->configurable_id);
          $bank_terms = TermsConditionsConfig::where('bank_id', $bank->id)
            ->where('id', '!=', $proposed_configuration_change->configurable_id)
            ->where('product_type', $terms_and_conditions_config->product_type)
            ->get();

          foreach ($bank_terms as $bank_term) {
            $bank_term->status = 'inactive';
            $bank_term->save();
          }
          break;
      }

      $proposed_configuration_change->delete();

      activity($bank->id)
        ->causedBy(auth()->user())
        ->performedOn($config)
        ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
        ->log('approved update to changes in configuration');
    } else {
      $proposed_configuration_change->user->notify(new ChangesApproval([$config], 'rejected'));

      $proposed_configuration_change->delete();
      activity($bank->id)
        ->causedBy(auth()->user())
        ->performedOn($config)
        ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
        ->log('rejected update to changes in configuration');
    }

    if (request()->wantsJson()) {
      return response()->json(['message' => 'Configuration updated successfully']);
    }

    toastr()->success('', 'Configurations updated successfully');

    return back();
  }

  public function bulkApproveConfigurations(Request $request, Bank $bank)
  {
    $request->validate([
      'status' => ['required'],
      'configurations' => ['required'],
    ]);

    foreach ($request->configurations as $key => $configuration) {
      $proposed_config = ProposedConfigurationChange::find($configuration);
      $config = $proposed_config->configurable_type::find($proposed_config->configurable_id);

      if ($request->status == 'approve') {
        if ($proposed_config->field == 'logo') {
          $config->update([
            $proposed_config->field => config('app.url') . '/storage/' . $proposed_config->new_value,
          ]);
        } elseif ($proposed_config->field == 'favicon') {
          $config->update([
            $proposed_config->field => config('app.url') . '/storage/' . $proposed_config->new_value,
          ]);
        } else {
          $config->update([
            $proposed_config->field => $proposed_config->new_value,
          ]);
        }

        // If configuration is for terms and conditions, check similar terms and conditions in the bank and make them inactive
        if ($proposed_config->configurable_type === 'App\\Models\\TermsConditionsConfig') {
          $terms_and_conditions_config = TermsConditionsConfig::find($proposed_config->configurable_id);
          $bank_terms = TermsConditionsConfig::where('bank_id', $bank->id)
            ->where('id', '!=', $proposed_config->configurable_id)
            ->where('product_type', $terms_and_conditions_config->product_type)
            ->get();

          foreach ($bank_terms as $bank_term) {
            $bank_term->status = 'inactive';
            $bank_term->save();
          }
        }

        if ($proposed_config->configurable_type === 'App\\Models\\BankUser') {
          $bank_user = BankUser::find($proposed_config->configurable_id);

          $user = User::find($bank_user->user_id);

          $link['Bank Dashboard'] = URL::temporarySignedRoute('auth.reset-password', now()->addHour(), [
            'id' => $user->id,
          ]);

          SendMail::dispatchAfterResponse($user->email, 'MappedToCompany', [
            'type' => 'Bank',
            'data' => ['bank' => $bank->name, 'name' => $user->name, 'email' => $user->email, 'links' => $link],
          ]);
        }

        $proposed_config->user->notify(new ChangesApproval([$config], 'approved'));

        $proposed_config->delete();

        if ($config) {
          activity($bank->id)
            ->causedBy(auth()->user())
            ->performedOn($config)
            ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
            ->log('approved update to changes in configuration');
        }
      } else {
        $proposed_config->user->notify(new ChangesApproval([$config], 'rejected'));

        $proposed_config->delete();

        activity($bank->id)
          ->causedBy(auth()->user())
          ->performedOn($config)
          ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
          ->log('rejected update to changes in configuration');
      }
    }

    toastr()->success('', 'Configurations updated successfully');

    if ($request->wantsJson()) {
      return response()->json(['message' => 'Configurations updated successfully']);
    }

    return back();
  }

  public function updateComplianceDocuments(Request $request, Bank $bank)
  {
    $request->validate([
      'name' => ['required', 'array', 'min:1'],
    ]);

    $bank->requiredDocuments()->delete();

    foreach ($request->name as $key => $value) {
      $bank->requiredDocuments()->create([
        'name' => $value,
      ]);
    }

    toastr()->success('', 'Compliance Documents updated successfully');

    return back();
  }

  public function addComplianceDocument(Request $request, Bank $bank)
  {
    $vendor_financing = ProgramType::where('name', Program::VENDOR_FINANCING)->first();

    $validator = Validator::make(
      $request->all(),
      [
        'name' => ['required'],
        'product_type' => ['nullable', 'exists:program_types,id'],
        'product_code' => ['nullable', 'required_if:product_type,' . $vendor_financing->id, 'exists:program_codes,id'],
      ],
      [
        'name.required' => 'Document name is required',
        'product_code.required_if' => 'Product Code is required if Product Type is ' . $vendor_financing->name,
      ]
    );

    if ($validator->fails()) {
      toastr()->error('', $validator->messages()->first());

      return back();
    }

    $bank->requiredDocuments()->create([
      'name' => $request->name,
      'product_type_id' => $request->product_type,
      'product_code_id' => $request->product_code,
    ]);

    toastr()->success('', 'Added Compliance document successfully');

    return back();
  }

  public function deleteComplianceDocument(Bank $bank, BankDocument $bank_document)
  {
    $bank_document->delete();

    toastr()->success('', 'Compliance Document deleted successfully');

    return back();
  }

  public function holidays(Bank $bank)
  {
    $holidays = $bank->holidays;

    return view('content.bank.configurations.holidays', ['bank' => $bank, 'holidays' => $holidays]);
  }

  public function storeHoliday(Bank $bank, Request $request)
  {
    $request->validate([
      'name' => ['required'],
      'date' => ['required', 'date'],
    ]);

    $bank_users = BankUser::whereNotIn('user_id', [auth()->id()])
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where('name', 'Approve Product Configurations');
          });
        });
      })
      ->where('bank_id', $bank->id)
      ->get();

    $holiday = BankHoliday::create([
      'bank_id' => $bank->id,
      'name' => $request->name,
      'date' => $request->date,
      'status' => $bank_users->count() > 0 ? 'inactive' : 'active',
    ]);

    if ($bank_users->count() > 0) {
      ProposedConfigurationChange::create([
        'user_id' => auth()->id(),
        'modeable_type' => Bank::class,
        'modeable_id' => $bank->id,
        'configurable_type' => BankHoliday::class,
        'configurable_id' => $holiday->id,
        'old_value' => 'inactive',
        'new_value' => 'active',
        'field' => 'status',
        'description' =>
          'Added new holiday ' .
          $holiday->name .
          ' on date ' .
          Carbon::parse($holiday->date)->format($bank->adminConfiguration?->date_format),
      ]);

      $message = 'Holiday added. Awaiting approval.';
    } else {
      $message = 'Holiday added successfully.';
    }

    foreach ($bank_users as $bank_user) {
      if ($bank_user->user->id != auth()->id()) {
        $bank_user->user->notify(new HolidayListUpdate($holiday, 'created'));
      }
    }

    activity($bank->id)
      ->causedBy(auth()->user())
      ->performedOn($holiday)
      ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
      ->log('added holiday ' . $holiday->name);

    return response()->json(['message' => $message]);
  }

  public function holidaysData(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $name = $request->query('name');
    $status = $request->query('status');

    $holidays = BankHoliday::with('change', 'proposedUpdate')
      ->where('bank_id', $bank->id)
      ->when($name && $name != '', function ($query) use ($name) {
        $query->where('name', 'LIKE', '%' . $name . '%');
      })
      ->when($status && $status != '', function ($query) use ($status) {
        $query->where('status', $status);
      })
      ->latest()
      ->paginate($per_page);

    return response()->json($holidays);
  }

  public function updateHoliday(Bank $bank, Request $request)
  {
    $request->validate([
      'edit_id' => ['required', 'exists:bank_holidays,id'],
      'name' => ['required'],
      'date' => ['required'],
    ]);

    // Get checker users
    $bank_users = BankUser::where('bank_id', $bank->id)
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where('name', 'Approve Product Configurations');
          });
        });
      })
      ->where('user_id', '!=', auth()->id())
      ->where('active', true)
      ->get();

    $holiday = BankHoliday::find($request->edit_id);

    $holiday->name = $request->name;
    $holiday->date = $request->date;

    if (count($holiday->getDirty()) == 0) {
      $message = 'No changes were made';
    } else {
      if ($bank_users->count() > 0) {
        BankConfigChange::create([
          'configurable_id' => $holiday->id,
          'configurable_type' => BankHoliday::class,
          'changes' => $holiday->getDirty(),
          'created_by' => auth()->id(),
        ]);
        $message = 'Update sent for approval';
      } else {
        $holiday->save();

        activity($bank->id)
          ->causedBy(auth()->user())
          ->performedOn($holiday)
          ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
          ->log('updated holiday ' . $holiday->name);

        $message = 'Updated holiday ' . $holiday->name . ' successfully';
      }
    }

    return response()->json(['message' => $message]);
  }

  public function updateHolidayActiveStatus(Bank $bank, BankHoliday $bank_holiday, $status)
  {
    $bank_holiday->update([
      'status' => $status,
    ]);

    activity($bank->id)
      ->causedBy(auth()->user())
      ->performedOn($bank_holiday)
      ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
      ->log('updated holiday status to "' . $status . '"');

    if (!request()->wantsJson()) {
      toastr()->success('', 'Status updated successfully');

      return back();
    }

    return response()->json(['holiday' => $bank_holiday], 200);
  }

  public function deleteHoliday(Bank $bank, BankHoliday $holiday)
  {
    foreach ($bank->users as $user) {
      if ($user->id != auth()->id()) {
        $user->notify(new HolidayListUpdate($holiday, 'deleted'));
      }
    }

    $holiday->delete();

    return response()->json(['message' => 'Holiday deleted']);
  }

  public function downloadHolidaysTemplate(Bank $bank)
  {
    if (request()->wantsJson()) {
      return response()->download(public_path('Holidays.csv'));
    }

    return response()->download('Holidays.csv');
  }

  public function importHolidays(Bank $bank, Request $request)
  {
    $request->validate([
      'holidays' => ['required', 'mimes:csv,xlsx'],
    ]);

    $bank_users = BankUser::whereNotIn('user_id', [auth()->id()])
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where('name', 'Approve Product Configurations');
          });
        });
      })
      ->where('bank_id', $bank->id)
      ->get();

    $import = new HolidaysImport($bank, $bank_users->count());
    Excel::import($import, $request->file('holidays')->store('public'));

    if ($request->wantsJson()) {
      if ($import->data > 0) {
        return response()->json(
          [
            'message' => 'Invoices uploaded successfully',
            'uploaded' => $import->data,
            'total_rows' => collect($import->total_rows)->first() - 1,
          ],
          400
        );
      }

      return response()->json(
        [
          'message' => 'No Invoices were uploaded successfully',
          'uploaded' => $import->data,
          'total_rows' => collect($import->total_rows)->first() - 1,
        ],
        400
      );
    }

    toastr()->success('', 'Invoices uploaded successfully');

    return back();
  }

  public function branches(Bank $bank)
  {
    $branches = $bank->branches;

    return view('content.bank.configurations.branches', ['bank' => $bank, 'branches' => $branches]);
  }

  public function storeBranch(Bank $bank, Request $request)
  {
    $request->validate([
      'name' => ['required'],
    ]);

    $bank_users = BankUser::whereNotIn('user_id', [auth()->id()])
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where('name', 'Approve Product Configurations');
          });
        });
      })
      ->where('bank_id', $bank->id)
      ->get();

    $branch = BankBranch::create([
      'bank_id' => $bank->id,
      'name' => $request->name,
      'code' => $request->code,
      'location' => $request->location,
      'city' => $request->city,
      'address' => $request->address,
      'status' => $bank_users->count() > 0 ? 'inactive' : 'active',
      'created_by' => auth()->id(),
    ]);

    if ($bank_users->count() > 0) {
      ProposedConfigurationChange::create([
        'user_id' => auth()->id(),
        'modeable_type' => Bank::class,
        'modeable_id' => $bank->id,
        'configurable_type' => BankBranch::class,
        'configurable_id' => $branch->id,
        'old_value' => 'inactive',
        'new_value' => 'active',
        'field' => 'status',
        'description' => 'Added new branch ' . $branch->name,
      ]);

      $message = 'Branch added. Awaiting approval.';
    } else {
      $message = 'Branch added successfully.';
    }

    foreach ($bank_users as $bank_user) {
      if ($bank_user->user->id != auth()->id()) {
        $bank_user->user->notify(new BranchesUpdate($branch, 'created'));
      }
    }

    activity($bank->id)
      ->causedBy(auth()->user())
      ->performedOn($branch)
      ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
      ->log('added branch ' . $branch->name);

    return response()->json(['message' => $message]);
  }

  public function branchesData(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $name = $request->query('name');
    $status = $request->query('status');

    $branches = BankBranch::with('change', 'proposedUpdate')
      ->where('bank_id', $bank->id)
      ->when($name && $name != '', function ($query) use ($name) {
        $query->where('name', 'LIKE', '%' . $name . '%');
      })
      ->when($status && $status != '', function ($query) use ($status) {
        $query->where('status', $status);
      })
      ->latest()
      ->paginate($per_page);

    return response()->json($branches);
  }

  public function updateBranch(Bank $bank, Request $request)
  {
    $request->validate([
      'branch_id' => ['required', 'exists:bank_branches,id'],
      'name' => ['required'],
    ]);

    $branch = BankBranch::find($request->branch_id);

    // Get checker users
    $bank_users = BankUser::where('bank_id', $bank->id)
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where('name', 'Approve Product Configurations');
          });
        });
      })
      ->where('user_id', '!=', auth()->id())
      ->where('active', true)
      ->get();

    $branch->name = $request->name;
    $branch->code = $request->code;
    $branch->location = $request->location;
    $branch->city = $request->city;
    $branch->address = $request->address;
    $branch->status = $request->status;

    if ($bank_users->count() > 0) {
      BankConfigChange::create([
        'configurable_id' => $branch->id,
        'configurable_type' => BankBranch::class,
        'changes' => $branch->getDirty(),
        'created_by' => auth()->id(),
      ]);
      $message = 'Update sent for approval';
    } else {
      $branch->save();

      activity($bank->id)
        ->causedBy(auth()->user())
        ->performedOn($branch)
        ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
        ->log('updated branch ' . $branch->name);

      $message = 'Updated branch ' . $branch->name . ' successfully';
    }

    foreach ($bank->users as $user) {
      if ($user->id != auth()->id()) {
        $user->notify(new BranchesUpdate($branch, 'updated'));
      }
    }

    return response()->json(['message' => $message]);
  }

  public function feesMaster(Bank $bank)
  {
    return view('content.bank.configurations.fees-master', compact('bank'));
  }

  public function feesMasterData(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $name = $request->query('name');
    $account_number = $request->query('account_number');
    $status = $request->query('status');

    $fees_master = BankFeesMaster::with('change', 'proposedUpdate')
      ->where('bank_id', $bank->id)
      ->when($name && $name != '', function ($query) use ($name) {
        $query->where('name', 'LIKE', '%' . $name . '%');
      })
      ->when($account_number && $account_number != '', function ($query) use ($account_number) {
        $query->where('account_number', 'LIKE', '%' . $account_number . '%');
      })
      ->when($status && $status != '', function ($query) use ($status) {
        $query->where('status', $status);
      })
      ->latest()
      ->paginate($per_page);

    return response()->json($fees_master);
  }

  public function storeFeesMaster(Request $request, Bank $bank)
  {
    $validator = Validator::make($request->all(), [
      'name' => ['required'],
      'account_number' => ['required'],
      'fees_account_branch_specific' => ['required'],
      'discount_bearing' => ['required'],
    ]);

    if ($validator->fails()) {
      return response()->json($validator->messages(), 422);
    }

    $bank_users = BankUser::whereNotIn('user_id', [auth()->id()])
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where('name', 'Approve Product Configurations');
          });
        });
      })
      ->where('bank_id', $bank->id)
      ->get();

    $fees = BankFeesMaster::create([
      'bank_id' => $bank->id,
      'name' => $request->name,
      'account_number' => $request->account_number,
      'fee_account_branch_specific' => $request->fees_account_branch_specific == 'yes' ? true : false,
      'discount_bearing' => $request->discount_bearing == 'yes' ? true : false,
      'tax_name' => $request->tax_name,
      'tax_percent' => $request->tax_percent,
      'tax_account_number' => $request->tax_account_number,
      'tax_account_branch_specific' => $request->tax_account_branch_specific == 'yes' ? true : false,
      'service_code' => $request->service_code,
      'sac' => $request->sac,
      'status' => $bank_users->count() > 0 ? 'inactive' : 'active',
    ]);

    if ($bank_users->count() > 0) {
      ProposedConfigurationChange::create([
        'user_id' => auth()->id(),
        'modeable_type' => Bank::class,
        'modeable_id' => $bank->id,
        'configurable_type' => BankFeesMaster::class,
        'configurable_id' => $fees->id,
        'old_value' => 'inactive',
        'new_value' => 'active',
        'field' => 'status',
        'description' => 'Added new fees ' . $fees->name,
      ]);

      $message = 'Fee added. Awaiting approval.';
    } else {
      $message = 'Fee added successfully.';
    }

    foreach ($bank_users as $bank_user) {
      if ($bank_user->user->id != auth()->id()) {
        $bank_user->user->notify(new FeesMasterUpdate($fees, 'created'));
      }
    }

    return response()->json(['message' => $message]);
  }

  public function updateFeesMaster(Request $request, Bank $bank)
  {
    $validator = Validator::make($request->all(), [
      'fees_master_id' => ['required'],
      'name' => ['required'],
      'account_number' => ['required'],
      'fees_account_branch_specific' => ['required'],
      'discount_bearing' => ['required'],
    ]);

    if ($validator->fails()) {
      return response()->json($validator->messages(), 422);
    }

    $fees_master = BankFeesMaster::find($request->fees_master_id);

    // Get checker users
    $bank_users = BankUser::where('bank_id', $bank->id)
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where('name', 'Approve Product Configurations');
          });
        });
      })
      ->where('user_id', '!=', auth()->id())
      ->where('active', true)
      ->count();

    $fees_master->name = $request->name;
    $fees_master->account_number = $request->account_number;
    $fees_master->fee_account_branch_specific = $request->fees_account_branch_specific == 'yes' ? true : false;
    $fees_master->discount_bearing = $request->discount_bearing == 'yes' ? true : false;
    $fees_master->tax_name = $request->tax_name;
    $fees_master->tax_percent = $request->tax_percent;
    $fees_master->tax_account_number = $request->tax_account_number;
    $fees_master->tax_account_branch_specific = $request->tax_account_branch_specific == 'yes' ? true : false;
    $fees_master->service_code = $request->service_code;
    $fees_master->sac = $request->sac;
    $fees_master->status = $request->status;

    if ($bank_users > 0) {
      BankConfigChange::create([
        'configurable_id' => $fees_master->id,
        'configurable_type' => BankFeesMaster::class,
        'changes' => $fees_master->getDirty(),
        'created_by' => auth()->id(),
      ]);
      $message = 'Update sent for approval';
    } else {
      $fees_master->save();

      foreach ($bank->users as $user) {
        if ($user->id != auth()->id()) {
          $user->notify(new FeesMasterUpdate($fees_master, 'updated'));
        }
      }

      $message = 'Updated record to fees master successfully';
    }

    return response()->json(['message' => $message]);
  }

  public function userManagement(Bank $bank)
  {
    $user_roles = auth()->user()->roles;
    $user_permissions = [];
    foreach ($user_roles as $role) {
      foreach ($role->permissions as $permission) {
        array_push($user_permissions, $permission->name);
      }
    }

    $roles = PermissionData::where('RoleTypeName', 'Bank')
      ->where(function ($query) use ($bank) {
        $query->where('bank_id', $bank->id)->orWhere('bank_id', null);
      })
      ->select('id', 'RoleName')
      ->where('status', 'approved')
      ->get();

    $role_types = RoleType::with('Groups.AccessGroups')
      ->where('name', '!=', 'Admin')
      ->where('name', '!=', 'CRM')
      ->get();

    $bank_roles = PermissionData::with('roleIDs')
      ->where(function ($query) use ($bank) {
        $query->where('bank_id', $bank->id)->orWhere('bank_id', null);
      })
      ->whereNotIn('RoleTypeName', ['Admin', 'CRM'])
      ->select('id', 'RoleName', 'RoleDescription', 'RoleTypeName', 'status', 'user_id', 'bank_id')
      ->get();

    $countries = json_decode(file_get_contents(public_path('assets/country-codes.json')));

    $products = ['vendor_financing' => 'Vendor Financing', 'dealer_financing' => 'Dealer Financing'];
    $reporting_managers = User::where('is_active', true)
      ->whereIn('id', $bank->users->pluck('id'))
      ->get();
    $visibilities = ['All Records', 'My Records', 'My and My Reportee Records'];
    $location_types = ['Country', 'City', 'Branch'];
    $notification_channels = ['email' => 'Email', 'sms' => 'SMS', 'email_and_sms' => 'Email and SMS'];
    $branches = BankBranch::where('status', 'active')
      ->where('bank_id', $bank->id)
      ->get();

    return view('content.bank.configurations.users', [
      'bank' => $bank,
      'roles' => $roles,
      'role_types' => $role_types,
      'bank_roles' => $bank_roles,
      'user_permissions' => $user_permissions,
      'countries' => $countries,
      'products' => $products,
      'reporting_managers' => $reporting_managers,
      'visibilities' => $visibilities,
      'location_types' => $location_types,
      'notification_channels' => $notification_channels,
      'branches' => $branches,
    ]);
  }

  public function storeRejectionReason(Bank $bank, Request $request)
  {
    $request->validate(['reason' => 'required', 'string']);

    $bank->rejectionReasons()->create(['reason' => $request->reason]);

    if ($request->wantsJson()) {
      return response()->json(['message' => 'Rejection Reason added successfully']);
    }

    toastr()->success('', 'Rejection reason added successfully');

    return back();
  }

  public function updateRejectionReason(Request $request, Bank $bank, BankRejectionReason $bank_rejection_reason)
  {
    $request->validate(['reason' => 'required']);

    $bank_rejection_reason->update(['reason' => $request->reason]);

    if ($request->wantsJson()) {
      return response()->json(['message' => 'Rejection reason updated successfully']);
    }

    toastr()->success('', 'Rejection reason updated successfully');

    return back();
  }

  public function deleteRejectionReason(Bank $bank, BankRejectionReason $bank_rejection_reason)
  {
    $bank_rejection_reason->delete();

    if (request()->wantsJson()) {
      return response()->json(['message' => 'Deleted rejection reason successfully']);
    }

    toastr()->success('', 'Deleted rejection reason successfully');

    return back();
  }

  public function noaTemplates(Bank $bank)
  {
    return view('content.bank.configurations.noa-templates');
  }

  public function noaTemplatesData(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $name = $request->query('name');
    $status = $request->query('status');
    $product_type = $request->query('product_type');

    $noa_templates = NoaTemplate::with('change', 'proposedUpdate')
      ->where('bank_id', $bank->id)
      ->when($name && $name != '', function ($query) use ($name) {
        $query->where('name', 'LIKE', '%' . $name . '%');
      })
      ->when($status && $status != '', function ($query) use ($status) {
        $query->where('status', $status);
      })
      ->when($product_type && $product_type != '', function ($query) use ($product_type) {
        $query->where('product_type', $product_type);
      })
      ->latest()
      ->paginate($per_page);

    return response()->json($noa_templates);
  }

  public function storeNoaTemplate(Bank $bank, Request $request)
  {
    $validator = Validator::make($request->all(), [
      'name' => 'required',
      'product_type' => 'required',
      'body' => 'required',
      // 'status' => 'required',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->messages(), 422);
    }

    $bank_users = BankUser::whereNotIn('user_id', [auth()->id()])
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where('name', 'Approve Product Configurations');
          });
        });
      })
      ->where('bank_id', $bank->id)
      ->get();

    $noa_template = NoaTemplate::create([
      'bank_id' => $bank->id,
      'name' => $request->name,
      'product_type' => $request->product_type,
      'status' => $bank_users->count() > 0 ? 'inactive' : 'active',
      'body' => $request->body,
    ]);

    if ($bank_users->count() > 0) {
      ProposedConfigurationChange::create([
        'user_id' => auth()->id(),
        'modeable_type' => Bank::class,
        'modeable_id' => $bank->id,
        'configurable_type' => NoaTemplate::class,
        'configurable_id' => $noa_template->id,
        'old_value' => 'inactive',
        'new_value' => 'active',
        'field' => 'status',
        'description' => 'Added new NOA Template ' . $noa_template->name,
      ]);

      $message = 'NOA Template added. Awaiting approval.';
    } else {
      $message = 'NOA Template added successfully.';
    }

    return response()->json(['message' => $message]);
  }

  public function updateNoaTemplate(Bank $bank, Request $request)
  {
    $validator = Validator::make($request->all(), [
      'id' => 'required',
      'name' => 'required',
      'product_type' => 'required',
      'body' => 'required',
      'status' => 'required',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->messages(), 422);
    }

    $noa_template = NoaTemplate::find($request->id);

    // Get checker users
    $bank_users = BankUser::where('bank_id', $bank->id)
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where('name', 'Approve Product Configurations');
          });
        });
      })
      ->where('user_id', '!=', auth()->id())
      ->where('active', true)
      ->count();

    $noa_template->name = $request->name;
    $noa_template->product_type = $request->product_type;
    $noa_template->status = $request->status;
    $noa_template->body = $request->body;

    if ($bank_users > 0) {
      BankConfigChange::create([
        'configurable_id' => $noa_template->id,
        'configurable_type' => NoaTemplate::class,
        'changes' => $noa_template->getDirty(),
        'created_by' => auth()->id(),
      ]);
      $message = 'Update sent for approval';
    } else {
      $noa_template->save();
      $message = 'Template saved successfully';
    }

    return response()->json(['message' => $message]);
  }

  public function deleteNoaTemplate(Bank $bank, NoaTemplate $noa_template)
  {
    $noa_template->delete();

    return response()->json($noa_template);
  }

  public function termsAndConditions(Bank $bank)
  {
    return view('content.bank.configurations.terms-and-conditions', ['bank' => $bank]);
  }

  public function termsAndConditionsData(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');
    $name = $request->query('name');
    $status = $request->query('status');
    $product_type = $request->query('product_type');

    $terms_and_conditions = TermsConditionsConfig::with('change', 'proposedUpdate')
      ->where('bank_id', $bank->id)
      ->when($name && $name != '', function ($query) use ($name) {
        $query->where('name', 'LIKE', '%' . $name . '%');
      })
      ->when($status && $status != '', function ($query) use ($status) {
        $query->where('status', $status);
      })
      ->when($product_type && $product_type != '', function ($query) use ($product_type) {
        $query->where('product_type', $product_type);
      })
      ->latest()
      ->paginate($per_page);

    return response()->json($terms_and_conditions);
  }

  public function storeTermsAndConditions(Bank $bank, Request $request)
  {
    $validator = Validator::make($request->all(), [
      'name' => 'required',
      'product_type' => 'required',
      'body' => 'required',
      // 'status' => 'required',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->messages(), 422);
    }

    $bank_users = BankUser::whereNotIn('user_id', [auth()->id()])
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where('name', 'Approve Product Configurations');
          });
        });
      })
      ->where('bank_id', $bank->id)
      ->get();

    $terms_and_conditions = TermsConditionsConfig::create([
      'bank_id' => $bank->id,
      'name' => $request->name,
      'product_type' => $request->product_type,
      'status' => $bank_users->count() > 0 ? 'inactive' : 'active',
      'terms_conditions' => $request->body,
      'created_by' => auth()->id(),
    ]);

    if ($bank_users->count() > 0) {
      ProposedConfigurationChange::create([
        'user_id' => auth()->id(),
        'modeable_type' => Bank::class,
        'modeable_id' => $bank->id,
        'configurable_type' => TermsConditionsConfig::class,
        'configurable_id' => $terms_and_conditions->id,
        'old_value' => 'inactive',
        'new_value' => 'active',
        'field' => 'status',
        'description' =>
          'Added Terms and Conditions ' . $terms_and_conditions->name . ' for ' . $terms_and_conditions->product_type,
      ]);

      $message = 'Terms and Conditions added. Awaiting approval.';
    } else {
      $message = 'Terms and Conditions added successfully.';
    }

    return response()->json(['message' => $message]);
  }

  public function updateTermsAndConditions(Bank $bank, Request $request)
  {
    $validator = Validator::make($request->all(), [
      'id' => 'required',
      'name' => 'required',
      'product_type' => 'required',
      'body' => 'required',
      'status' => 'required',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->messages(), 422);
    }

    $terms_and_conditions = TermsConditionsConfig::find($request->id);

    // Get checker users
    $bank_users = BankUser::where('bank_id', $bank->id)
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where('name', 'Approve Product Configurations');
          });
        });
      })
      ->where('user_id', '!=', auth()->id())
      ->where('active', true)
      ->count();

    $terms_and_conditions->name = $request->name;
    $terms_and_conditions->product_type = $request->product_type;
    $terms_and_conditions->status = $request->status;
    $terms_and_conditions->terms_conditions = $request->body;

    if ($bank_users > 0) {
      BankConfigChange::create([
        'configurable_id' => $terms_and_conditions->id,
        'configurable_type' => TermsConditionsConfig::class,
        'changes' => $terms_and_conditions->getDirty(),
        'created_by' => auth()->id(),
      ]);
      $message = 'Update sent for approval';
    } else {
      $terms_and_conditions->save();
      $message = 'Terms and Conditions saved successfully';
    }

    return response()->json(['message' => $message]);
  }

  public function deleteTermsAndConditions(Bank $bank, TermsConditionsConfig $terms_and_conditions)
  {
    $terms_and_conditions->delete();

    return response()->json($terms_and_conditions);
  }

  public function updateTermsAndConditionsActiveStatus(
    Bank $bank,
    TermsConditionsConfig $terms_conditions_config,
    $status
  ) {
    // Get checker users
    $bank_users = BankUser::where('bank_id', $bank->id)
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where('name', 'Approve Product Configurations');
          });
        });
      })
      ->where('user_id', '!=', auth()->id())
      ->where('active', true)
      ->count();

    $terms_conditions_config->status = $status;

    if ($bank_users > 0) {
      BankConfigChange::create([
        'configurable_id' => $terms_conditions_config->id,
        'configurable_type' => TermsConditionsConfig::class,
        'changes' => $terms_conditions_config->getDirty(),
        'created_by' => auth()->id(),
      ]);
      $message = 'Update sent for approval';
    } else {
      $terms_conditions_config->save();
      $message = 'Terms and Conditions saved successfully';
    }

    activity($bank->id)
      ->causedBy(auth()->user())
      ->performedOn($terms_conditions_config)
      ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
      ->log('updated term and conditions status to "' . $status . '"');

    if (!request()->wantsJson()) {
      toastr()->success('', $message);

      return back();
    }

    return response()->json(['message' => $message], 200);
  }

  public function changeStatusUpdate(Bank $bank, Request $request)
  {
    $request->validate([
      'type' => ['required'],
      'id' => ['required'],
      'status' => ['required'],
    ]);

    $config = $request->type::find($request->id);

    if ($config) {
      $changes = BankConfigChange::where('configurable_type', $request->type)
        ->where('configurable_id', $request->id)
        ->first();

      if ($changes) {
        if ($request->status == 'approve') {
          foreach ($changes->changes as $key => $change) {
            $config->update([
              $key => $change,
            ]);
          }

          if ($request->type == 'App\\Models\\BankTaxRate' && $config->is_default) {
            $tax_rates = BankTaxRate::where('bank_id', $bank->id)
              ->where('id', '!=', $config->id)
              ->get();

            foreach ($tax_rates as $tax_rate) {
              $tax_rate->is_default = false;
              $tax_rate->save();
            }
          }

          if ($request->type == 'App\\Models\\BankBaseRate' && $config->is_default) {
            $tax_rates = BankBaseRate::where('bank_id', $bank->id)
              ->where('id', '!=', $config->id)
              ->get();

            foreach ($tax_rates as $tax_rate) {
              $tax_rate->is_default = false;
              $tax_rate->save();
            }
          }

          if ($request->type == 'App\\Models\\TermsConditionsConfig') {
            $terms_and_conditions_configs = TermsConditionsConfig::where('bank_id', $bank->id)
              ->where('id', '!=', $config->id)
              ->where('product_type', $config->product_type)
              ->get();

            foreach ($terms_and_conditions_configs as $terms_and_conditions_config) {
              $terms_and_conditions_config->status = 'inactive';
              $terms_and_conditions_config->save();
            }
          }

          $message = 'Changes applied successfully';
        } else {
          $message = 'Changes discarded';
        }

        $changes->delete();

        return response()->json(['message' => $message]);
      } else {
        return response()->json(['message' => 'Invalid request'], 422);
      }
    }

    return response()->json(['message' => 'Invalid request'], 422);
  }

  public function convertionRates(Bank $bank)
  {
    return view('content.bank.configurations.convertion-rates');
  }

  public function convertionRatesData(Bank $bank, Request $request)
  {
    $per_page = $request->query('per_page');

    $convertion_rates = BankConvertionRate::where('bank_id', $bank->id)->paginate($per_page);

    $currency = explode(',', str_replace("\"", '', $bank->adminConfiguration?->selectedCurrencyIds));

    $currencies = Currency::whereIn('id', $currency)->get();

    return response()->json(['data' => $convertion_rates, 'currencies' => $currencies]);
  }

  public function storeConvertionRate(Bank $bank, Request $request)
  {
    $validator = Validator::make($request->all(), [
      'from_currency' => ['required'],
      'to_currency' => ['required'],
      'rate' => ['required'],
    ]);

    if ($validator->fails()) {
      return response()->json($validator->messages(), 422);
    }

    BankConvertionRate::create([
      'bank_id' => $bank->id,
      'from_currency' => $request->from_currency,
      'to_currency' => $request->to_currency,
      'rate' => $request->rate,
    ]);

    return response()->json(['message' => 'Convertion rate added successfully']);
  }

  public function updateConvertionRate(Bank $bank, Request $request, BankConvertionRate $bank_convertion_rate)
  {
    $validator = Validator::make($request->all(), [
      // 'from_currency' => ['required'],
      // 'to_currency' => ['required'],
      'rate' => ['required'],
    ]);

    if ($validator->fails()) {
      return response()->json($validator->messages(), 422);
    }

    $bank_convertion_rate->update([
      'from_currency' => $request->from_currency,
      'to_currency' => $request->to_currency,
      'rate' => $request->rate,
    ]);

    return response()->json(['message' => 'Convertion rate added successfully']);
  }

  public function switchBank(Bank $bank, Request $request)
  {
    $request->validate([
      'bank_id' => ['required'],
    ]);

    UserCurrentBank::where(['user_id' => auth()->id()])->update(['bank_id' => $request->bank_id]);

    $bank = Bank::find($request->bank_id);

    if (request()->wantsJson()) {
      return response()->json(['success' => 'Bank updated successfully']);
    }

    return redirect()->route('bank.dashboard', ['bank' => $bank]);
  }
}
