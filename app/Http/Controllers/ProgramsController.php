<?php

namespace App\Http\Controllers;

use App\Exports\ProgramsExport;
use App\Exports\Report;
use Carbon\Carbon;
use App\Models\Tax;
use App\Models\Bank;
use App\Models\User;
use App\Jobs\SendMail;
use App\Models\Company;
use App\Models\Program;
use App\Helpers\Helpers;
use App\Http\Resources\OdAccountsResource;
use App\Models\BankUser;
use App\Models\BaseRate;
use App\Models\ProgramFee;
use App\Models\BankTaxRate;
use App\Models\CompanyUser;
use App\Models\ProgramCode;
use App\Models\ProgramRole;
use App\Models\ProgramType;
use Illuminate\Support\Str;
use App\Models\BankBaseRate;
use Illuminate\Http\Request;
use App\Models\ProgramChange;
use App\Models\BankMasterList;
use App\Models\PermissionData;
use App\Models\ProgramDiscount;
use Illuminate\Validation\Rule;
use App\Models\ProgramVendorFee;
use App\Models\ProgramBankDetails;
use App\Models\ProgramCompanyRole;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Models\ProgramAnchorDetails;
use App\Models\ProgramMappingChange;
use App\Models\ProgramVendorDiscount;
use App\Models\ProgramBankUserDetails;
use App\Notifications\ChangesApproval;
use App\Notifications\ProgramCreation;
use App\Notifications\ProgramUpdation;
use App\Http\Resources\ProgramResource;
use App\Models\ProgramVendorBankDetail;
use App\Notifications\NewProgramMapping;
use App\Models\BankProductsConfiguration;
use App\Models\CompanyAuthorizationGroup;
use App\Models\Invoice;
use App\Models\ProgramDealerDiscountRate;
use App\Models\ProgramVendorConfiguration;
use App\Models\ProgramVendorContactDetail;
use App\Notifications\ProgramReview;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ProgramsController extends Controller
{
  public function index(Bank $bank, $status = null)
  {
    $programs = Program::where('bank_id', $bank->id)->count();
    $pending_programs = Program::where('bank_id', $bank->id)
      ->where('account_status', 'suspended')
      ->where('status', 'pending')
      ->count();

    $expired_programs = Program::where('bank_id', $bank->id)
      ->whereDate('limit_expiry_date', '<', now())
      ->count();

    $exhausted_programs = Program::where('bank_id', $bank->id)
      ->get()
      ->filter(function ($value) {
        return $value->utilized_amount >= $value->program_limit;
      })
      ->count();

    return view('content.bank.programs.index', [
      'bank' => $bank,
      'programs' => $programs,
      'pending_programs' => $pending_programs,
      'expired_programs' => $expired_programs,
      'exhausted_programs' => $exhausted_programs,
    ]);
  }

  public function programs(Request $request, Bank $bank)
  {
    $name = $request->query('name');
    $anchor = $request->query('anchor');
    $type = $request->query('type');
    $status = $request->query('status');
    $per_page = $request->query('per_page');

    $programs = Program::with('programType', 'programCode', 'bank.adminConfiguration', 'anchor')
      ->withCount('proposedUpdate')
      ->where('bank_id', $bank->id)
      ->when($name && $name != '', function ($query) use ($name) {
        $query->where('companies.name', 'LIKE', '%' . $name . '%');
      })
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('anchor', function ($query) use ($anchor) {
          $query->where('name', 'LIKE', '%' . $anchor . '%');
        });
      })
      ->when($type && $type != '', function ($query) use ($type) {
        switch ($type) {
          case 'vendor_financing_receivable':
            $query->whereHas('programCode', function ($query) {
              $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
            });
            break;
          case 'factoring_with_recourse':
            $query->whereHas('programCode', function ($query) {
              $query->where('name', Program::FACTORING_WITH_RECOURSE);
            });
            break;
          case 'factoring_without_recourse':
            $query->whereHas('programCode', function ($query) {
              $query->where('name', Program::FACTORING_WITHOUT_RECOURSE);
            });
            break;
          case 'dealer_financing':
            $query->whereHas('programType', function ($query) {
              $query->where('name', Program::DEALER_FINANCING);
            });
            break;
          default:
            $query->whereHas('programCode', function ($query) {
              $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
            });
            break;
        }
      })
      ->when($status && $status != '', function ($query) use ($status) {
        $query->where('account_status', $status);
      })
      ->orderBy('proposed_update_count', 'DESC')
      ->orderBy('name', 'ASC')
      ->paginate($per_page);

    $programs = ProgramResource::collection($programs)
      ->response()
      ->getData();

    if (request()->wantsJson()) {
      return response()->json(
        [
          'data' => $programs,
        ],
        200
      );
    }
  }

  public function exportPrograms(Bank $bank, Request $request)
  {
    $name = $request->query('name');
    $anchor = $request->query('anchor');
    $type = $request->query('type');
    $status = $request->query('status');

    $date = now()->format('d M Y');

    Excel::store(new ProgramsExport($bank, $name, $anchor, $type, $status), 'Programs_' . $date . '.csv', 'exports');

    return Storage::disk('exports')->download('Programs_' . $date . '.csv');
  }

  public function pendingPrograms(Request $request, Bank $bank)
  {
    $name = $request->query('name');
    $anchor = $request->query('anchor');
    $type = $request->query('type');
    $per_page = $request->query('per_page');

    $programs = Program::with('programType', 'programCode', 'bank.adminConfiguration', 'anchor')
      ->withCount('proposedUpdate')
      ->where('bank_id', $bank->id)
      ->where('account_status', 'suspended')
      ->where('status', 'pending')
      ->when($name && $name != '', function ($query) use ($name) {
        $query->where('name', 'LIKE', '%' . $name . '%');
      })
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('anchor', function ($query) use ($anchor) {
          $query->where('name', 'LIKE', '%' . $anchor . '%');
        });
      })
      ->when($type && $type != '', function ($query) use ($type) {
        switch ($type) {
          case 'vendor_financing_receivable':
            $query->whereHas('programCode', function ($query) {
              $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
            });
            break;
          case 'factoring_with_recourse':
            $query->whereHas('programCode', function ($query) {
              $query->where('name', Program::FACTORING_WITH_RECOURSE);
            });
            break;
          case 'factoring_without_recourse':
            $query->whereHas('programCode', function ($query) {
              $query->where('name', Program::FACTORING_WITHOUT_RECOURSE);
            });
            break;
          case 'dealer_financing':
            $query->whereHas('programType', function ($query) {
              $query->where('name', Program::DEALER_FINANCING);
            });
            break;
          default:
            $query->whereHas('programCode', function ($query) {
              $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
            });
            break;
        }
      })
      ->orderBy('name', 'ASC')
      ->paginate($per_page);

    $programs = ProgramResource::collection($programs)
      ->response()
      ->getData();

    if (request()->wantsJson()) {
      return response()->json(
        [
          'data' => $programs,
        ],
        200
      );
    }
  }

  public function exhaustedProgramsData(Request $request, Bank $bank)
  {
    $name = $request->query('name');
    $anchor = $request->query('anchor');
    $type = $request->query('type');
    $per_page = $request->query('per_page');

    $programs = $bank->programs;

    $program_ids = $programs
      ->filter(function ($value) {
        return $value->utilized_amount >= $value->program_limit;
      })
      ->pluck('id');

    $programs = Program::with('programType', 'programCode', 'bank.adminConfiguration', 'anchor')
      ->withCount('proposedUpdate')
      ->whereIn('id', $program_ids)
      ->when($name && $name != '', function ($query) use ($name) {
        $query->where('name', 'LIKE', '%' . $name . '%');
      })
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('anchor', function ($query) use ($anchor) {
          $query->where('name', 'LIKE', '%' . $anchor . '%');
        });
      })
      ->when($type && $type != '', function ($query) use ($type) {
        switch ($type) {
          case 'vendor_financing_receivable':
            $query->whereHas('programCode', function ($query) {
              $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
            });
            break;
          case 'factoring_with_recourse':
            $query->whereHas('programCode', function ($query) {
              $query->where('name', Program::FACTORING_WITH_RECOURSE);
            });
            break;
          case 'factoring_without_recourse':
            $query->whereHas('programCode', function ($query) {
              $query->where('name', Program::FACTORING_WITHOUT_RECOURSE);
            });
            break;
          case 'dealer_financing':
            $query->whereHas('programType', function ($query) {
              $query->where('name', Program::DEALER_FINANCING);
            });
            break;
          default:
            $query->whereHas('programCode', function ($query) {
              $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
            });
            break;
        }
      })
      ->orderBy('name', 'ASC')
      ->paginate($per_page);

    $programs = ProgramResource::collection($programs)
      ->response()
      ->getData();

    if (request()->wantsJson()) {
      return response()->json(['data' => $programs]);
    }
  }

  public function expiredProgramsData(Request $request, Bank $bank)
  {
    $name = $request->query('name');
    $anchor = $request->query('anchor');
    $type = $request->query('type');
    $per_page = $request->query('per_page');

    $programs = Program::with('programType', 'programCode', 'bank.adminConfiguration', 'anchor')
      ->withCount('proposedUpdate')
      ->where('bank_id', $bank->id)
      ->whereDate('limit_expiry_date', '<', now())
      ->when($name && $name != '', function ($query) use ($name) {
        $query->where('name', 'LIKE', '%' . $name . '%');
      })
      ->when($anchor && $anchor != '', function ($query) use ($anchor) {
        $query->whereHas('anchor', function ($query) use ($anchor) {
          $query->where('name', 'LIKE', '%' . $anchor . '%');
        });
      })
      ->when($type && $type != '', function ($query) use ($type) {
        switch ($type) {
          case 'vendor_financing_receivable':
            $query->whereHas('programCode', function ($query) {
              $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
            });
            break;
          case 'factoring_with_recourse':
            $query->whereHas('programCode', function ($query) {
              $query->where('name', Program::FACTORING_WITH_RECOURSE);
            });
            break;
          case 'factoring_without_recourse':
            $query->whereHas('programCode', function ($query) {
              $query->where('name', Program::FACTORING_WITHOUT_RECOURSE);
            });
            break;
          case 'dealer_financing':
            $query->whereHas('programType', function ($query) {
              $query->where('name', Program::DEALER_FINANCING);
            });
            break;
          default:
            $query->whereHas('programCode', function ($query) {
              $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
            });
            break;
        }
      })
      ->orderBy('name', 'ASC')
      ->paginate($per_page);

    $programs = ProgramResource::collection($programs)
      ->response()
      ->getData();

    if (request()->wantsJson()) {
      return response()->json(['data' => $programs]);
    }
  }

  public function create(Bank $bank)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Add/Edit Program & Mapping')
    ) {
      toastr()->error('', 'You do not have the permission to perform this action');
      return back();
    }

    $bank->load('users');

    $program = request()->query('program');

    $companies = Company::with('relationshipManagers')
      ->where('bank_id', $bank->id)
      ->where('approval_status', 'approved')
      ->where('status', 'active')
      ->where('is_published', true)
      ->latest()
      ->get();

    $bank_product_types = collect($bank->product_types)->map(fn($type) => Str::title(str_replace('_', ' ', $type)));
    if ($bank_product_types->count() > 0) {
      $program_types = ProgramType::with('programCodes')
        ->whereIn('name', $bank_product_types->all())
        ->get();
    } else {
      $program_types = ProgramType::with('programCodes')->get();
    }

    $reset_frequencies = [
      'Daily' => 1,
      'Monthly' => 30,
      'Quarterly' => 90,
      'Half Annually' => 180,
      'Annually' => 365,
    ];

    $benchmark_rates = [];

    $base_rates = BankBaseRate::active()
      ->where('bank_id', $bank->id)
      ->get();

    if ($base_rates->count() <= 0) {
      $base_rates = BaseRate::active()->get();

      foreach ($base_rates as $rate) {
        $benchmark_rates[$rate->rate_code] = [
          'rate' => $rate->rate,
          'is_default' => false,
        ];
      }
    } else {
      foreach ($base_rates as $rate) {
        $benchmark_rates[$rate->name] = [
          'rate' => $rate->rate,
          'is_default' => $rate->is_default,
        ];
      }
    }

    $taxes = [];

    $bank_tax_rates = BankTaxRate::active()
      ->where('bank_id', $bank->id)
      ->get();

    if ($bank_tax_rates->count() <= 0) {
      $bank_tax_rates = Tax::active()->get();

      foreach ($bank_tax_rates as $rate) {
        $taxes[$rate->name] = [
          'rate' => $rate->percentage,
          'is_default' => false,
        ];
      }
    } else {
      foreach ($bank_tax_rates as $rate) {
        $taxes[$rate->tax_name] = [
          'rate' => $rate->value,
          'is_default' => $rate->is_default,
        ];
      }
    }

    $roles = PermissionData::where('RoleTypeName', 'Anchor')
      ->where(function ($query) use ($bank) {
        $query->where('bank_id', $bank->id)->orWhere('bank_id', null);
      })
      ->select('id', 'RoleName')
      ->get();

    $banks = BankMasterList::all();

    if ($program) {
      $program = Program::with(
        'anchor.bankDetails',
        'anchor.relationshipManagers',
        'programType',
        'programCode',
        'fees',
        'discountDetails',
        'dealerDiscountRates'
      )->find($program);
    } else {
      $program = null;
    }

    $countries = json_decode(file_get_contents(public_path('assets/country-codes.json')));

    $drafts = Program::where('bank_id', $bank->id)
      ->where('publisher_type', User::class)
      ->where('publisher_id', auth()->id())
      ->onlyDrafts()
      ->get();

    // Check if bank has fees and tax accounts setup
    $vendor_financing = ProgramType::where('name', Program::VENDOR_FINANCING)->first();
    $vendor_financing_receivable = ProgramCode::where('name', Program::VENDOR_FINANCING_RECEIVABLE)->first();
    $factoring_with_recourse = ProgramCode::where('name', Program::FACTORING_WITH_RECOURSE)->first();
    $factoring_without_recourse = ProgramCode::where('name', Program::FACTORING_WITHOUT_RECOURSE)->first();
    $dealer_financing = ProgramType::where('name', Program::DEALER_FINANCING)->first();

    // Check if Discount Accounts have been configured
    $vendor_financing_discount_accounts = BankProductsConfiguration::where('bank_id', $bank->id)
      ->where('product_code_id', $vendor_financing_receivable->id)
      ->where('product_type_id', $vendor_financing->id)
      ->whereIn('name', [
        'Discount Income Account',
        'Advanced Discount Account',
        'Discount Receivable Account',
        'Unrealized Discount Account',
      ])
      ->where('value', null)
      ->first();

    $factoring_discount_accounts = BankProductsConfiguration::where('bank_id', $bank->id)
      ->where('product_type_id', $vendor_financing->id)
      ->whereIn('product_code_id', [$factoring_with_recourse->id, $factoring_without_recourse->id])
      ->whereIn('name', [
        'Discount Income Account',
        'Advanced Discount Account',
        'Discount Receivable Account',
        'Unrealized Discount Account',
      ])
      ->where('value', null)
      ->first();

    $dealer_financing_discount_accounts = BankProductsConfiguration::where('bank_id', $bank->id)
      ->where('product_type_id', $dealer_financing->id)
      ->where('product_code_id', null)
      ->whereIn('name', [
        'Discount Receivable from Overdraft',
        'Discount Income Account',
        'Unrealized Discount Account',
      ])
      ->where('value', null)
      ->first();

    $vendor_financing_fees_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
      ->where('product_code_id', $vendor_financing_receivable->id)
      ->where('product_type_id', $vendor_financing->id)
      ->where('name', 'Fee Income Account')
      ->first();

    $factoring_fees_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
      ->where('product_type_id', $vendor_financing->id)
      ->whereIn('product_code_id', [$factoring_with_recourse->id, $factoring_without_recourse->id])
      ->where('name', 'Fee Income Account')
      ->first();

    $dealer_fees_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
      ->where('product_type_id', $dealer_financing->id)
      ->where('name', 'Fee Income Account')
      ->first();

    $vendor_financing_penal_account = BankProductsConfiguration::where('bank_id', $bank->id)
      ->where('product_code_id', $vendor_financing_receivable->id)
      ->where('product_type_id', $vendor_financing->id)
      ->where('name', 'Penal Discount Income Account')
      ->first();

    $factoring_penal_account = BankProductsConfiguration::where('bank_id', $bank->id)
      ->where('product_type_id', $vendor_financing->id)
      ->whereIn('product_code_id', [$factoring_with_recourse->id, $factoring_without_recourse->id])
      ->where('name', 'Penal Discount Income Account')
      ->first();

    $dealer_financing_penal_account = BankProductsConfiguration::where('bank_id', $bank->id)
      ->where('product_type_id', $dealer_financing->id)
      ->where('name', 'Penal Discount Income Account')
      ->first();

    $users = BankUser::where('bank_id', $bank->id)
      ->where('active', true)
      ->pluck('user_id');

    $bank_users = User::whereIn('id', $users)->get();

    $bank_payment_accounts = $bank->paymentAccounts;

    return view('content.bank.programs.create', [
      'bank' => $bank,
      'bank_users' => $bank_users,
      'companies' => $companies,
      'program_types' => $program_types,
      'reset_frequencies' => $reset_frequencies,
      'benchmark_rates' => $benchmark_rates,
      'taxes' => $taxes,
      'banks' => $banks,
      'roles' => $roles,
      'program' => $program,
      'drafts' => $drafts,
      'countries' => $countries,
      'vendor_financing_discount_accounts' => $vendor_financing_discount_accounts,
      'factoring_discount_accounts' => $factoring_discount_accounts,
      'dealer_financing_discount_accounts' => $dealer_financing_discount_accounts,
      'vendor_financing_fees_income_account' => $vendor_financing_fees_income_account,
      'factoring_fees_income_account' => $factoring_fees_income_account,
      'dealer_fees_income_account' => $dealer_fees_income_account,
      'vendor_financing_penal_account' => $vendor_financing_penal_account,
      'factoring_penal_account' => $factoring_penal_account,
      'dealer_financing_penal_account' => $dealer_financing_penal_account,
      'bank_payment_accounts' => $bank_payment_accounts,
    ]);
  }

  public function accountsChecker(Bank $bank, $side, $program_type, $program_code = null)
  {
    $program_type_code = ProgramType::find($program_type);
    if ($program_code) {
      $program_code = ProgramCode::find($program_code);
    }

    switch ($side) {
      case Invoice::FRONT_ENDED:
        // Check if Advanced Discount Income Account, Discount Income Account and Fee Income are configured
        if ($program_type_code->name == Program::DEALER_FINANCING) {
          $advanced_discount_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
            ->where('product_code_id', $program_code?->id)
            ->where('product_type_id', $program_type_code->id)
            ->where('name', 'Discount Income Account')
            ->first();
          $discount_income_account = $advanced_discount_income_account;
          $fee_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
            ->where('product_code_id', $program_code?->id)
            ->where('product_type_id', $program_type_code->id)
            ->where('name', 'Fee Income Account')
            ->first();
          $penal_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
            ->where('product_code_id', $program_code?->id)
            ->where('product_type_id', $program_type_code->id)
            ->where('name', 'Penal Discount Income Account')
            ->first();
        } else {
          if ($program_code?->name == Program::VENDOR_FINANCING_RECEIVABLE) {
            $advanced_discount_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
              ->where('product_code_id', $program_code?->id)
              ->where('product_type_id', $program_type_code->id)
              ->where('name', 'Advance Discount Account')
              ->first();
            $discount_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
              ->where('product_code_id', $program_code?->id)
              ->where('product_type_id', $program_type_code->id)
              ->where('name', 'Discount Income Account')
              ->first();
            $fee_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
              ->where('product_code_id', $program_code?->id)
              ->where('product_type_id', $program_type_code->id)
              ->where('name', 'Fee Income Account')
              ->first();
            $penal_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
              ->where('product_code_id', $program_code?->id)
              ->where('product_type_id', $program_type_code->id)
              ->where('name', 'Penal Discount Income Account')
              ->first();
          } else {
            $advanced_discount_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
              ->where('product_code_id', $program_code?->id)
              ->where('product_type_id', $program_type_code->id)
              ->where('name', 'Advanced Discount Account')
              ->first();
            $discount_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
              ->where('product_code_id', $program_code?->id)
              ->where('product_type_id', $program_type_code->id)
              ->where('name', 'Discount Income Account')
              ->first();
            $fee_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
              ->where('product_code_id', $program_code?->id)
              ->where('product_type_id', $program_type_code->id)
              ->where('name', 'Fee Income Account')
              ->first();
            if (
              $program_code?->name == Program::FACTORING_WITH_RECOURSE ||
              $program_code?->name == Program::FACTORING_WITHOUT_RECOURSE
            ) {
              $penal_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
                ->where('product_type_id', $program_type_code->id)
                ->where('name', 'Penal Discount Income Account')
                ->first();
            }
          }
        }

        return response()->json(
          [
            'product_type' => $program_type_code->name,
            'fee_income_account' => $fee_income_account,
            'discount_income_account' => $discount_income_account,
            'advanced_discount_income_account' => $advanced_discount_income_account,
            'penal_discount_income_account' => $penal_income_account,
          ],
          200
        );
        break;
      case Invoice::REAR_ENDED:
        // Check if Unrealized Discount, Discount Receivable and Fee Income Account are configured
        if ($program_type_code->name == Program::DEALER_FINANCING) {
          $unrealized_discount_account = BankProductsConfiguration::where('bank_id', $bank->id)
            ->where('product_code_id', $program_code?->id)
            ->where('product_type_id', $program_type_code->id)
            ->where('name', 'Unrealised Discount Account')
            ->first();
          $discount_receivable_account = BankProductsConfiguration::where('bank_id', $bank->id)
            ->where('product_code_id', $program_code?->id)
            ->where('product_type_id', $program_type_code->id)
            ->where('name', 'Discount Receivable from Overdraft')
            ->first();
          $discount_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
            ->where('product_code_id', $program_code?->id)
            ->where('product_type_id', $program_type_code->id)
            ->where('name', 'Discount Income Account')
            ->first();
          $fee_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
            ->where('product_code_id', $program_code?->id)
            ->where('product_type_id', $program_type_code->id)
            ->where('name', 'Fee Income Account')
            ->first();
          $penal_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
            ->where('product_code_id', $program_code?->id)
            ->where('product_type_id', $program_type_code->id)
            ->where('name', 'Penal Discount Income Account')
            ->first();
        } else {
          if ($program_code?->name == Program::VENDOR_FINANCING_RECEIVABLE) {
            $unrealized_discount_account = BankProductsConfiguration::where('bank_id', $bank->id)
              ->where('product_code_id', $program_code?->id)
              ->where('product_type_id', $program_type_code->id)
              ->where('name', 'Unrealised Discount Account')
              ->first();
            $discount_receivable_account = BankProductsConfiguration::where('bank_id', $bank->id)
              ->where('product_code_id', $program_code?->id)
              ->where('product_type_id', $program_type_code->id)
              ->where('name', 'Discount Receivable Account')
              ->first();
            $discount_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
              ->where('product_code_id', $program_code?->id)
              ->where('product_type_id', $program_type_code->id)
              ->where('name', 'Discount Income Account')
              ->first();
            $fee_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
              ->where('product_code_id', $program_code?->id)
              ->where('product_type_id', $program_type_code->id)
              ->where('name', 'Fee Income Account')
              ->first();
            $penal_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
              ->where('product_code_id', $program_code?->id)
              ->where('product_type_id', $program_type_code->id)
              ->where('name', 'Penal Discount Income Account')
              ->first();
          } else {
            $unrealized_discount_account = BankProductsConfiguration::where('bank_id', $bank->id)
              ->where('product_code_id', $program_code?->id)
              ->where('product_type_id', $program_type_code->id)
              ->where('name', 'Unrealised Discount Account')
              ->first();
            $discount_receivable_account = BankProductsConfiguration::where('bank_id', $bank->id)
              ->where('product_code_id', $program_code?->id)
              ->where('product_type_id', $program_type_code->id)
              ->where('name', 'Discount Receivable Account')
              ->first();
            $discount_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
              ->where('product_code_id', $program_code?->id)
              ->where('product_type_id', $program_type_code->id)
              ->where('name', 'Discount Income Account')
              ->first();
            $fee_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
              ->where('product_code_id', $program_code?->id)
              ->where('product_type_id', $program_type_code->id)
              ->where('name', 'Fee Income Account')
              ->first();
            if (
              $program_code?->name == Program::FACTORING_WITH_RECOURSE ||
              $program_code?->name == Program::FACTORING_WITHOUT_RECOURSE
            ) {
              $penal_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
                ->where('product_type_id', $program_type_code->id)
                ->where('name', 'Penal Discount Income Account')
                ->first();
            }
            // $penal_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
            //   ->where('product_code_id', $program_code?->id)
            //   ->where('product_type_id', $program_type_code->id)
            //   ->where('name', 'Penal Discount Income Account')
            //   ->first();
          }
        }

        return response()->json(
          [
            'product_type' => $program_type_code->name,
            'fee_income_account' => $fee_income_account,
            'discount_income_account' => $discount_income_account,
            'unrealized_discount_account' => $unrealized_discount_account,
            'discount_receivable_account' => $discount_receivable_account,
            'penal_discount_income_account' => $penal_income_account,
          ],
          200
        );
        break;
      default:
        break;
    }
  }

  public function checkProgramName(Request $request, Bank $bank)
  {
    $company = Company::find($request->company_id);
    $program = Program::where('bank_id', $bank->id)
      ->whereHas('anchor', function ($query) use ($company) {
        $query->where('name', $company->name);
      })
      ->where('name', $request->name)
      ->first();

    if ($program) {
      return response()->json(['exists' => true]);
    }

    return response()->json(['exists' => false]);
  }

  public function drafts(Bank $bank)
  {
    $drafts = Program::with('anchor')
      ->where('bank_id', $bank->id)
      ->where('publisher_type', User::class)
      ->onlyDrafts()
      ->latest()
      ->get();

    return view('content.bank.programs.drafts', ['bank' => $bank, 'drafts' => $drafts]);
  }

  public function storeDraft(Request $request, Bank $bank)
  {
    $program = Program::where(['bank_id' => $bank->id, 'name' => $request->name])
      ->whereHas('anchor', function ($query) use ($request) {
        $query->where('companies.id', $request->anchor_id);
      })
      ->onlyDrafts()
      ->delete();

    $program = Program::createDraft([
      'bank_id' => $bank->id,
      'name' => $request->name,
      'program_type_id' => $request->program_type_id,
      'program_code_id' =>
        $request->has('program_code_id') && !empty($request->program_code_id) ? $request->program_code_id : null,
      'eligibility' => $request->eligibility,
      'code' => $request->program_code,
      'invoice_margin' => $request->invoice_margin,
      'program_limit' => Str::replace(',', '', $request->program_limit),
      'approved_date' => Carbon::parse($request->approved_date)->format('Y-m-d'),
      'limit_expiry_date' => Carbon::parse($request->limit_expiry_date)->format('Y-m-d'),
      'max_limit_per_account' => Str::replace(',', '', $request->max_limit_per_account),
      'collection_account' => $request->collection_account,
      'factoring_payment_account' => $request->factoring_payment_account,
      'request_auto_finance' => $request->request_auto_finance,
      'stale_invoice_period' => $request->stale_invoice_period,
      'stop_supply' => $request->stop_supply,
      'min_financing_days' => $request->min_financing_days,
      'max_financing_days' => $request->max_financing_days,
      'segment' => $request->segment,
      'auto_debit_anchor_financed_invoices' => $request->auto_debit_anchor_financed_invoices,
      'auto_debit_anchor_non_financed_invoices' => $request->auto_debit_anchor_non_financed_invoices,
      'anchor_can_change_due_date' => $request->anchor_can_change_due_date,
      'max_days_due_date_extension' => $request->max_days_due_date_extension,
      'days_limit_for_due_date_change' => $request->days_limit_for_due_date_change,
      'default_payment_terms' => $request->default_payment_terms,
      'anchor_can_change_payment_term' => $request->anchor_can_change_payment_term,
      'repayment_appropriation' => $request->repayment_appropriation,
      'mandatory_invoice_attachment' => $request->mandatory_invoice_attachment,
      'partner' => $request->partner,
      'recourse' => $request->recourse,
      'due_date_calculated_from' => $request->due_date_calculated_from,
      'buyer_invoice_approval_required' => $request->buyer_invoice_approval_required,
      'noa' => $request->noa,
      'account_status' => $request->account_status,
    ]);

    $program = Program::where(['bank_id' => $bank->id, 'name' => $request->name])
      ->where('publisher_type', User::class)
      ->where('publisher_id', auth()->id())
      ->current()
      ->get();

    $anchor_role = ProgramRole::where('name', 'anchor')->first();

    ProgramCompanyRole::create([
      'program_id' => $program->first()->id,
      'company_id' => $request->anchor_id,
      'role_id' => $anchor_role->id,
    ]);

    if ($program->first()->programType->name === Program::DEALER_FINANCING) {
      if (
        $request->has('dealer_benchmark_title') &&
        !empty($request->dealer_benchmark_title) &&
        $request->has('dealer_benchmark_rate') &&
        !empty($request->dealer_benchmark_rate) &&
        $request->has('dealer_grace_period') &&
        !empty($request->dealer_grace_period) &&
        $request->has('dealer_grace_period_discount') &&
        !empty($request->dealer_grace_period_discount) &&
        $request->has('dealer_penal_discount_on_principle') &&
        !empty($request->dealer_penal_discount_on_principle) &&
        $request->has('dealer_maturity_handling_on_holidays') &&
        !empty($request->dealer_maturity_handling_on_holidays)
      ) {
        $program
          ->first()
          ->discountDetails()
          ->create([
            'benchmark_title' => $request->dealer_benchmark_title,
            'benchmark_rate' => $request->dealer_benchmark_rate,
            'tax_on_discount' => $request->dealer_tax_on_discount,
            'limit_block_overdue_days' => $request->limit_block_overdue_days,
            'penal_discount_on_principle' => $request->dealer_penal_discount_on_principle,
            'anchor_fee_recovery' => $request->anchor_fee_recovery,
            'grace_period' => $request->dealer_grace_period,
            'grace_period_discount' => $request->dealer_grace_period_discount,
            'maturity_handling_on_holidays' => $request->dealer_maturity_handling_on_holidays,
            'discount_on_posted_discount_spread' => $request->discount_posted_spread,
            'discount_on_posted_discount' => $request->discount_posted,
            'discount_type' => $request->discount_type,
          ]);
      }

      if (
        $request->has('from_day') &&
        !empty($request->from_day) &&
        is_array($request->from_day) &&
        count($request->from_day) > 0 &&
        $request->has('to_day') &&
        !empty($request->to_day) &&
        is_array($request->to_day) &&
        count($request->to_day) > 0 &&
        is_array($request->dealer_business_strategy_spread) &&
        count($request->dealer_business_strategy_spread) > 0 &&
        is_array($request->dealer_credit_spread) &&
        count($request->dealer_credit_spread) > 0 &&
        is_array($request->dealer_total_spread) &&
        count($request->dealer_total_spread) > 0 &&
        is_array($request->dealer_total_roi) &&
        count($request->dealer_total_roi) > 0
      ) {
        foreach ($request->from_day as $key => $from_day) {
          if (
            array_key_exists($key, $request->to_day) &&
            $request->to_day[$key] != null &&
            array_key_exists($key, $request->dealer_business_strategy_spread) &&
            $request->dealer_business_strategy_spread[$key] != null &&
            array_key_exists($key, $request->dealer_credit_spread) &&
            $request->dealer_credit_spread[$key] != null &&
            array_key_exists($key, $request->dealer_total_spread) &&
            $request->dealer_total_spread[$key] != null &&
            array_key_exists($key, $request->dealer_total_roi) &&
            $request->dealer_total_roi[$key] != null
          ) {
            $program
              ->first()
              ->dealerDiscountRates()
              ->create([
                'from_day' => $from_day,
                'to_day' => $request->to_day[$key],
                'business_strategy_spread' =>
                  gettype($request->dealer_business_strategy_spread[$key]) == 'double'
                    ? round($request->dealer_business_strategy_spread[$key], 2)
                    : $request->dealer_business_strategy_spread[$key],
                'credit_spread' =>
                  gettype($request->dealer_credit_spread[$key]) == 'double'
                    ? round($request->dealer_credit_spread[$key], 2)
                    : $request->dealer_credit_spread[$key],
                'total_spread' => $request->dealer_total_spread[$key],
                'total_roi' => $request->dealer_total_roi[$key],
              ]);
          }
        }
      }
    }

    if ($program->first()->programType->name == Program::VENDOR_FINANCING) {
      if (
        $request->has('benchmark_title') &&
        !empty($request->benchmark_title) &&
        $request->has('benchmark_rate') &&
        !empty($request->benchmark_rate) &&
        $request->has('credit_spread') &&
        !empty($request->credit_spread) &&
        $request->has('business_strategy_spread') &&
        !empty($request->business_strategy_spread) &&
        $request->has('total_spread') &&
        !empty($request->total_spread) &&
        $request->has('total_roi') &&
        !empty($request->total_roi)
      ) {
        $program
          ->first()
          ->discountDetails()
          ->create([
            'benchmark_title' => $request->benchmark_title,
            'benchmark_rate' => $request->benchmark_rate,
            'reset_frequency' => $request->reset_frequency,
            'days_frequency_days' => $request->days_frequency_days,
            'business_strategy_spread' => round((float) $request->business_strategy_spread, 2),
            'credit_spread' => round((float) $request->credit_spread, 2),
            'total_spread' => $request->total_spread,
            'total_roi' => $request->total_roi,
            'anchor_discount_bearing' => $request->anchor_discount_bearing,
            'vendor_discount_bearing' => $request->vendor_discount_bearing,
            'discount_type' => $request->discount_type,
            'penal_discount_on_principle' => $request->penal_discount_on_principle,
            'anchor_fee_recovery' => $request->anchor_fee_recovery,
            'grace_period' => $request->grace_period,
            'grace_period_discount' => $request->grace_period_discount,
            'maturity_handling_on_holidays' => $request->maturity_handling_on_holidays,
            'tax_on_discount' => $request->tax_on_discount,
          ]);
      }
    }

    if ($program->first()->programType->name == 'Dealer Financing') {
      if (
        $request->has('dealer_fee_names') &&
        count($request->dealer_fee_names) > 0 &&
        $request->has('dealer_fee_types') &&
        count($request->dealer_fee_types) > 0 &&
        $request->has('dealer_fee_values') &&
        count($request->dealer_fee_values) > 0
      ) {
        foreach ($request->dealer_fee_names as $key => $value) {
          if (
            array_key_exists($key, $request->dealer_fee_names) &&
            !empty($request->dealer_fee_names[$key]) &&
            array_key_exists($key, $request->dealer_fee_values) &&
            !empty($request->dealer_fee_values[$key])
          ) {
            $program
              ->first()
              ->fees()
              ->create([
                'fee_name' => $value,
                'type' => $request->dealer_fee_types[$key],
                'value' => $request->dealer_fee_values[$key],
                'per_amount' =>
                  $request->has('dealer_fee_per_amount') &&
                  !empty($request->dealer_fee_per_amount) &&
                  count($request->dealer_fee_per_amount) > 0 &&
                  array_key_exists($key, $request->dealer_fee_per_amount) &&
                  !empty($request->dealer_fee_per_amount[$key])
                    ? $request->dealer_fee_per_amount[$key]
                    : null,
                'dealer_bearing' => 100,
                'taxes' => array_key_exists($key, $request->dealer_taxes) ? $request->dealer_taxes[$key] : null,
              ]);
          }
        }
      }
    }

    if ($program->first()->programType->name === Program::VENDOR_FINANCING) {
      if (
        $request->has('fee_names') &&
        count($request->fee_names) > 0 &&
        $request->has('fee_types') &&
        count($request->fee_types) > 0 &&
        $request->has('fee_values') &&
        count($request->fee_values) > 0
      ) {
        foreach ($request->fee_names as $key => $value) {
          if (
            array_key_exists($key, $request->fee_names) &&
            !empty($request->fee_names[$key]) &&
            array_key_exists($key, $request->fee_values) &&
            !empty($request->fee_values[$key])
          ) {
            $program
              ->first()
              ->fees()
              ->create([
                'fee_name' => $value,
                'type' => $request->fee_types[$key],
                'value' => $request->fee_values[$key],
                'per_amount' =>
                  $request->has('fee_per_amount') &&
                  !empty($request->fee_per_amount) &&
                  count($request->fee_per_amount) > 0 &&
                  array_key_exists($key, $request->fee_per_amount) &&
                  !empty($request->fee_per_amount[$key])
                    ? $request->fee_per_amount[$key]
                    : null,
                'anchor_bearing_discount' =>
                  array_key_exists($key, $request->fee_anchor_bearing_discount) &&
                  !empty($request->fee_anchor_bearing_discount[$key])
                    ? $request->fee_anchor_bearing_discount[$key]
                    : null,
                'vendor_bearing_discount' =>
                  array_key_exists($key, $request->fee_vendor_bearing_discount) &&
                  !empty($request->fee_vendor_bearing_discount[$key])
                    ? $request->fee_vendor_bearing_discount[$key]
                    : null,
                'taxes' => array_key_exists($key, $request->taxes) ? $request->taxes[$key] : null,
              ]);
          }
        }
      }
    }

    if (
      $request->has('bank_user_emails') &&
      !empty($request->bank_user_emails[0]) &&
      count($request->bank_user_emails) > 0 &&
      $request->has('bank_user_phone_numbers') &&
      !empty($request->bank_user_phone_numbers[0]) &&
      count($request->bank_user_phone_numbers) > 0
    ) {
      foreach ($request->bank_user_emails as $key => $value) {
        $program
          ->first()
          ->bankUserDetails()
          ->create([
            'email' => $value,
            'name' => array_key_exists($key, $request->bank_user_names) ? $request->bank_user_names[$key] : null,
            'phone_number' => array_key_exists($key, $request->bank_user_phone_numbers)
              ? $request->bank_user_phone_numbers[$key]
              : null,
          ]);
      }
    }

    $drafts = Program::where('bank_id', $bank->id)
      ->where('publisher_type', User::class)
      ->where('publisher_id', auth()->id())
      ->onlyDrafts()
      ->count();

    return response()->json(['message' => 'Draft saved successfully', 'revisions' => $drafts]);
  }

  public function deleteDraft(Bank $bank, Program $program)
  {
    DB::table('programs')
      ->where('id', $program->id)
      ->delete();

    toastr()->success('', 'Draft deleted successfully');

    return redirect()->back();
  }

  public function store(Request $request, Bank $bank)
  {
    abort_if(
      !auth()
        ->user()
        ->hasPermissionTo('Add/Edit Program & Mapping'),
      403,
      'You do not have permission to create programs.'
    );

    $request->validate(
      [
        'program_type_id' => ['required'],
        'name' => [
          'required',
          Rule::unique('programs')->where(function ($query) use ($bank) {
            $query->where('bank_id', $bank->id)->where('is_published', true);
          }),
        ],
        'anchor_id' => ['required'],
        'eligibility' => ['required', 'integer', 'max:100'],
        'program_code' => [
          'required',
          Rule::unique('programs', 'code')->where(function ($query) use ($bank) {
            $query->where('bank_id', $bank->id)->where('is_published', true);
          }),
        ],
      ],
      [
        'program_type_id.required' => 'Selecct program type',
        'name.required' => 'Enter Program Name',
        'name.unique' => 'The Program Name already exists',
        'program_code.required' => 'The Program Code is required',
        'program_code.unique' => 'The Program Code already exists',
        'eligibility.required' => 'Enter Program Invoice Eligibility',
        'bank_names_as_per_bank' => ['required', 'array', 'min:1'],
        'bank_names_as_per_bank.*' => ['required', 'string'],
        'account_names' => ['required', 'array', 'min:1'],
        'account_names.*' => ['required', 'string'],
        'bank_names' => ['required', 'array', 'min:1'],
        'bank_names.*' => ['required', 'string'],
      ]
    );

    $program_type = ProgramType::find($request->program_type_id);

    $request->validate([
      'program_code_id' => [
        Rule::requiredIf(function () use ($program_type) {
          return $program_type->name == Program::VENDOR_FINANCING;
        }),
      ],
      // 'benchmark_title' => [
      //   Rule::requiredIf(function () use ($program_type) {
      //     return $program_type->name == Program::VENDOR_FINANCING;
      //   }),
      // ],
      // 'benchmark_rate' => [
      //   Rule::requiredIf(function () use ($program_type) {
      //     return $program_type->name == Program::VENDOR_FINANCING;
      //   }),
      // ],
      // 'business_strategy_spread' => [
      //   Rule::requiredIf(function () use ($program_type) {
      //     return $program_type->name == Program::VENDOR_FINANCING;
      //   }),
      // ],
      'min_financing_days' => [
        Rule::requiredIf(function () use ($program_type) {
          return $program_type->name == Program::VENDOR_FINANCING;
        }),
        'integer',
        'min:1',
      ],
      'max_financing_days' => [
        Rule::requiredIf(function () use ($program_type) {
          return $program_type->name == Program::VENDOR_FINANCING;
        }),
        'integer',
        'min:1',
      ],
      // 'credit_spread' => [
      //   Rule::requiredIf(function () use ($program_type) {
      //     return $program_type->name == Program::VENDOR_FINANCING;
      //   }),
      // ],
      // 'total_spread' => [
      //   Rule::requiredIf(function () use ($program_type) {
      //     return $program_type->name == Program::VENDOR_FINANCING;
      //   }),
      // ],
      // 'total_roi' => [
      //   Rule::requiredIf(function () use ($program_type) {
      //     return $program_type->name == Program::VENDOR_FINANCING;
      //   }),
      // ],
      // 'penal_discount_on_principle' => [
      //   Rule::requiredIf(function () use ($program_type) {
      //     return $program_type->name == Program::VENDOR_FINANCING;
      //   }),
      // ],
      'discount_type' => [
        'sometimes',
        Rule::requiredIf(function () use ($program_type) {
          return $program_type->name == Program::VENDOR_FINANCING;
        }),
      ],
      // 'fee_type' => [
      //   'sometimes',
      //   Rule::requiredIf(function () use ($program_type) {
      //     return $program_type->name == Program::VENDOR_FINANCING;
      //   }),
      // ],
      // 'dealer_benchmark_title' => [
      //   Rule::requiredIf(function () use ($program_type) {
      //     return $program_type->name == Program::DEALER_FINANCING;
      //   }),
      // ],
      // 'dealer_benchmark_rate' => [
      //   Rule::requiredIf(function () use ($program_type) {
      //     return $program_type->name == Program::DEALER_FINANCING;
      //   }),
      // ],
      'from_day' => [
        Rule::requiredIf(function () use ($program_type) {
          return $program_type->name == Program::DEALER_FINANCING;
        }),
        'array',
        'min:1',
      ],
      'to_day' => [
        Rule::requiredIf(function () use ($program_type) {
          return $program_type->name == Program::DEALER_FINANCING;
        }),
        'array',
        'min:1',
      ],
      'dealer_business_strategy_spread' => [
        Rule::requiredIf(function () use ($program_type) {
          return $program_type->name == Program::DEALER_FINANCING;
        }),
        'array',
        'min:1',
      ],
      'dealer_credit_spread' => [
        Rule::requiredIf(function () use ($program_type) {
          return $program_type->name == Program::DEALER_FINANCING;
        }),
        'array',
        'min:1',
      ],
      'dealer_total_spread' => [
        Rule::requiredIf(function () use ($program_type) {
          return $program_type->name == Program::DEALER_FINANCING;
        }),
        'array',
        'min:1',
      ],
      'dealer_total_roi' => [
        Rule::requiredIf(function () use ($program_type) {
          return $program_type->name == Program::DEALER_FINANCING;
        }),
        'array',
        'min:1',
      ],
      // 'dealer_penal_discount_on_principle' => [
      //   Rule::requiredIf(function () use ($program_type) {
      //     return $program_type->name == Program::DEALER_FINANCING;
      //   }),
      // ],
      'dealer_discount_type' => [
        'sometimes',
        Rule::requiredIf(function () use ($program_type) {
          return $program_type->name == Program::DEALER_FINANCING;
        }),
      ],
      // 'dealer_fee_type' => [
      //   'sometimes',
      //   Rule::requiredIf(function () use ($program_type) {
      //     return $program_type->name == Program::DEALER_FINANCING;
      //   }),
      // ],
    ]);

    $all_accounts_set = true;

    // Depending on Discount and Fee Type, check if GLs have been set
    $selected_program_type = ProgramType::where('id', $request->program_type_id)->first();
    $selected_program_code = ProgramCode::where('id', $request->program_code_id)->first();

    // If Dealer Financing
    if ($request->dealer_discount_type == Invoice::FRONT_ENDED) {
      if ($selected_program_type->name == Program::DEALER_FINANCING) {
        $discount_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
          ->where('product_type_id', $request->program_type_id)
          ->where('name', 'Discount Income Account')
          ->first();

        if (!$discount_income_account->value) {
          $all_accounts_set = false;
        }

        $discount_receivable_from_overdraft = BankProductsConfiguration::where('bank_id', $bank->id)
          ->where('product_type_id', $request->program_type_id)
          ->where('name', 'Discount Receivable from Overdraft')
          ->first();

        if (!$discount_receivable_from_overdraft->value) {
          $all_accounts_set = false;
        }
      }
      if ($selected_program_code) {
        if ($selected_program_code->name == Program::VENDOR_FINANCING_RECEIVABLE) {
          $advanced_discount_account = BankProductsConfiguration::where('bank_id', $bank->id)
            ->where('product_type_id', $request->program_type_id)
            ->where('product_code_id', $request->program_code_id)
            ->where('name', 'Advance Discount Account')
            ->first();

          if (!$advanced_discount_account->value) {
            $all_accounts_set = false;
          }

          $discount_receivable_account = BankProductsConfiguration::where('bank_id', $bank->id)
            ->where('product_type_id', $request->program_type_id)
            ->where('product_code_id', $request->program_code_id)
            ->where('name', 'Discount Receivable Account')
            ->first();

          if (!$discount_receivable_account->value) {
            $all_accounts_set = false;
          }

          $discount_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
            ->where('product_type_id', $request->program_type_id)
            ->where('product_code_id', $request->program_code_id)
            ->where('name', 'Discount Income Account')
            ->first();

          if (!$discount_income_account->value) {
            $all_accounts_set = false;
          }
        }
        if (
          $selected_program_code->name == Program::FACTORING_WITHOUT_RECOURSE ||
          $selected_program_code->name == Program::FACTORING_WITH_RECOURSE
        ) {
          $advanced_discount_account = BankProductsConfiguration::where('bank_id', $bank->id)
            ->where('product_type_id', $request->program_type_id)
            ->where('product_code_id', $request->program_code_id)
            ->where('name', 'Advance Discount Account')
            ->first();

          if (!$advanced_discount_account->value) {
            $all_accounts_set = false;
          }

          $discount_receivable_account = BankProductsConfiguration::where('bank_id', $bank->id)
            ->where('product_type_id', $request->program_type_id)
            ->where('product_code_id', $request->program_code_id)
            ->where('name', 'Discount Receivable Account')
            ->first();

          if (!$discount_receivable_account->value) {
            $all_accounts_set = false;
          }

          $discount_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
            ->where('product_type_id', $request->program_type_id)
            ->where('product_code_id', $request->program_code_id)
            ->where('name', 'Discount Income Account')
            ->first();

          if (!$discount_income_account->value) {
            $all_accounts_set = false;
          }
        }
      }
    } else {
      if ($selected_program_type->name == Program::DEALER_FINANCING) {
        $discount_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
          ->where('product_type_id', $request->program_type_id)
          ->where('name', 'Discount Income Account')
          ->first();

        if (!$discount_income_account->value) {
          $all_accounts_set = false;
        }

        $discount_receivable_from_overdraft = BankProductsConfiguration::where('bank_id', $bank->id)
          ->where('product_type_id', $request->program_type_id)
          ->where('name', 'Discount Receivable from Overdraft')
          ->first();

        if (!$discount_receivable_from_overdraft->value) {
          $all_accounts_set = false;
        }

        $unrealized_discount_account = BankProductsConfiguration::where('bank_id', $bank->id)
          ->where('product_type_id', $request->program_type_id)
          ->where('name', 'Unrealised Discount Account')
          ->first();

        if (!$unrealized_discount_account->value) {
          $all_accounts_set = false;
        }
      }
      if ($selected_program_code) {
        if ($selected_program_code->name == Program::VENDOR_FINANCING_RECEIVABLE) {
          $unrealised_discount_account = BankProductsConfiguration::where('bank_id', $bank->id)
            ->where('product_type_id', $request->program_type_id)
            ->where('product_code_id', $request->program_code_id)
            ->where('name', 'Unrealised Discount Account')
            ->first();

          if (!$unrealised_discount_account->value) {
            $all_accounts_set = false;
          }

          $discount_receivable_account = BankProductsConfiguration::where('bank_id', $bank->id)
            ->where('product_type_id', $request->program_type_id)
            ->where('product_code_id', $request->program_code_id)
            ->where('name', 'Discount Receivable Account')
            ->first();

          if (!$discount_receivable_account->value) {
            $all_accounts_set = false;
          }

          $discount_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
            ->where('product_type_id', $request->program_type_id)
            ->where('product_code_id', $request->program_code_id)
            ->where('name', 'Discount Income Account')
            ->first();

          if (!$discount_income_account->value) {
            $all_accounts_set = false;
          }
        }
        if (
          $selected_program_code->name == Program::FACTORING_WITHOUT_RECOURSE ||
          $selected_program_code->name == Program::FACTORING_WITH_RECOURSE
        ) {
          $unrealised_discount_account = BankProductsConfiguration::where('bank_id', $bank->id)
            ->where('product_type_id', $request->program_type_id)
            ->where('product_code_id', $request->program_code_id)
            ->where('name', 'Unrealised Discount Account')
            ->first();

          if (!$unrealised_discount_account->value) {
            $all_accounts_set = false;
          }

          $discount_receivable_account = BankProductsConfiguration::where('bank_id', $bank->id)
            ->where('product_type_id', $request->program_type_id)
            ->where('product_code_id', $request->program_code_id)
            ->where('name', 'Discount Receivable Account')
            ->first();

          if (!$discount_receivable_account->value) {
            $all_accounts_set = false;
          }

          $discount_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
            ->where('product_type_id', $request->program_type_id)
            ->where('product_code_id', $request->program_code_id)
            ->where('name', 'Discount Income Account')
            ->first();

          if (!$discount_income_account->value) {
            $all_accounts_set = false;
          }
        }
      }
    }

    if (!$all_accounts_set) {
      toastr()->error('', 'Set all Discount Accounts in Configurations to proceed.');

      return back()->withInput();
    }

    $bank_users = BankUser::where('bank_id', $bank->id)
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where(function ($query) {
              $query
                ->where('name', 'Add/Edit Program & Mapping')
                ->orWhere('name', 'Activate/Deactivate Program & Mapping');
            });
          });
        });
      })
      ->where('user_id', '!=', auth()->id())
      ->where('active', true)
      ->get();

    $anchor = Company::find($request->anchor_id);

    try {
      DB::beginTransaction();

      $program = $bank->programs()->create([
        'name' => $request->name,
        'program_type_id' => $request->program_type_id,
        'program_code_id' =>
          $request->has('program_code_id') && !empty($request->program_code_id) ? $request->program_code_id : null,
        'eligibility' => $request->eligibility,
        'code' => $request->program_code,
        'invoice_margin' => $request->invoice_margin,
        'program_limit' => Str::replace(',', '', $request->program_limit),
        'approved_date' => !empty($request->approved_date)
          ? Carbon::parse($request->approved_date)->format('Y-m-d')
          : now()->format('Y-m-d'),
        'limit_expiry_date' =>
          $request->has('limit_expiry_date') && !empty($request->limit_expiry_date)
            ? Carbon::parse($request->limit_expiry_date)->format('Y-m-d')
            : null,
        'max_limit_per_account' => Str::replace(',', '', $request->max_limit_per_account),
        'collection_account' => $request->collection_account,
        'factoring_payment_account' => $request->factoring_payment_account,
        'request_auto_finance' => $request->request_auto_finance,
        'stale_invoice_period' => $request->stale_invoice_period,
        'fldg_days' => $request->fldg_days,
        'stop_supply' => $request->stop_supply,
        'min_financing_days' => $request->min_financing_days,
        'max_financing_days' => $request->max_financing_days,
        'segment' => $request->segment,
        'auto_debit_anchor_financed_invoices' => $request->auto_debit_anchor_financed_invoices,
        'auto_debit_anchor_non_financed_invoices' => $request->auto_debit_anchor_non_financed_invoices,
        'anchor_can_change_due_date' => $request->anchor_can_change_due_date,
        'max_days_due_date_extension' => $request->max_days_due_date_extension,
        'days_limit_for_due_date_change' => $request->days_limit_for_due_date_change,
        'default_payment_terms' => $request->default_payment_terms,
        'anchor_can_change_payment_term' => $request->anchor_can_change_payment_term,
        'repayment_appropriation' => $request->repayment_appropriation,
        'mandatory_invoice_attachment' => $request->mandatory_invoice_attachment,
        'partner' => $request->partner,
        'recourse' => $request->recourse ?? 'Without Recourse',
        'due_date_calculated_from' => $request->due_date_calculated_from,
        'buyer_invoice_approval_required' => $request->buyer_invoice_approval_required,
        'noa' => $request->noa,
        // 'account_status' => $bank_users->count() > 0 ? 'suspended' : 'active',
        'account_status' => 'suspended',
        'status' => 'pending',
        'created_by' => auth()->id(),
      ]);

      if ($program->programType->name == Program::VENDOR_FINANCING) {
        if ($program->programCode->name == Program::FACTORING_WITH_RECOURSE) {
          $program->update([
            'recourse' => Invoice::WITH_RECOURSE,
          ]);
        } else {
          $program->update([
            'recourse' => Invoice::WITHOUT_RECOURSE,
          ]);
        }
      }

      if ($program->programType->name == Program::DEALER_FINANCING) {
        $program->update([
          'recourse' => Invoice::WITHOUT_RECOURSE,
        ]);
      }

      if ($program->programType->name == Program::DEALER_FINANCING) {
        $program->discountDetails()->create([
          'benchmark_title' => $request->dealer_benchmark_title,
          'benchmark_rate' => $request->dealer_benchmark_rate,
          'tax_on_discount' => $request->dealer_tax_on_discount,
          'limit_block_overdue_days' => $request->limit_block_overdue_days,
          'discount_posted' => $request->discount_on_posted_discount,
          'penal_discount_on_principle' => $request->dealer_penal_discount_on_principle,
          'anchor_fee_recovery' => $request->anchor_fee_recovery,
          'grace_period' => $request->dealer_grace_period,
          'grace_period_discount' => $request->dealer_grace_period_discount,
          'maturity_handling_on_holidays' => $request->dealer_maturity_handling_on_holidays,
          'discount_on_posted_discount_spread' => $request->discount_posted_spread,
          'discount_on_posted_discount' => $request->discount_posted,
          'discount_type' =>
            $request->has('dealer_discount_type') && !empty($request->dealer_discount_type)
              ? $request->dealer_discount_type
              : Invoice::FRONT_ENDED,
          'discount_type' =>
            $request->has('dealer_fee_type') && !empty($request->dealer_fee_type)
              ? $request->dealer_fee_type
              : Invoice::FRONT_ENDED,
        ]);
        if (
          $request->has('from_day') &&
          !empty($request->from_day) &&
          is_array($request->from_day) &&
          count($request->from_day) > 0 &&
          $request->has('to_day') &&
          !empty($request->to_day) &&
          is_array($request->to_day) &&
          count($request->to_day) > 0 &&
          is_array($request->dealer_business_strategy_spread) &&
          count($request->dealer_business_strategy_spread) > 0 &&
          is_array($request->dealer_credit_spread) &&
          count($request->dealer_credit_spread) > 0 &&
          is_array($request->dealer_total_spread) &&
          count($request->dealer_total_spread) > 0 &&
          is_array($request->dealer_total_roi) &&
          count($request->dealer_total_roi) > 0
        ) {
          foreach ($request->from_day as $key => $from_day) {
            if (
              array_key_exists($key, $request->to_day) &&
              $request->to_day[$key] != null &&
              array_key_exists($key, $request->dealer_business_strategy_spread) &&
              $request->dealer_business_strategy_spread[$key] != null &&
              array_key_exists($key, $request->dealer_credit_spread) &&
              $request->dealer_credit_spread[$key] != null &&
              array_key_exists($key, $request->dealer_total_spread) &&
              $request->dealer_total_spread[$key] != null &&
              array_key_exists($key, $request->dealer_total_roi) &&
              $request->dealer_total_roi[$key] != null
            ) {
              $program->dealerDiscountRates()->create([
                'from_day' => $from_day,
                'to_day' => $request->to_day[$key],
                'business_strategy_spread' =>
                  gettype($request->dealer_business_strategy_spread[$key]) == 'double'
                    ? round($request->dealer_business_strategy_spread[$key], 2)
                    : $request->dealer_business_strategy_spread[$key],
                'credit_spread' =>
                  gettype($request->dealer_credit_spread[$key]) == 'double'
                    ? round($request->dealer_credit_spread[$key], 2)
                    : $request->dealer_credit_spread[$key],
                'total_spread' => $request->dealer_total_spread[$key],
                'total_roi' => $request->dealer_total_roi[$key],
              ]);
            }
          }
        }
      }

      if ($program->programType->name == Program::VENDOR_FINANCING) {
        // if (
        //   $request->has('benchmark_title') &&
        //   !empty($request->benchmark_title) &&
        //   $request->has('benchmark_rate') &&
        //   !empty($request->benchmark_rate) &&
        //   $request->has('credit_spread') &&
        //   !empty($request->credit_spread) &&
        //   $request->has('business_strategy_spread') &&
        //   !empty($request->business_strategy_spread) &&
        //   $request->has('total_spread') &&
        //   !empty($request->total_spread) &&
        //   $request->has('total_roi') &&
        //   !empty($request->total_roi)
        // ) {
        // }
        $program->discountDetails()->create([
          'benchmark_title' => $request->benchmark_title,
          'benchmark_rate' => $request->benchmark_rate ?? 0,
          'reset_frequency' => $request->reset_frequency,
          'days_frequency_days' => $request->days_frequency_days,
          'business_strategy_spread' => round((float) $request->business_strategy_spread ?? 0, 2),
          'credit_spread' => round((float) $request->credit_spread ?? 0, 2),
          'total_spread' => $request->total_spread ?? 0,
          'total_roi' => $request->total_roi ?? 0,
          'anchor_discount_bearing' => $request->anchor_discount_bearing ?? 0,
          'vendor_discount_bearing' => $request->vendor_discount_bearing ?? 100,
          'discount_type' =>
            $request->has('discount_type') && !empty($request->discount_type)
              ? $request->discount_type
              : Invoice::FRONT_ENDED,
          'fee_type' =>
            $request->has('fee_type') && !empty($request->fee_type) ? $request->fee_type : Invoice::FRONT_ENDED,
          'penal_discount_on_principle' => $request->penal_discount_on_principle ?? 0,
          'anchor_fee_recovery' => $request->anchor_fee_recovery,
          'grace_period' => $request->grace_period ?? 0,
          'grace_period_discount' => $request->grace_period_discount ?? 0,
          'maturity_handling_on_holidays' => $request->maturity_handling_on_holidays ?? 'No Effect',
          'tax_on_discount' => $request->tax_on_discount ?? 0,
        ]);
      }

      if ($program->programType->name == Program::DEALER_FINANCING) {
        // Get Fee Income Account as set in Configs to use as default
        $fee_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
          ->where('product_code_id', null)
          ->where('product_type_id', $program->program_type_id)
          ->where('name', 'Fee Income Account')
          ->first();

        if (
          $request->has('dealer_fee_names') &&
          count($request->dealer_fee_names) > 0 &&
          $request->has('dealer_fee_types') &&
          count($request->dealer_fee_types) > 0 &&
          $request->has('dealer_fee_values') &&
          count($request->dealer_fee_values) > 0
        ) {
          foreach ($request->dealer_fee_names as $key => $value) {
            if (
              array_key_exists($key, $request->dealer_fee_names) &&
              !empty($request->dealer_fee_names[$key]) &&
              array_key_exists($key, $request->dealer_fee_values) &&
              !empty($request->dealer_fee_values[$key])
            ) {
              $program->fees()->create([
                'fee_name' => $value,
                'type' => $request->dealer_fee_types[$key],
                'value' => $request->dealer_fee_values[$key],
                'per_amount' =>
                  $request->has('dealer_fee_per_amount') &&
                  !empty($request->dealer_fee_per_amount) &&
                  count($request->dealer_fee_per_amount) > 0 &&
                  array_key_exists($key, $request->dealer_fee_per_amount) &&
                  !empty($request->dealer_fee_per_amount[$key])
                    ? $request->dealer_fee_per_amount[$key]
                    : null,
                'dealer_bearing' => 100,
                'taxes' =>
                  $request->has('dealer_taxes') &&
                  count($request->dealer_taxes) > 0 &&
                  array_key_exists($key, $request->dealer_taxes)
                    ? $request->dealer_taxes[$key]
                    : null,
                'charge_type' =>
                  $request->has('dealer_charge_types') &&
                  !empty($request->dealer_charge_types) &&
                  count($request->dealer_charge_types) > 0
                    ? $request->dealer_charge_types[$key]
                    : 'fixed',
                'account_number' =>
                  $request->has('dealer_fee_account_numbers') &&
                  !empty($request->dealer_fee_account_numbers) &&
                  count($request->dealer_fee_account_numbers) > 0
                    ? $request->dealer_fee_account_numbers[$key]
                    : $fee_income_account->value,
              ]);
            }
          }
        }
      }

      if ($program->programType->name == Program::VENDOR_FINANCING) {
        // Get Fee Income Account as set in Configs to use as default
        $fee_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
          ->where('product_code_id', $program->program_code_id)
          ->where('product_type_id', $program->program_type_id)
          ->where('name', 'Fee Income Account')
          ->first();
        if (
          $request->has('fee_names') &&
          count($request->fee_names) > 0 &&
          $request->has('fee_types') &&
          count($request->fee_types) > 0 &&
          $request->has('fee_values') &&
          count($request->fee_values) > 0
        ) {
          foreach ($request->fee_names as $key => $value) {
            if (
              array_key_exists($key, $request->fee_names) &&
              !empty($request->fee_names[$key]) &&
              array_key_exists($key, $request->fee_values) &&
              !empty($request->fee_values[$key])
            ) {
              $program->fees()->create([
                'fee_name' => $value,
                'type' => $request->fee_types[$key],
                'value' => $request->fee_values[$key],
                'per_amount' =>
                  $request->has('fee_per_amount') &&
                  !empty($request->fee_per_amount) &&
                  count($request->fee_per_amount) > 0 &&
                  array_key_exists($key, $request->fee_per_amount) &&
                  !empty($request->fee_per_amount[$key])
                    ? $request->fee_per_amount[$key]
                    : null,
                'anchor_bearing_discount' =>
                  array_key_exists($key, $request->fee_anchor_bearing_discount) &&
                  !empty($request->fee_anchor_bearing_discount[$key])
                    ? $request->fee_anchor_bearing_discount[$key]
                    : 0,
                'vendor_bearing_discount' =>
                  array_key_exists($key, $request->fee_vendor_bearing_discount) &&
                  !empty($request->fee_vendor_bearing_discount[$key])
                    ? $request->fee_vendor_bearing_discount[$key]
                    : 100,
                'taxes' => array_key_exists($key, $request->taxes) ? $request->taxes[$key] : null,
                'charge_type' =>
                  $request->has('charge_types') && !empty($request->charge_types) && count($request->charge_types) > 0
                    ? $request->charge_types[$key]
                    : 'fixed',
                'account_number' =>
                  $request->has('fee_account_numbers') &&
                  !empty($request->fee_account_numbers) &&
                  count($request->fee_account_numbers) > 0
                    ? $request->fee_account_numbers[$key]
                    : $fee_income_account->value,
              ]);
            }
          }
        }
      }

      if (
        $request->has('anchor_names') &&
        count($request->anchor_names) > 0 &&
        $request->has('anchor_emails') &&
        count($request->anchor_emails) > 0 &&
        $request->has('country_code') &&
        count($request->country_code) > 0 &&
        $request->has('anchor_phone_numbers') &&
        count($request->anchor_phone_numbers) > 0 &&
        $request->has('anchor_roles') &&
        count($request->anchor_roles) > 0
      ) {
        foreach ($request->anchor_emails as $key => $value) {
          if (
            array_key_exists($key, $request->anchor_names) &&
            array_key_exists($key, $request->anchor_phone_numbers) &&
            array_key_exists($key, $request->anchor_roles)
          ) {
            if (
              !empty($value) &&
              !empty($request->anchor_phone_numbers[$key]) &&
              !empty($request->anchor_names[$key])
            ) {
              $user = User::where('email', $value)->first();
              if ($user) {
                CompanyUser::firstOrCreate([
                  'company_id' => $anchor->id,
                  'user_id' => $user->id,
                ]);

                $permission_data = PermissionData::find($request->anchor_roles[$key]);

                // Assign Role
                $role = Role::where('name', $permission_data->RoleName)
                  ->where('guard_name', 'web')
                  ->first();

                if ($role) {
                  $user->assignRole($role);
                }

                $link['Anchor Dashboard'] = config('app.url');

                if ($bank_users->count() <= 0) {
                  SendMail::dispatchAfterResponse($user->email, 'MappedToCompany', [
                    'type' => 'Company',
                    'data' => ['company' => $anchor->name, 'name' => $user->name, 'links' => $link],
                  ]);
                }
              } else {
                $user = User::create([
                  'email' => $value,
                  'name' => $request->anchor_names[$key],
                  'phone_number' => $request->country_code[$key] . '' . $request->anchor_phone_numbers[$key],
                  'password' => Hash::make('Secret!'),
                ]);

                CompanyUser::firstOrCreate([
                  'company_id' => $anchor->id,
                  'user_id' => $user->id,
                ]);

                $permission_data = PermissionData::find($request->anchor_roles[$key]);

                // Assign Role
                $role = Role::where('name', $permission_data->RoleName)
                  ->where('guard_name', 'web')
                  ->first();

                if ($role) {
                  $user->assignRole($role);
                }

                $link['Anchor Dashboard'] = URL::temporarySignedRoute('auth.reset-password', now()->addHour(), [
                  'id' => $user->id,
                ]);

                if ($bank_users->count() <= 0) {
                  SendMail::dispatchAfterResponse($user->email, 'MappedToCompany', [
                    'type' => 'Company',
                    'data' => ['company' => $anchor->name, 'name' => $user->name, 'links' => $link],
                  ]);
                }
              }
            }
          }
        }
      }

      if (
        $request->has('bank_user_emails') &&
        count($request->bank_user_emails) > 0 &&
        $request->has('bank_user_phone_numbers') &&
        count($request->bank_user_phone_numbers) > 0
      ) {
        foreach ($request->bank_user_emails as $key => $value) {
          if (!empty($value) && !empty($request->bank_user_phone_numbers[$key])) {
            $program->bankUserDetails()->create([
              'email' => $value,
              'name' => array_key_exists($key, $request->bank_user_names) ? $request->bank_user_names[$key] : null,
              'phone_number' => array_key_exists($key, $request->bank_user_phone_numbers)
                ? $request->bank_user_phone_numbers[$key]
                : null,
            ]);
          }
        }
      }

      if (
        $request->has('bank_names_as_per_banks') &&
        count($request->bank_names_as_per_banks) > 0 &&
        $request->has('account_numbers') &&
        count($request->account_numbers) > 0 &&
        $request->has('bank_names') &&
        count($request->bank_names) > 0
      ) {
        foreach ($request->bank_names_as_per_banks as $key => $value) {
          if (!empty($value) && !empty($request->account_numbers[$key])) {
            $program->bankDetails()->create([
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
        $company = Company::find($request->anchor_id);

        foreach ($company->bankDetails as $bank_details) {
          $program->bankDetails()->create([
            'name_as_per_bank' => $bank_details->name_as_per_bank,
            'account_number' => $bank_details->account_number,
            'bank_name' => $bank_details->bank_name,
            'branch' => $bank_details->branch,
            'swift_code' => $bank_details->swift_code,
            'account_type' => $bank_details->account_type,
          ]);
        }
      }

      $anchor_role = ProgramRole::where('name', 'anchor')->first();

      ProgramCompanyRole::create([
        'program_id' => $program->id,
        'company_id' => $request->anchor_id,
        'role_id' => $anchor_role->id,
      ]);

      // Set the default payment terms for the anchor company if program is DEALER FINANCING and is not already set
      if ($program->programType->name === Program::DEALER_FINANCING) {
        $company = Company::find($request->anchor_id);
        if (!$company->invoiceSetting()->exists()) {
          $company->invoiceSetting()->create([
            'default_payment_terms' => $request->default_payment_terms,
          ]);
        } else {
          if ($company->invoiceSetting->default_payment_terms == 0) {
            $company->invoiceSetting->update([
              'default_payment_terms' => $request->default_payment_terms,
            ]);
          }
        }
      }

      activity($bank->id)
        ->causedBy(auth()->user())
        ->performedOn($program)
        ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
        ->log('created');

      DB::commit();

      DB::table('programs')
        ->where('id', '!=', $program->id)
        ->where('name', $request->name)
        ->where('is_published', false)
        ->delete();

      // Update anchor's invoice setting to start with maker checker required if not mapped to any other programs
      $anchor = $program->anchor;
      $other_programs = Program::whereHas('anchor', function ($q) use ($anchor) {
        $q->where('companies.id', $anchor->id);
      })
        ->where('id', '!=', $program->id)
        ->count();

      if ($other_programs == 0) {
        $anchor->invoiceSetting->update([
          'maker_checker_creating_updating' => true,
        ]);
      }

      if ($bank_users->count() > 0) {
        foreach ($bank_users as $bank_user) {
          $bank_user->user->notify(new ProgramCreation($program));
          SendMail::dispatchAfterResponse($bank_user->user->email, 'ProgramCreated', ['program_id' => $program->id]);
        }
      }

      toastr()->success('', 'Program created successfully.');

      return redirect()->route('programs.show', ['bank' => $bank, 'program' => $program]);
    } catch (\Throwable $th) {
      info($th);
      DB::rollback();
      toastr()->error('', 'An error occurred while creating the program.');
      return back();
    }
  }

  public function edit(Bank $bank, Program $program)
  {
    if (!$program->can_edit) {
      toastr()->error('', 'You don\'t have permission to edit this program');
      return back();
    }

    $companies = Company::where('bank_id', $bank->id)
      ->where('approval_status', 'approved')
      ->where('status', 'active')
      ->get();

    $program_types = ProgramType::with('programCodes')->get();

    $reset_frequencies = [
      'Daily' => 1,
      'Monthly' => 30,
      'Quarterly' => 90,
      'Half Annually' => 180,
      'Annually' => 365,
    ];

    $benchmark_rates = [];

    $base_rates = BankBaseRate::active()
      ->where('bank_id', $bank->id)
      ->get();

    if ($base_rates->count() <= 0) {
      $base_rates = BaseRate::active()->get();

      foreach ($base_rates as $rate) {
        $benchmark_rates[$rate->rate_code] = $rate->rate;
      }
    } else {
      foreach ($base_rates as $rate) {
        $benchmark_rates[$rate->name] = $rate->rate;
      }
    }

    $taxes = [];

    $bank_tax_rates = BankTaxRate::active()
      ->where('bank_id', $bank->id)
      ->get();

    if ($bank_tax_rates->count() <= 0) {
      $bank_tax_rates = Tax::active()->get();

      foreach ($bank_tax_rates as $rate) {
        $taxes[$rate->name] = $rate->percentage;
      }
    } else {
      foreach ($bank_tax_rates as $rate) {
        $taxes[$rate->tax_name] = $rate->value;
      }
    }

    $program->load('programType', 'programCode', 'discountDetails', 'fees', 'dealerDiscountRates', 'bankDetails');

    $banks = BankMasterList::all();

    $mappings = ProgramVendorConfiguration::with('company', 'buyer')
      ->where('program_id', $program->id)
      ->where('status', 'active')
      ->select('id', 'payment_account_number', 'company_id', 'buyer_id', 'program_id')
      ->get();

    $bank_payment_accounts = $bank->paymentAccounts;

    return view('content.bank.programs.edit', [
      'bank' => $bank,
      'program' => $program,
      'companies' => $companies,
      'program_types' => $program_types,
      'reset_frequencies' => $reset_frequencies,
      'benchmark_rates' => $benchmark_rates,
      'taxes' => $taxes,
      'banks' => $banks,
      'mappings' => $mappings,
      'bank_payment_accounts' => $bank_payment_accounts,
    ]);
  }

  public function update(Request $request, Bank $bank, Program $program)
  {
    if (!$program->can_edit) {
      toastr()->error('', 'You don\'t have permission to edit this program');
      return back();
    }

    // Get Current Program Names
    $program_names = Program::where('bank_id', $bank->id)
      ->where('id', '!=', $program->id)
      ->where('is_published', true)
      ->pluck('name');

    $request->validate(
      [
        'name' => ['required', 'not_in:' . $program_names],
        'eligibility' => ['required', 'integer', 'max:100'],
      ],
      [
        'name' => 'Enter Program Name',
        'name.not_in' => 'The Program Name is aleady in use',
        'eligibility' => 'Enter Program Invoice Eligibility',
      ]
    );

    // Fetch other bank users who can approve progra changes
    $bank_users = BankUser::where('bank_id', $bank->id)
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where('name', 'Program Changes Checker');
          });
        });
      })
      ->where('user_id', '!=', auth()->id())
      ->where('active', true)
      ->get();

    try {
      DB::beginTransaction();

      ProgramChange::where('program_id', $program->id)->delete();

      $update_data = [];

      $program->name = $request->name;
      $program->program_code_id =
        $request->has('program_code_id') && !empty($request->program_code_id)
          ? $request->program_code_id
          : $program->program_code_id;
      $program->eligibility = $request->eligibility;
      $program->code = $request->program_code;
      $program->invoice_margin = $request->invoice_margin;
      $program->program_limit = Str::replace(',', '', $request->program_limit);
      $program->approved_date = Carbon::parse($request->approved_date)->format('Y-m-d');
      $program->limit_expiry_date =
        $request->has('limit_expiry_date') && !empty($request->limit_expiry_date)
          ? Carbon::parse($request->limit_expiry_date)->format('Y-m-d')
          : null;
      $program->max_limit_per_account = Str::replace(',', '', $request->max_limit_per_account);
      $program->collection_account = $request->collection_account;
      $program->factoring_payment_account = $request->factoring_payment_account;
      $program->request_auto_finance = $request->request_auto_finance;
      $program->stale_invoice_period =
        $request->has('stale_invoice_period') && !empty($request->stale_invoice_period)
          ? $request->stale_invoice_period
          : $program->stale_invoice_period;
      $program->stop_supply =
        $request->has('stop_supply') && !empty($request->stop_supply) ? $request->stop_supply : $program->stop_supply;
      $program->fldg_days =
        $request->has('fldg_days') && !empty($request->fldg_days) ? $request->fldg_days : $program->fldg_days;
      $program->min_financing_days =
        $request->has('min_financing_days') && !empty($request->min_financing_days)
          ? $request->min_financing_days
          : $program->min_financing_days;
      $program->max_financing_days =
        $request->has('max_financing_days') && !empty($request->max_financing_days)
          ? $request->max_financing_days
          : $program->max_financing_days;
      $program->segment = $request->segment;
      $program->auto_debit_anchor_financed_invoices = $request->auto_debit_anchor_financed_invoices;
      $program->auto_debit_anchor_non_financed_invoices = $request->auto_debit_anchor_non_financed_invoices;
      $program->anchor_can_change_due_date = $request->anchor_can_change_due_date;
      $program->max_days_due_date_extension = $request->max_days_due_date_extension;
      $program->days_limit_for_due_date_change = $request->days_limit_for_due_date_change;
      $program->default_payment_terms = $request->default_payment_terms;
      $program->anchor_can_change_payment_term = $request->anchor_can_change_payment_term;
      $program->repayment_appropriation = $request->repayment_appropriation;
      $program->mandatory_invoice_attachment = $request->mandatory_invoice_attachment;
      $program->partner = $request->partner;
      $program->recourse = $request->recourse;
      $program->due_date_calculated_from = $request->due_date_calculated_from;
      $program->buyer_invoice_approval_required = $request->buyer_invoice_approval_required;
      $program->noa = $request->noa;
      $program->account_status = $request->account_status;

      $dirty_count = 0; // Used to determine if changes were made

      if ($bank_users->count() <= 0) {
        // Check if buyer invoice approval field was changed
        if ($program->isDirty('buyer_invoice_approval_required')) {
          if (!$request->buyer_invoice_approval_required) {
            // Get all mappings made on the program
            $mappings = ProgramVendorConfiguration::where('program_id', $program->id)->get();
            foreach ($mappings as $mapping) {
              $mapping->update([
                'auto_approve_invoices' => true,
              ]);
            }
          }
        }
        $program->save();
      } else {
        if (count($program->getDirty()) > 0) {
          $dirty_count += 1; // Used to determine if changes were made
          $update_data['Program Details'] = $program->getDirty();
        }
      }

      if ($program->programType->name == Program::DEALER_FINANCING) {
        $program_discount_details = ProgramDiscount::where('program_id', $program->id)->first();
        $program_discount_details->benchmark_title = $request->dealer_benchmark_title;
        $program_discount_details->benchmark_rate = $request->dealer_benchmark_rate;
        $program_discount_details->tax_on_discount = $request->dealer_tax_on_discount;
        $program_discount_details->limit_block_overdue_days = $request->limit_block_overdue_days;
        $program_discount_details->penal_discount_on_principle = $request->dealer_penal_discount_on_principle;
        $program_discount_details->discount_type = $request->dealer_discount_type;
        $program_discount_details->fee_type = $request->dealer_fee_type;
        $program_discount_details->anchor_fee_recovery =
          $request->has('anchor_fee_recovery') && !empty($request->anchor_fee_recovery)
            ? $request->anchor_fee_recovery
            : $program->discountDetails?->first()->anchor_fee_recovery;
        $program_discount_details->grace_period = $request->dealer_grace_period;
        $program_discount_details->grace_period_discount = $request->dealer_grace_period_discount;
        $program_discount_details->maturity_handling_on_holidays = $request->dealer_maturity_handling_on_holidays;
        $program_discount_details->discount_on_posted_discount_spread = Str::replace(
          ',',
          '',
          $request->discount_posted_spread
        );
        $program_discount_details->discount_on_posted_discount = Str::replace(',', '', $request->discount_posted);
        if ($bank_users->count() <= 0) {
          $program_discount_details->save();
        } else {
          if (count($program_discount_details->getDirty()) > 0) {
            $dirty_count += 1; // Used to determine if changes were made
            $update_data['Program Discount Details'] = $program_discount_details->getDirty();
          }
        }

        if (
          $request->has('from_day') &&
          !empty($request->from_day) &&
          is_array($request->from_day) &&
          $request->has('to_day') &&
          !empty($request->to_day) &&
          is_array($request->to_day) &&
          is_array($request->dealer_business_strategy_spread) &&
          is_array($request->dealer_credit_spread) &&
          is_array($request->dealer_total_spread) &&
          is_array($request->dealer_total_roi)
        ) {
          foreach ($request->from_day as $key => $from_day) {
            if (
              array_key_exists($key, $request->to_day) &&
              array_key_exists($key, $request->dealer_business_strategy_spread) &&
              array_key_exists($key, $request->dealer_credit_spread) &&
              array_key_exists($key, $request->dealer_total_spread) &&
              array_key_exists($key, $request->dealer_total_roi)
            ) {
              if ($request->discount_details_key[$key] != '-1') {
                $program_dealer_discount_details = ProgramDealerDiscountRate::find(
                  $request->discount_details_key[$key]
                );
                $program_dealer_discount_details->from_day = $from_day;
                $program_dealer_discount_details->to_day = $request->to_day[$key];
                $program_dealer_discount_details->business_strategy_spread = round(
                  (float) $request->dealer_business_strategy_spread[$key],
                  2
                );
                $program_dealer_discount_details->credit_spread = round(
                  (float) $request->dealer_credit_spread[$key],
                  2
                );
                $program_dealer_discount_details->total_spread = $request->dealer_total_spread[$key];
                $program_dealer_discount_details->total_roi = $request->dealer_total_roi[$key];
                if ($bank_users->count() <= 0) {
                  $program_dealer_discount_details->save();
                } else {
                  if (count($program_dealer_discount_details->getDirty()) > 0) {
                    $dirty_count += 1; // Used to determine if changes were made
                    $update_data['Program Dealer Discount Rates'][
                      $program_dealer_discount_details->id
                    ] = $program_dealer_discount_details->getDirty();
                  }
                }
              } else {
                $program_dealer_discount_details = new ProgramDealerDiscountRate();
                $program_dealer_discount_details->program_id = $program->id;
                $program_dealer_discount_details->from_day = $from_day;
                $program_dealer_discount_details->to_day = $request->to_day[$key];
                $program_dealer_discount_details->business_strategy_spread = round(
                  (float) $request->dealer_business_strategy_spread[$key],
                  2
                );
                $program_dealer_discount_details->credit_spread = round(
                  (float) $request->dealer_credit_spread[$key],
                  2
                );
                $program_dealer_discount_details->total_spread = $request->dealer_total_spread[$key];
                $program_dealer_discount_details->total_roi = $request->dealer_total_roi[$key];
                if ($bank_users->count() <= 0) {
                  $program_dealer_discount_details->save();
                } else {
                  if (count($program_dealer_discount_details->getDirty()) > 0) {
                    $dirty_count += 1; // Used to determine if changes were made
                    $update_data['Program Dealer Discount Rates'][$key] = $program_dealer_discount_details->getDirty();
                  }
                }
              }
            }
          }
        }
      }

      if ($program->programType->name == Program::VENDOR_FINANCING) {
        $program_discount_details = $program->discountDetails->first();
        if (!$program_discount_details) {
          $program_discount_details = new ProgramDiscount();
          $program_discount_details->program_id = $program->id;
        }
        $program_discount_details->benchmark_title = $request->benchmark_title;
        $program_discount_details->benchmark_rate = $request->benchmark_rate ?? 0;
        $program_discount_details->reset_frequency = $request->reset_frequency;
        $program_discount_details->days_frequency_days = $request->days_frequency_days;
        $program_discount_details->business_strategy_spread = round((float) $request->business_strategy_spread ?? 0, 2);
        $program_discount_details->credit_spread = round((float) $request->credit_spread ?? 0, 2);
        $program_discount_details->total_spread = $request->total_spread ?? 0;
        $program_discount_details->total_roi = $request->total_roi ?? 0;
        $program_discount_details->tax_on_discount = $request->tax_on_discount ?? 0;
        $program_discount_details->anchor_discount_bearing = $request->anchor_discount_bearing ?? 0;
        $program_discount_details->vendor_discount_bearing = $request->vendor_discount_bearing ?? 100;
        $program_discount_details->anchor_fee_recovery =
          $request->has('anchor_fee_recovery') && !empty($request->anchor_fee_recovery)
            ? $request->anchor_fee_recovery
            : $program->discountDetails?->first()->anchor_fee_recovery;
        $program_discount_details->grace_period = $request->grace_period ?? 0;
        $program_discount_details->grace_period_discount = $request->grace_period_discount ?? 0;
        $program_discount_details->maturity_handling_on_holidays =
          $request->maturity_handling_on_holidays ?? 'No Effect';
        $program_discount_details->discount_type = $request->discount_type ?? Invoice::FRONT_ENDED;
        $program_discount_details->fee_type = $request->fee_type ?? Invoice::FRONT_ENDED;

        if ($bank_users->count() <= 0) {
          $program_discount_details->save();
        } else {
          if (count($program_discount_details->getDirty()) > 0) {
            $dirty_count += 1; // Used to determine if changes were made
            $update_data['Program Discount Details'] = $program_discount_details->getDirty();
          }
        }
      }

      if ($program->programType->name === Program::DEALER_FINANCING) {
        $fee_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
          ->where('product_code_id', null)
          ->where('product_type_id', $program->program_type_id)
          ->where('name', 'Fee Income Account')
          ->first();
      } else {
        $fee_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
          ->where('product_code_id', $program->program_code_id)
          ->where('product_type_id', $program->program_type_id)
          ->where('name', 'Fee Income Account')
          ->first();
      }

      if (
        $request->has('fee_names') &&
        count($request->fee_names) > 0 &&
        $request->has('fee_types') &&
        count($request->fee_types) > 0 &&
        $request->has('fee_values') &&
        count($request->fee_values) > 0
      ) {
        foreach ($request->fee_names as $key => $value) {
          if (
            array_key_exists($key, $request->fee_names) &&
            !empty($request->fee_names[$key]) &&
            array_key_exists($key, $request->fee_values) &&
            !empty($request->fee_values[$key])
          ) {
            if ($request->fee_key[$key] != '-1') {
              $program_fee = ProgramFee::find($request->fee_key[$key]);
              $program_fee->fee_name = $value;
              $program_fee->type = $request->fee_types[$key];
              $program_fee->value = $request->fee_values[$key];
              $program_fee->per_amount =
                $request->has('fee_per_amount') &&
                !empty($request->fee_per_amount) &&
                count($request->fee_per_amount) > 0 &&
                array_key_exists($key, $request->fee_per_amount) &&
                !empty($request->fee_per_amount[$key])
                  ? $request->fee_per_amount[$key]
                  : null;
              if ($program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
                $program_fee->vendor_bearing_discount = array_key_exists($key, $request->fee_vendor_bearing_discount)
                  ? $request->fee_vendor_bearing_discount[$key]
                  : 100;
                $program_fee->anchor_bearing_discount = array_key_exists($key, $request->fee_anchor_bearing_discount)
                  ? $request->fee_anchor_bearing_discount[$key]
                  : 0;
              } else {
                $program_fee->vendor_bearing_discount = array_key_exists($key, $request->fee_vendor_bearing_discount)
                  ? $request->fee_vendor_bearing_discount[$key]
                  : 0;
                $program_fee->anchor_bearing_discount = array_key_exists($key, $request->fee_anchor_bearing_discount)
                  ? $request->fee_anchor_bearing_discount[$key]
                  : 100;
              }
              $program_fee->taxes = array_key_exists($key, $request->taxes) ? $request->taxes[$key] : null;
              $program_fee->charge_type =
                $request->has('charge_types') &&
                !empty($request->charge_types) &&
                count($request->charge_types) > 0 &&
                array_key_exists($key, $request->charge_types)
                  ? $request->charge_types[$key]
                  : 'fixed';
              $program_fee->account_number =
                $request->has('fee_account_numbers') &&
                !empty($request->fee_account_numbers) &&
                count($request->fee_account_numbers) > 0 &&
                array_key_exists($key, $request->fee_account_numbers)
                  ? $request->fee_account_numbers[$key]
                  : $fee_income_account->value;
              if ($bank_users->count() <= 0) {
                $program_fee->save();
              } else {
                if (count($program_fee->getDirty()) > 0) {
                  $dirty_count += 1; // Used to determine if changes were made
                  $update_data['Program Fees'][$program_fee->id] = $program_fee->getDirty();
                }
              }
            } else {
              $program_fee = new ProgramFee();
              $program_fee->fee_name = $value;
              $program_fee->type = $request->fee_types[$key];
              $program_fee->value = $request->fee_values[$key];
              $program_fee->per_amount =
                $request->has('fee_per_amount') &&
                !empty($request->fee_per_amount) &&
                count($request->fee_per_amount) > 0 &&
                array_key_exists($key, $request->fee_per_amount) &&
                !empty($request->fee_per_amount[$key])
                  ? $request->fee_per_amount[$key]
                  : null;
              if ($program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
                $program_fee->vendor_bearing_discount = array_key_exists($key, $request->fee_vendor_bearing_discount)
                  ? $request->fee_vendor_bearing_discount[$key]
                  : 100;
                $program_fee->anchor_bearing_discount = array_key_exists($key, $request->fee_anchor_bearing_discount)
                  ? $request->fee_anchor_bearing_discount[$key]
                  : 0;
              } else {
                $program_fee->vendor_bearing_discount = array_key_exists($key, $request->fee_vendor_bearing_discount)
                  ? $request->fee_vendor_bearing_discount[$key]
                  : 0;
                $program_fee->anchor_bearing_discount = array_key_exists($key, $request->fee_anchor_bearing_discount)
                  ? $request->fee_anchor_bearing_discount[$key]
                  : 100;
              }

              $program_fee->taxes = array_key_exists($key, $request->taxes) ? $request->taxes[$key] : null;
              $program_fee->charge_type =
                $request->has('charge_types') &&
                !empty($request->charge_types) &&
                count($request->charge_types) > 0 &&
                array_key_exists($key, $request->charge_types)
                  ? $request->charge_types[$key]
                  : 'fixed';
              $program_fee->account_number =
                $request->has('fee_account_numbers') &&
                !empty($request->fee_account_numbers) &&
                count($request->fee_account_numbers) > 0 &&
                array_key_exists($key, $request->fee_account_numbers)
                  ? $request->fee_account_numbers[$key]
                  : $fee_income_account->value;
              if ($bank_users->count() <= 0) {
                $program_fee->save();
              } else {
                if (count($program_fee->getDirty()) > 0) {
                  $dirty_count += 1; // Used to determine if changes were made
                  $update_data['Program Fees'][$key] = $program_fee->getDirty();
                }
              }
            }
          }
        }
      }

      if (
        $request->has('dealer_fee_names') &&
        count($request->dealer_fee_names) > 0 &&
        $request->has('dealer_fee_types') &&
        count($request->dealer_fee_types) > 0 &&
        $request->has('dealer_fee_values') &&
        count($request->dealer_fee_values) > 0
      ) {
        foreach ($request->dealer_fee_names as $key => $value) {
          if (
            array_key_exists($key, $request->dealer_fee_names) &&
            !empty($request->dealer_fee_names[$key]) &&
            array_key_exists($key, $request->dealer_fee_values) &&
            !empty($request->dealer_fee_values[$key])
          ) {
            if ($request->dealer_fee_key[$key] != '-1') {
              $program_fee = ProgramFee::find($request->dealer_fee_key[$key]);
              $program_fee->fee_name = $value;
              $program_fee->type = $request->dealer_fee_types[$key];
              $program_fee->value = $request->dealer_fee_values[$key];
              $program_fee->per_amount =
                $request->has('dealer_fee_per_amount') &&
                !empty($request->dealer_fee_per_amount) &&
                count($request->dealer_fee_per_amount) > 0 &&
                array_key_exists($key, $request->dealer_fee_per_amount) &&
                !empty($request->dealer_fee_per_amount[$key])
                  ? $request->dealer_fee_per_amount[$key]
                  : null;
              $program_fee->dealer_bearing =
                array_key_exists($key, $request->fee_dealer_bearing_discount) &&
                !empty($request->fee_dealer_bearing_discount[$key])
                  ? $request->fee_dealer_bearing_discount[$key]
                  : null;
              $program_fee->taxes = array_key_exists($key, $request->dealer_taxes)
                ? $request->dealer_taxes[$key]
                : null;
              $program_fee->charge_type =
                $request->has('dealer_charge_types') &&
                !empty($request->dealer_charge_types) &&
                count($request->dealer_charge_types) > 0 &&
                array_key_exists($key, $request->dealer_charge_types)
                  ? $request->dealer_charge_types[$key]
                  : 'fixed';
              $program_fee->account_number =
                $request->has('dealer_fee_account_numbers') &&
                !empty($request->dealer_fee_account_numbers) &&
                count($request->dealer_fee_account_numbers) > 0 &&
                array_key_exists($key, $request->dealer_fee_account_numbers)
                  ? $request->dealer_fee_account_numbers[$key]
                  : $fee_income_account->value;
              if ($bank_users->count() <= 0) {
                $program_fee->save();
              } else {
                if (count($program_fee->getDirty()) > 0) {
                  $dirty_count += 1; // Used to determine if changes were made
                  $update_data['Program Fees'][$program_fee->id] = $program_fee->getDirty();
                }
              }
            } else {
              $program_fee = new ProgramFee();
              $program_fee->fee_name = $value;
              $program_fee->type = $request->dealer_fee_types[$key];
              $program_fee->value = $request->dealer_fee_values[$key];
              $program_fee->per_amount =
                $request->has('dealer_fee_per_amount') &&
                !empty($request->dealer_fee_per_amount) &&
                count($request->dealer_fee_per_amount) > 0 &&
                array_key_exists($key, $request->dealer_fee_per_amount) &&
                !empty($request->dealer_fee_per_amount[$key])
                  ? $request->dealer_fee_per_amount[$key]
                  : null;
              $program_fee->dealer_bearing =
                array_key_exists($key, $request->fee_dealer_bearing_discount) &&
                !empty($request->fee_dealer_bearing_discount[$key])
                  ? $request->fee_dealer_bearing_discount[$key]
                  : null;
              $program_fee->taxes =
                $request->has('dealer_taxes') && array_key_exists($key, $request->dealer_taxes)
                  ? $request->dealer_taxes[$key]
                  : null;
              $program_fee->charge_type =
                $request->has('dealer_charge_types') &&
                !empty($request->dealer_charge_types) &&
                count($request->dealer_charge_types) > 0 &&
                array_key_exists($key, $request->dealer_charge_types)
                  ? $request->dealer_charge_types[$key]
                  : 'fixed';
              $program_fee->account_number =
                $request->has('dealer_fee_account_numbers') &&
                !empty($request->dealer_fee_account_numbers) &&
                count($request->dealer_fee_account_numbers) > 0 &&
                array_key_exists($key, $request->dealer_fee_account_numbers)
                  ? $request->dealer_fee_account_numbers[$key]
                  : $fee_income_account->value;
              if ($bank_users->count() <= 0) {
                $program_fee->save();
              } else {
                if (count($program_fee->getDirty()) > 0) {
                  $dirty_count += 1; // Used to determine if changes were made
                  $update_data['Program Fees'][$key] = $program_fee->getDirty();
                }
              }
            }
          }
        }
      }

      if (
        $request->has('bank_user_emails') &&
        count($request->bank_user_emails) > 0 &&
        $request->has('bank_user_phone_numbers') &&
        count($request->bank_user_phone_numbers) > 0
      ) {
        foreach ($request->bank_user_emails as $key => $value) {
          if ($request->bank_user_key[$key] != '-1') {
            $bank_user = ProgramBankUserDetails::find($request->bank_user_key[$key]);
            $bank_user->email = $request->bank_user_emails[$key];
            $bank_user->name = array_key_exists($key, $request->bank_user_names)
              ? $request->bank_user_names[$key]
              : null;
            $bank_user->phone_number = array_key_exists($key, $request->bank_user_phone_numbers)
              ? $request->bank_user_phone_numbers[$key]
              : null;
            if ($bank_users->count() <= 0) {
              $bank_user->save();
            } else {
              if (count($bank_user->getDirty()) > 0) {
                $dirty_count += 1; // Used to determine if changes were made
                $update_data['Program Bank User Details'][$bank_user->id] = $bank_user->getDirty();
              }
            }
          } else {
            $bank_user = new ProgramBankUserDetails();
            $bank_user->program_id = $program->id;
            $bank_user->email = $request->bank_user_emails[$key];
            $bank_user->name = array_key_exists($key, $request->bank_user_names)
              ? $request->bank_user_names[$key]
              : null;
            $bank_user->phone_number = array_key_exists($key, $request->bank_user_phone_numbers)
              ? $request->bank_user_phone_numbers[$key]
              : null;
            if ($bank_users->count() <= 0) {
              $bank_user->save();
            } else {
              if (count($bank_user->getDirty()) > 0) {
                $dirty_count += 1; // Used to determine if changes were made
                $update_data['Program Bank User Details'][$key] = $bank_user->getDirty();
              }
            }
          }
        }
      }

      if (
        $request->has('bank_names_as_per_banks') &&
        count($request->bank_names_as_per_banks) > 0 &&
        $request->has('account_numbers') &&
        count($request->account_numbers) > 0 &&
        $request->has('bank_names') &&
        count($request->bank_names) > 0
      ) {
        foreach ($request->bank_names_as_per_banks as $key => $value) {
          if ($request->bank_details[$key] != '-1') {
            $bank_account_details = ProgramBankDetails::find($request->bank_details[$key]);
            $bank_account_details->name_as_per_bank = $request->bank_names_as_per_banks[$key];
            $bank_account_details->account_number = array_key_exists($key, $request->account_numbers)
              ? $request->account_numbers[$key]
              : null;
            $bank_account_details->bank_name = array_key_exists($key, $request->bank_names)
              ? $request->bank_names[$key]
              : null;
            $bank_account_details->branch = array_key_exists($key, $request->branches)
              ? $request->branches[$key]
              : null;
            $bank_account_details->swift_code = array_key_exists($key, $request->swift_codes)
              ? $request->swift_codes[$key]
              : null;
            $bank_account_details->account_type = array_key_exists($key, $request->account_types)
              ? $request->account_types[$key]
              : null;
            if ($bank_users->count() <= 0) {
              $bank_account_details->save();
            } else {
              if (count($bank_account_details->getDirty()) > 0) {
                $dirty_count += 1; // Used to determine if changes were made
                $update_data['Program Bank Details'][$bank_account_details->id] = $bank_account_details->getDirty();
              }
            }
          } else {
            // New Bank Details were added
            $bank_account_details = new ProgramBankDetails();
            $bank_account_details->program_id = $program->id;
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
              $update_data['Program Bank Details']['-' . $key] = $bank_account_details->getDirty();
            }
          }
        }
      }

      if ($request->has('program_vendor_configurations') && count($request->program_vendor_configurations) > 0) {
        $update_data['Program Vendor Configurations'] = $request->program_vendor_configurations;
      }

      // Check if changes were made to program fees
      $program_fees_changes = ProgramFee::where('program_id', $program->id)
        ->where('deleted_at', '!=', null)
        ->get();

      $program_bank_details_changes = ProgramBankDetails::where('program_id', $program->id)
        ->where('deleted_at', '!=', null)
        ->get();

      if ($dirty_count == 0 && $program_fees_changes->count() == 0 && $program_bank_details_changes->count() == 0) {
        toastr()->error('', 'No Changes were made to the program.');
        return back();
      }

      // If there are users who can approve program changes,
      // Save the changes temporarily for approval
      if ($bank_users->count() > 0) {
        if ($program_fees_changes->count() > 0) {
          foreach ($program_fees_changes as $fee_change) {
            $update_data['Program Fees'][$fee_change->id] = [
              'name' => $fee_change->fee_name,
              'deleted_at' => $fee_change->deleted_at,
            ];
          }
        }

        if ($program_bank_details_changes->count() > 0) {
          foreach ($program_bank_details_changes as $bank_details) {
            $update_data['Program Bank Details'][$bank_details->id] = [
              'name_as_per_bank' => $bank_details->name_as_per_bank,
              'account_number' => $bank_details->account_number,
              'bank_name' => $bank_details->bank_name,
              'branch' => $bank_details->branch,
              'swift_code' => $bank_details->swift_code,
              'account_type' => $bank_details->account_type,
              'deleted_at' => $bank_details->deleted_at,
            ];
          }
        }

        ProgramChange::create([
          'program_id' => $program->id,
          'changes' => $update_data,
          'user_id' => auth()->id(),
        ]);

        $type = '';
        if ($program->programType->name == Program::VENDOR_FINANCING) {
          if ($program->programCode && $program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
            $type = 'vendor_financing';
          }
          if (
            ($program->programCode && $program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE) ||
            $program->programCode->name == Program::FACTORING_WITH_RECOURSE
          ) {
            $type = 'factoring';
          }
        } else {
          $type = 'dealer_financing';
        }

        activity($bank->id)
          ->causedBy(auth()->user())
          ->performedOn($program)
          ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
          ->log('proposed update');
        // Notify users of changes
        foreach ($bank_users as $bank_user) {
          SendMail::dispatchAfterResponse($bank_user->user->email, 'ProgramChanged', [
            'program' => $program->id,
            'url' => route('programs.show', ['bank' => $bank, 'program' => $program]),
            'name' => auth()->user()->name,
            'type' => $type,
          ]);
          $bank_user->user->notify(new ProgramUpdation($program));
        }

        toastr()->success('', 'Program changes sent for approval.');
      } else {
        activity($bank->id)
          ->causedBy(auth()->user())
          ->performedOn($program)
          ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
          ->log('updated program');

        Program::where('bank_id', $bank->id)
          ->where('name', $program->name)
          ->where('is_published', false)
          ->delete();

        toastr()->success('', 'Program updated');
      }

      DB::commit();

      return redirect()->route('programs.show', ['bank' => $bank, 'program' => $program]);
    } catch (\Throwable $th) {
      info($th);
      DB::rollback();
      toastr()->error('', 'An error occurred while updating the program.');
      return back();
    }
  }

  public function deleteProgramFee(Bank $bank, Program $program, $program_fee_id)
  {
    $program_fee = ProgramFee::find($program_fee_id);
    if ($program_fee) {
      $program_fee->deleted_at = now();
      $program_fee->save();

      if (request()->wantsJson()) {
        return response()->json(['status' => 'success']);
      }

      return back();
    }

    return response()->json(['status' => 'succcess'], 200);
  }

  public function deleteProgramBankDetails(Bank $bank, Program $program, $program_bank_details_id)
  {
    $program_bank_details = ProgramBankDetails::find($program_bank_details_id);
    if ($program_bank_details) {
      $program_bank_details->deleted_at = now();
      $program_bank_details->save();

      if (request()->wantsJson()) {
        return response()->json(['status' => 'success']);
      }

      return back();
    }

    return response()->json(['status' => 'succcess'], 200);
  }

  public function delete(Bank $bank, Program $program)
  {
    // Fetch other bank users who can approve progra changes
    $bank_users = BankUser::where('bank_id', $bank->id)
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where('name', 'Add/Edit Program & Mapping');
          });
        });
      })
      ->where('user_id', '!=', auth()->id())
      ->where('active', true)
      ->get();

    if ($bank_users->count() <= 0 || $program->deleted_at) {
      activity($bank->id)
        ->causedBy(auth()->user())
        ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
        ->log('deleted program ' . $program->name);

      $program->delete();

      if (request()->wantsJson()) {
        return response()->json(['message' => 'Program deleted successfully']);
      }

      toastr()->success('', 'Program Deleted successfully');

      return redirect()->route('programs.index');
    } else {
      $program->update([
        'deleted_at' => now(),
        'deleted_by' => auth()->id(),
      ]);

      $type = '';
      if ($program->programType->name == Program::VENDOR_FINANCING) {
        if ($program->programCode && $program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
          $type = 'vendor_financing';
        }
        if (
          ($program->programCode && $program->programCode->name == Program::FACTORING_WITH_RECOURSE) ||
          $program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE
        ) {
          $type = 'factoring';
        }
      }
      if ($program->programType->name == Program::DEALER_FINANCING) {
        $type = 'dealer_financing';
      }

      foreach ($bank_users as $bank_user) {
        SendMail::dispatchAfterResponse($bank_user->user->email, 'ProgramChanged', [
          'program' => $program->id,
          'url' => route('programs.show', ['bank' => $bank, 'program' => $program]),
          'name' => auth()->user()->name,
          'type' => $type,
        ]);
        $bank_user->user->notify(new ProgramUpdation($program));
      }

      if (request()->wantsJson()) {
        return response()->json(['message' => 'Program deletion sent for approval'], 200);
      }

      toastr()->success('', 'Program deletion sent for approval');

      return redirect()->route('programs.show', ['bank' => $bank->url, 'program' => $program]);
    }
  }

  public function cancelDeletion(Bank $bank, Program $program)
  {
    $program->update([
      'deleted_at' => null,
      'deleted_by' => null,
    ]);

    if (request()->wantsJson()) {
      return response()->json(['message' => 'Program deletion cancelled'], 200);
    }

    toastr()->success('', 'Program deletion cancelled');

    return back();
  }

  public function approveProgram(Bank $bank, Program $program, $status)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Activate/Deactivate Program & Mapping')
    ) {
      toastr()->error('', 'You are not allowed to perform this action');

      return back();
    }

    if ($status == 'approved') {
      try {
        DB::beginTransaction();

        $program->status = 'approved';
        $program->account_status = 'active';
        $program->save();

        // Update anchor's invoice setting to start with maker checker required if not mapped to any other programs
        $other_programs = Program::whereHas('anchor', function ($q) use ($program) {
          $q->where('companies.id', $program->anchor->id);
        })
          ->where('id', '!=', $program->id)
          ->count();

        if ($other_programs == 0) {
          $program->anchor->invoiceSetting->update([
            'maker_checker_creating_updating' => true,
          ]);
        }

        activity($bank->id)
          ->causedBy(auth()->user())
          ->performedOn($program)
          ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
          ->log('approved program');

        $users = $program->anchor->users->where('last_login', null);

        foreach ($users as $user) {
          $link['Anchor Dashboard'] = URL::temporarySignedRoute('auth.reset-password', now()->addHour(), [
            'id' => $user->id,
          ]);

          SendMail::dispatchAfterResponse($user->email, 'MappedToCompany', [
            'type' => 'Company',
            'data' => ['company' => $program->anchor->name, 'name' => $user->name, 'links' => $link],
          ]);
        }

        DB::commit();
      } catch (\Throwable $th) {
        DB::rollBack();
        info($th);
      }
    } else {
      $program->status = 'rejected';
      $program->account_status = 'suspended';
      $program->save();

      activity($bank->id)
        ->causedBy(auth()->user())
        ->performedOn($program)
        ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
        ->log('rejected to program');
    }

    Program::where('bank_id', $bank->id)
      ->where('name', $program->name)
      ->where('is_published', false)
      ->delete();

    toastr()->success('', 'Program updated successfully');

    return back();
  }

  public function approveChanges(Bank $bank, Program $program, $status, Request $request)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Program Changes Checker')
    ) {
      toastr()->error('', 'You are not allowed to perform this action');

      if (request()->wantsJson()) {
        return response()->json(['mesasge' => 'You are not allowed to perform this action'], 401);
      }

      return back();
    }

    $proposed_updates = $program->proposedUpdate;

    if ($status == 'approve') {
      try {
        DB::beginTransaction();

        foreach ($proposed_updates->changes as $key => $update) {
          if ($key == 'Program Details') {
            foreach ($update as $column => $data) {
              $program->update([
                $column => $data,
              ]);

              if ($column === 'buyer_invoice_approval_required') {
                // Check if buyer invoice approval field was changed
                if (!$program->buyer_invoice_approval_required) {
                  // Get all mappings made on the program
                  $mappings = ProgramVendorConfiguration::where('program_id', $program->id)->get();
                  foreach ($mappings as $mapping) {
                    $mapping->update([
                      'auto_approve_invoices' => true,
                    ]);
                  }
                }
              }
            }
          }
          if ($key === 'Program Discount Details') {
            $program_discount_details = ProgramDiscount::where('program_id', $program->id)->first();
            if (!$program_discount_details) {
              $program_discount_details = new ProgramDiscount();
              $program_discount_details->program_id = $program->id;
            }
            foreach ($update as $column => $data) {
              $program_discount_details->$column = $data;
            }
            $program_discount_details->save();
            // Update selected program mappings
            if ($request->has('program_id') && count($request->program_id) > 0) {
              foreach ($request->program_id as $key => $program_id) {
                $vendor_configuration = ProgramVendorConfiguration::find($program_id);
                $vendor_discount_details = ProgramVendorDiscount::where('program_id', $vendor_configuration->program_id)
                  ->where('company_id', $vendor_configuration->company_id)
                  ->when($vendor_configuration->buyer_id, function ($query) use ($vendor_configuration) {
                    $query->where('buyer_id', $vendor_configuration->buyer_id);
                  })
                  ->get();
                foreach ($vendor_discount_details as $key => $vendor_discount_detail) {
                  foreach ($update as $column => $data) {
                    if (Schema::hasColumn('program_vendor_discounts', $column)) {
                      $vendor_discount_detail->$column = $data;
                    }
                  }
                  $vendor_discount_detail->save();
                }
              }
            }
          }
          if ($key == 'Program Dealer Discount Rates') {
            foreach ($update as $id => $rows) {
              $dealer_discount_rate = ProgramDealerDiscountRate::where('program_id', $program->id)
                ->where('id', $id)
                ->first();
              if ($dealer_discount_rate) {
                foreach ($rows as $column => $data) {
                  foreach ($rows as $column => $data) {
                    $rate_details[$column] = $data;
                  }
                }
                $dealer_discount_rate->update($rate_details);
              } else {
                $rate_details = ['program_id' => $program->id];
                foreach ($rows as $column => $data) {
                  $rate_details[$column] = $data;
                }
                ProgramDealerDiscountRate::create($rate_details);
              }
            }
            // Update selected program mappings
            if ($request->has('program_id') && count($request->program_id) > 0) {
              $vendor_discount_rates = ProgramDealerDiscountRate::where('program_id', $program->id)->get();
              foreach ($request->program_id as $key => $program_id) {
                $vendor_configuration = ProgramVendorConfiguration::find($program_id);
                $vendor_discount_details = ProgramDiscount::where(
                  'program_id',
                  $vendor_configuration->program_id
                )->first();
                ProgramVendorDiscount::where('program_id', $vendor_configuration->program_id)
                  ->where('company_id', $vendor_configuration->company_id)
                  ->delete();
                foreach ($vendor_discount_rates as $key => $discount_rate) {
                  $vendor_fee_details = new ProgramVendorDiscount();
                  $vendor_fee_details->program_id = $vendor_configuration->program_id;
                  $vendor_fee_details->company_id = $vendor_configuration->company_id;
                  $vendor_fee_details->benchmark_title = $vendor_discount_details->benchmark_title;
                  $vendor_fee_details->benchmark_rate = $vendor_discount_details->benchmark_rate;
                  $vendor_fee_details->from_day = $discount_rate->from_day;
                  $vendor_fee_details->to_day = $discount_rate->to_day;
                  $vendor_fee_details->business_strategy_spread = $discount_rate->business_strategy_spread;
                  $vendor_fee_details->credit_spread = $discount_rate->credit_spread;
                  $vendor_fee_details->total_spread = $discount_rate->total_spread;
                  $vendor_fee_details->total_roi = $discount_rate->total_roi;
                  $vendor_fee_details->penal_discount_on_principle =
                    $vendor_discount_details->penal_discount_on_principle;
                  $vendor_fee_details->grace_period = $vendor_discount_details->grace_period;
                  $vendor_fee_details->grace_period_discount = $vendor_discount_details->grace_period_discount;
                  $vendor_fee_details->maturity_handling_on_holidays =
                    $vendor_discount_details->maturity_handling_on_holidays;
                  $vendor_fee_details->limit_block_overdue_days = $vendor_discount_details->limit_block_overdue_days;
                  $vendor_fee_details->discount_on_posted_discount_spread =
                    $vendor_discount_details->discount_on_posted_discount_spread;
                  $vendor_fee_details->discount_on_posted_discount =
                    $vendor_discount_details->discount_on_posted_discount;
                  $vendor_fee_details->save();
                }
              }
            }
          }
          if ($key == 'Program Fees') {
            foreach ($update as $id => $rows) {
              $program_fee = ProgramFee::where('program_id', $program->id)
                ->where('id', $id)
                ->first();
              if ($program_fee) {
                if ($program_fee->deleted_at) {
                  $program_fee->delete();
                } else {
                  foreach ($rows as $column => $data) {
                    foreach ($rows as $column => $data) {
                      $fee_details[$column] = $data;
                    }
                  }
                  $program_fee->update($fee_details);
                }
              } else {
                $fee_details = ['program_id' => $program->id];
                foreach ($rows as $column => $data) {
                  $fee_details[$column] = $data;
                }
                ProgramFee::create($fee_details);
              }
            }
            if ($request->has('program_id') && count($request->program_id) > 0) {
              $program_fees = ProgramFee::where('program_id', $program->id)->get();
              foreach ($request->program_id as $key => $program_id) {
                $vendor_configuration = ProgramVendorConfiguration::find($program_id);
                $vendor_fee_details = ProgramVendorFee::where('program_id', $vendor_configuration->program_id)
                  ->where('company_id', $vendor_configuration->company_id)
                  ->when($vendor_configuration->buyer_id, function ($query) use ($vendor_configuration) {
                    $query->where('buyer_id', $vendor_configuration->buyer_id);
                  })
                  ->delete();
                foreach ($program_fees as $key => $program_fee) {
                  $vendor_fee_details = new ProgramVendorFee();
                  $vendor_fee_details->program_id = $vendor_configuration->program_id;
                  $vendor_fee_details->company_id = $vendor_configuration->company_id;
                  $vendor_fee_details->buyer_id = $vendor_configuration->buyer_id;
                  $vendor_fee_details->fee_name = $program_fee->fee_name;
                  $vendor_fee_details->type = $program_fee->type;
                  $vendor_fee_details->value = $program_fee->value;
                  $vendor_fee_details->per_amount = $program_fee->per_amount;
                  $vendor_fee_details->anchor_bearing_discount = $program_fee->anchor_bearing_discount;
                  $vendor_fee_details->vendor_bearing_discount = $program_fee->vendor_bearing_discount;
                  $vendor_fee_details->taxes = $program_fee->taxes;
                  $vendor_fee_details->charge_type = $program_fee->charge_type;
                  $vendor_fee_details->account_number = $program_fee->account_number;
                  $vendor_fee_details->account_name = $program_fee->account_name;
                  $vendor_fee_details->dealer_bearing = $program_fee->dealer_bearing;
                  $vendor_fee_details->save();
                }
              }
            }
          }
          if ($key == 'Program Bank User Details') {
            foreach ($update as $id => $rows) {
              $bank_user = ProgramBankUserDetails::where('program_id', $program->id)
                ->where('id', $id)
                ->first();
              if ($bank_user) {
                foreach ($rows as $column => $data) {
                  foreach ($rows as $column => $data) {
                    $bank_user_details[$column] = $data;
                  }
                }
                $bank_user->update($bank_user_details);
              } else {
                $bank_user_details = ['program_id' => $program->id];
                foreach ($rows as $column => $data) {
                  $bank_user_details[$column] = $data;
                }
                ProgramBankUserDetails::create($bank_user_details);
              }
            }
          }
          if ($key == 'Program Bank Details') {
            foreach ($update as $id => $rows) {
              $bank_details = ProgramBankDetails::where('id', $id)
                ->where('program_id', $program->id)
                ->first();
              if ($bank_details) {
                if ($bank_details->deleted_at) {
                  $bank_details->delete();
                } else {
                  foreach ($rows as $column => $data) {
                    $bank_details[$column] = $data;
                  }
                  $bank_details->save();
                }
              } else {
                $bank_details = ['program_id' => $program->id];
                foreach ($rows as $column => $data) {
                  $bank_details[$column] = $data;
                }
                ProgramBankDetails::create($bank_details);
              }
            }
          }
        }

        activity($bank->id)
          ->causedBy(auth()->user())
          ->performedOn($program)
          ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
          ->log('approved update to program');

        $proposed_updates->user->notify(new ChangesApproval([$proposed_updates], 'approved'));

        DB::commit();
      } catch (\Throwable $th) {
        DB::rollBack();
        info($th);
      }
    } else {
      activity($bank->id)
        ->causedBy(auth()->user())
        ->performedOn($program)
        ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
        ->log('rejected update to program');
    }

    if ($status != 'approve') {
      // Remove deleted_at timestamp from program fees
      ProgramFee::where('program_id', $program->id)
        ->where('deleted_at', '!=', null)
        ->update([
          'deleted_at' => null,
        ]);

      // Remove deleted_at timestamp from program bank details
      ProgramBankDetails::where('program_id', $program->id)
        ->where('deleted_at', '!=', null)
        ->update([
          'deleted_at' => null,
        ]);

      $proposed_updates->user->notify(new ChangesApproval([$proposed_updates], 'rejected'));
    }

    // Notify other bank users
    foreach ($proposed_updates->program->bank->users as $user) {
      if ($user->id != auth()->id()) {
        SendMail::dispatchAfterResponse($user->email, 'ProgramChangesUpdated', [
          'program_id' => $proposed_updates->program->id,
          'status' => $status,
          'user' => $proposed_updates->user->name,
          'link' => route('programs.show', ['bank' => $bank, 'program' => $proposed_updates->program]),
        ]);
      }
    }

    $proposed_updates->delete();

    Program::where('bank_id', $bank->id)
      ->where('name', $program->name)
      ->where('is_published', false)
      ->delete();

    toastr()->success('', 'Program updated successfully');

    return back();
  }

  public function show(Bank $bank, Program $program)
  {
    $program['anchor'] = $program->getAnchor();
    if ($program->programType->name == Program::VENDOR_FINANCING) {
      if ($program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        $program['vendors'] = $program->getVendors();
      } else {
        $program['buyers'] = $program->getBuyers();
      }
    } else {
      $program['dealers'] = $program->getDealers();
    }

    $program->load(
      'discountDetails',
      'dealerDiscountRates',
      'fees',
      'programType',
      'programCode',
      'bankUserDetails',
      'bankDetails'
    );

    $mappings = ProgramVendorConfiguration::with('company', 'buyer')
      ->where('program_id', $program->id)
      ->where('status', 'active')
      ->select('id', 'program_id', 'payment_account_number', 'company_id', 'buyer_id')
      ->get();

    if (request()->wantsJson()) {
      return response()->json(['data' => $program, 'mappings' => $mappings]);
    }

    return view('content.bank.programs.show', ['bank' => $bank, 'program' => $program, 'mappings' => $mappings]);
  }

  public function manageVendors(Bank $bank, Program $program)
  {
    // Check if user has permissions and there are no pending changes on the program
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Add/Edit Program & Mapping')
    ) {
      toastr()->error('', 'You do not have the permission to perform this action');
      return redirect()->route('programs.index', ['bank' => $bank]);
    }

    if ($program->proposedUpdate) {
      toastr()->error('', 'Program has pending changes');
      return redirect()->route('programs.index', ['bank' => $bank]);
    }

    if ($program->programType->name == Program::VENDOR_FINANCING) {
      if ($program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        $type = 'vendors';
      } else {
        $type = 'buyers';
      }
    } else {
      $type = 'dealers';
    }

    $manage = true;

    return view('content.bank.programs.manage-vendors', [
      'type' => $type,
      'bank' => $bank,
      'program' => $program,
      'manage' => $manage,
    ]);
  }

  public function vendorsData(Request $request, Bank $bank, Program $program)
  {
    $per_page = $request->query('per_page');
    $company_name = $request->query('name');

    $company_ids = [];

    if ($program->programType->name == Program::VENDOR_FINANCING) {
      if ($program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        $company_ids = $program->getVendors()->pluck('id');
      } else {
        $company_ids = $program->getBuyers()->pluck('id');
      }
    } else {
      $company_ids = $program->getDealers()->pluck('id');
    }

    $data = Company::where('bank_id', $bank->id)
      ->whereIn('id', $company_ids)
      ->when($company_name && $company_name != '', function ($query) use ($company_name) {
        $query->where('name', 'LIKE', '%' . $company_name . '%');
      })
      ->orderBy('name', 'ASC')
      ->paginate($per_page);

    foreach ($data as $company) {
      if ($program->programType->name == Program::VENDOR_FINANCING) {
        if ($program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
          $vendor_configuration = new OdAccountsResource(
            ProgramVendorConfiguration::with('user')
              ->where('company_id', $company->id)
              ->where('program_id', $program->id)
              ->select(
                'id',
                'program_id',
                'company_id',
                'sanctioned_limit',
                'drawing_power',
                'utilized_amount',
                'pipeline_amount',
                'payment_account_number',
                'limit_approved_date',
                'limit_expiry_date',
                'limit_review_date',
                'request_auto_finance',
                'auto_approve_finance',
                'eligibility',
                'status',
                'created_by',
                'is_approved',
                'deleted_at',
                'deleted_by',
                'rejected_by',
                'rejection_reason',
                'is_blocked'
              )
              ->first()
          );
          $company['vendor_configuration'] = $vendor_configuration;
          $sanctioned_limit = $vendor_configuration->sanctioned_limit;
          $pipeline_amount = $vendor_configuration->pipeline_amount;
          $utilized_amount = $vendor_configuration->utilized_amount;
          $company['vendor_discount_details'] = ProgramVendorDiscount::where('company_id', $company->id)
            ->where('program_id', $program->id)
            ->select(
              'id',
              'benchmark_title',
              'benchmark_rate',
              'business_strategy_spread',
              'credit_spread',
              'total_spread',
              'total_roi',
              'anchor_discount_bearing',
              'vendor_discount_bearing',
              'penal_discount_on_principle',
              'grace_period',
              'grace_period_discount',
              'maturity_handling_on_holidays',
              'from_day',
              'to_day'
            )
            ->get();
          $company['vendor_bank_details'] = ProgramVendorBankDetail::where('company_id', $company->id)
            ->where('program_id', $program->id)
            ->select('id', 'name_as_per_bank', 'account_number', 'bank_name', 'branch', 'swift_code', 'account_type')
            ->get();
          $company['vendor_fee_details'] = ProgramVendorFee::where('company_id', $company->id)
            ->where('program_id', $program->id)
            ->select(
              'id',
              'fee_name',
              'type',
              'value',
              'anchor_bearing_discount',
              'vendor_bearing_discount',
              'taxes',
              'dealer_bearing',
              'per_amount',
              'charge_type',
              'account_number',
              'account_name'
            )
            ->get();
          $company['utilized_amount'] = $vendor_configuration->utilized_amount;
          $company['pipeline_amount'] = $vendor_configuration->pipeline_amount;
        } else {
          $vendor_configuration = new OdAccountsResource(
            ProgramVendorConfiguration::with('user')
              ->where('buyer_id', $company->id)
              ->where('program_id', $program->id)
              ->select(
                'id',
                'program_id',
                'company_id',
                'buyer_id',
                'sanctioned_limit',
                'drawing_power',
                'utilized_amount',
                'pipeline_amount',
                'payment_account_number',
                'limit_approved_date',
                'limit_expiry_date',
                'limit_review_date',
                'request_auto_finance',
                'auto_approve_finance',
                'eligibility',
                'status',
                'created_by',
                'is_approved',
                'deleted_at',
                'deleted_by',
                'rejected_by',
                'rejection_reason',
                'is_blocked'
              )
              ->first()
          );
          $company['vendor_configuration'] = $vendor_configuration;
          $sanctioned_limit = $vendor_configuration->sanctioned_limit;
          $pipeline_amount = $vendor_configuration->pipeline_amount;
          $utilized_amount = $vendor_configuration->utilized_amount;
          $company['vendor_discount_details'] = ProgramVendorDiscount::where('buyer_id', $company->id)
            ->where('program_id', $program->id)
            ->select(
              'id',
              'benchmark_title',
              'benchmark_rate',
              'business_strategy_spread',
              'credit_spread',
              'total_spread',
              'total_roi',
              'anchor_discount_bearing',
              'vendor_discount_bearing',
              'penal_discount_on_principle',
              'grace_period',
              'grace_period_discount',
              'maturity_handling_on_holidays',
              'from_day',
              'to_day'
            )
            ->get();
          $company['vendor_bank_details'] = ProgramVendorBankDetail::where('buyer_id', $company->id)
            ->where('program_id', $program->id)
            ->select('id', 'name_as_per_bank', 'account_number', 'bank_name', 'branch', 'swift_code', 'account_type')
            ->get();
          $company['vendor_fee_details'] = ProgramVendorFee::where('buyer_id', $company->id)
            ->where('program_id', $program->id)
            ->select(
              'id',
              'fee_name',
              'type',
              'value',
              'anchor_bearing_discount',
              'vendor_bearing_discount',
              'taxes',
              'dealer_bearing',
              'per_amount',
              'charge_type',
              'account_number',
              'account_name'
            )
            ->get();
          $company['utilized_amount'] = $vendor_configuration->utilized_amount;
          $company['pipeline_amount'] = $vendor_configuration->pipeline_amount;
        }
      } else {
        $vendor_configuration = new OdAccountsResource(
          ProgramVendorConfiguration::with('user')
            ->where('company_id', $company->id)
            ->where('program_id', $program->id)
            ->select(
              'id',
              'program_id',
              'company_id',
              'sanctioned_limit',
              'drawing_power',
              'utilized_amount',
              'pipeline_amount',
              'payment_account_number',
              'limit_approved_date',
              'limit_expiry_date',
              'limit_review_date',
              'request_auto_finance',
              'auto_approve_finance',
              'eligibility',
              'status',
              'created_by',
              'is_approved',
              'deleted_at',
              'deleted_by',
              'rejected_by',
              'rejection_reason',
              'is_blocked'
            )
            ->first()
        );
        $company['vendor_configuration'] = $vendor_configuration;
        $sanctioned_limit = $vendor_configuration->sanctioned_limit;
        $pipeline_amount = $vendor_configuration->pipeline_amount;
        $utilized_amount = $vendor_configuration->utilized_amount;
        $company['vendor_discount_details'] = ProgramVendorDiscount::where('company_id', $company->id)
          ->where('program_id', $program->id)
          ->select(
            'id',
            'benchmark_title',
            'benchmark_rate',
            'business_strategy_spread',
            'credit_spread',
            'total_spread',
            'total_roi',
            'anchor_discount_bearing',
            'vendor_discount_bearing',
            'penal_discount_on_principle',
            'grace_period',
            'grace_period_discount',
            'maturity_handling_on_holidays',
            'from_day',
            'to_day'
          )
          ->get();
        $company['vendor_bank_details'] = ProgramVendorBankDetail::where('company_id', $company->id)
          ->where('program_id', $program->id)
          ->select('id', 'name_as_per_bank', 'account_number', 'bank_name', 'branch', 'swift_code', 'account_type')
          ->get();
        $company['vendor_fee_details'] = ProgramVendorFee::where('company_id', $company->id)
          ->where('program_id', $program->id)
          ->select(
            'id',
            'fee_name',
            'type',
            'value',
            'anchor_bearing_discount',
            'vendor_bearing_discount',
            'taxes',
            'dealer_bearing',
            'per_amount',
            'charge_type',
            'account_number',
            'account_name'
          )
          ->get();
        $company['utilized_amount'] = $vendor_configuration->utilized_amount;
        $company['pipeline_amount'] = $vendor_configuration->pipeline_amount;
      }

      // $company['utilized_percentage_ratio'] = $company->utilizedPercentage($program);
      $company['utilized_percentage_ratio'] = round((($pipeline_amount + $utilized_amount) / $sanctioned_limit) * 100);
      $company['changes'] = ProgramMappingChange::where('company_id', $company->id)
        ->where('program_id', $program->id)
        ->first();
    }

    return response()->json($data);
  }

  public function vendorsDataExport(Request $request, Bank $bank, Program $program)
  {
    $company_name = $request->query('name');

    if ($program->programType->name == Program::VENDOR_FINANCING) {
      if ($program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        $report_data = ProgramVendorConfiguration::where('program_id', $program->id)
          ->when($company_name && $company_name != '', function ($query) use ($company_name) {
            $query->whereHas('company', function ($query) use ($company_name) {
              $query->where('name', 'LIKE', '%' . $company_name . '%');
            });
          })
          ->get();

        $headers = [
          'Vendors',
          'Payment/OD Account No.',
          'Sanctioned Limit',
          'Utilized Amount',
          'Pipeline Requests',
          'Available Amount',
          'Status',
          'Created At',
        ];
      } else {
        $report_data = ProgramVendorConfiguration::where('program_id', $program->id)
          ->when($company_name && $company_name != '', function ($query) use ($company_name) {
            $query->whereHas('buyer', function ($query) use ($company_name) {
              $query->where('name', 'LIKE', '%' . $company_name . '%');
            });
          })
          ->get();

        $headers = [
          'Buyers',
          'Payment/OD Account No.',
          'Sanctioned Limit',
          'Utilized Amount',
          'Pipeline Requests',
          'Available Amount',
          'Status',
          'Created At',
        ];
      }
    } else {
      $report_data = ProgramVendorConfiguration::where('program_id', $program->id)
        ->when($company_name && $company_name != '', function ($query) use ($company_name) {
          $query->whereHas('company', function ($query) use ($company_name) {
            $query->where('name', 'LIKE', '%' . $company_name . '%');
          });
        })
        ->get();

      $headers = [
        'Dealers',
        'Payment/OD Account No.',
        'Sanctioned Limit',
        'Utilized Amount',
        'Pipeline Requests',
        'Available Amount',
        'Status',
        'Created At',
      ];
    }

    $data = [];

    $date = now()->format('Y-m-d');

    foreach ($report_data as $key => $report) {
      if ($program->programType->name === Program::DEALER_FINANCING) {
        $data[$key]['Dealers'] = $report->company->name;
      } else {
        if ($program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE) {
          $data[$key]['Vendors'] = $report->company->name;
        } else {
          $data[$key]['Buyers'] = $report->company->name;
        }
      }
      $data[$key]['Payment/OD Account No.'] = $report->payment_account_number;
      $data[$key]['Sanctioned Limit'] = number_format($report->sanctioned_limit, 2);
      $data[$key]['Utilized Amount'] = number_format($report->utilized_amount, 2);
      $data[$key]['Pipeline Requests'] = number_format($report->pipeline_amount, 2);
      $data[$key]['Available Amount'] = number_format(
        $report->sanctioned_limit - $report->utilized_amount - $report->pipeline_amount,
        2
      );
      $data[$key]['Status'] = Str::title($report->status);
      $data[$key]['Created At'] = $report->created_at->format('d/m/Y');
    }

    Excel::store(new Report($headers, $data), 'Mappings' . $date . '.csv', 'exports');

    return Storage::disk('exports')->download('Mappings' . $date . '.csv');
  }

  public function updateMappingStatus(Bank $bank, Program $program, Company $company)
  {
    // Fetch other bank users who can approve progra changes
    $bank_users = BankUser::where('bank_id', $bank->id)
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where('name', 'Program Changes Checker');
          });
        });
      })
      ->where('user_id', '!=', auth()->id())
      ->where('active', true)
      ->get();

    if ($program->programType->name == Program::DEALER_FINANCING) {
      $mapping = ProgramVendorConfiguration::where('company_id', $company->id)
        ->where('program_id', $program->id)
        ->first();
    } else {
      if ($program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        $mapping = ProgramVendorConfiguration::where('company_id', $company->id)
          ->where('program_id', $program->id)
          ->first();
      } else {
        $mapping = ProgramVendorConfiguration::where('buyer_id', $company->id)
          ->where('program_id', $program->id)
          ->first();
      }
    }

    if ($mapping->status == 'inactive') {
      if (Carbon::parse($mapping->limit_expiry_date)->lessThan(now())) {
        if (request()->wantsJson()) {
          return response()->json(['message' => 'Cannot activate. Program Mapping Limit is expired.'], 401);
        }

        toastr()->error('', 'Cannot Active. Program Mapping Limit is expired');

        return back();
      }
    }

    $mapping->status = $mapping->status == 'active' ? 'inactive' : 'active';

    if ($bank_users->count() > 0) {
      ProgramMappingChange::where('program_id', $program->id)
        ->where('company_id', $company->id)
        ->delete();
      $update_data['vendor_configuration'] = ['status' => $mapping->status];
      ProgramMappingChange::create([
        'program_id' => $program->id,
        'company_id' => $company->id,
        'user_id' => auth()->id(),
        'changes' => $update_data,
      ]);
    } else {
      $mapping->save();
    }

    return response()->json(['message' => 'Mapping updated successfully']);
  }

  public function bulkUpdateMappingStatus(Request $request, Bank $bank, Program $program)
  {
    $bank_users = BankUser::where('bank_id', $bank->id)
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where('name', 'Program Changes Checker');
          });
        });
      })
      ->where('user_id', '!=', auth()->id())
      ->where('active', true)
      ->get();

    if (
      $program->programType->name == Program::VENDOR_FINANCING &&
      ($program->programCode->name == Program::FACTORING_WITH_RECOURSE ||
        $program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE)
    ) {
      foreach ($request->companies as $company) {
        $mapping = ProgramVendorConfiguration::where('buyer_id', $company)
          ->where('program_id', $program->id)
          ->first();
        if ($request->status == 'active') {
          if (Carbon::parse($mapping->limit_expiry_date)->greaterThanOrEqualTo(now())) {
            $mapping->status = $request->status;
            if ($bank_users->count() > 0) {
              ProgramMappingChange::where('program_id', $mapping->program->id)
                ->where('company_id', $company->id)
                ->delete();
              $update_data['Vendor Configuration'] = ['status' => $mapping->status];
              ProgramMappingChange::create([
                'program_id' => $mapping->program_id,
                'company_id' => $mapping->company_id,
                'user_id' => auth()->id(),
                'changes' => $update_data,
              ]);
            } else {
              $mapping->save();
            }
          }
        } else {
          $mapping->status = $request->status;
          if ($bank_users->count() > 0) {
            ProgramMappingChange::where('program_id', $mapping->program->id)
              ->where('company_id', $company->id)
              ->delete();
            $update_data['Vendor Configuration'] = ['status' => $mapping->status];
            ProgramMappingChange::create([
              'program_id' => $mapping->program_id,
              'company_id' => $mapping->company_id,
              'user_id' => auth()->id(),
              'changes' => $update_data,
            ]);
          } else {
            $mapping->save();
          }
        }
      }
    } else {
      foreach ($request->companies as $company) {
        $mapping = ProgramVendorConfiguration::where('company_id', $company)
          ->where('program_id', $program->id)
          ->first();
        if ($request->status == 'active') {
          if (Carbon::parse($mapping->limit_expiry_date)->greaterThanOrEqualTo(now())) {
            $mapping->status = $request->status;
            if ($bank_users->count() > 0) {
              ProgramMappingChange::where('program_id', $mapping->program->id)
                ->where('company_id', $company->id)
                ->delete();
              $update_data['Vendor Configuration'] = ['status' => $mapping->status];
              ProgramMappingChange::create([
                'program_id' => $mapping->program_id,
                'company_id' => $mapping->company_id,
                'user_id' => auth()->id(),
                'changes' => $update_data,
              ]);
            } else {
              $mapping->save();
            }
          }
        } else {
          $mapping->status = $request->status;
          if ($bank_users->count() > 0) {
            ProgramMappingChange::where('program_id', $mapping->program->id)
              ->where('company_id', $company->id)
              ->delete();
            $update_data['Vendor Configuration'] = ['status' => $mapping->status];
            ProgramMappingChange::create([
              'program_id' => $mapping->program_id,
              'company_id' => $mapping->company_id,
              'user_id' => auth()->id(),
              'changes' => $update_data,
            ]);
          } else {
            $mapping->save();
          }
        }
      }
    }

    return response()->json(['message' => 'Mapping updated successfully']);
  }

  public function updateApprovalMappingStatus(Request $request, Bank $bank, Program $program, Company $company)
  {
    $users = [];
    if ($program->programType->name == Program::VENDOR_FINANCING) {
      if (
        $program->programCode->name == Program::FACTORING_WITH_RECOURSE ||
        $program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE
      ) {
        $mapping = ProgramVendorConfiguration::where('buyer_id', $company->id)
          ->where('program_id', $program->id)
          ->first();
      } else {
        $mapping = ProgramVendorConfiguration::where('company_id', $company->id)
          ->where('program_id', $program->id)
          ->first();
      }
    } else {
      $mapping = ProgramVendorConfiguration::where('company_id', $company->id)
        ->where('program_id', $program->id)
        ->first();
    }

    if ($request->status == 'approve') {
      $mapping->is_approved = true;
      $mapping->status = 'active';
      $mapping->save();
      if ($program->programType->name == Program::VENDOR_FINANCING) {
        if ($program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
          $users = $mapping->company->users->where('last_login', null);
          $anchor_users = $mapping->program->anchor->users->where('last_login', null);
          $company->notify(new NewProgramMapping($program, 'vendor'));
        } else {
          $company->notify(new NewProgramMapping($program, 'buyer'));
          $users = $mapping->buyer->users->where('last_login', null);
          $anchor_users = $mapping->program->anchor->users->where('last_login', null);
        }
      } else {
        $company->notify(new NewProgramMapping($program, 'dealer'));
        $users = $mapping->company->users->where('last_login', null);
        $anchor_users = $mapping->program->anchor->users->where('last_login', null);
      }

      foreach ($users as $user) {
        $link['Dashboard'] = URL::temporarySignedRoute('auth.reset-password', now()->addHour(), [
          'id' => $user->id,
        ]);

        SendMail::dispatchAfterResponse($user->email, 'MappedToCompany', [
          'type' => 'Company',
          'data' => ['company' => $company->name, 'name' => $user->name, 'links' => $link],
        ]);
      }

      foreach ($anchor_users as $user) {
        $link['Dashboard'] = URL::temporarySignedRoute('auth.reset-password', now()->addHour(), [
          'id' => $user->id,
        ]);
        SendMail::dispatchAfterResponse($user->email, 'MappedToCompany', [
          'type' => 'Company',
          'data' => ['company' => $company->name, 'name' => $user->name, 'links' => $link],
        ]);
      }
    } else {
      $mapping->rejected_by = auth()->id();
      $mapping->rejection_reason = $request->rejection_reason;
      $mapping->save();
    }

    return response()->json(['message' => 'Mapping updated successfully']);
  }

  public function bulkApproveMappingStatus(Request $request, Bank $bank, Program $program)
  {
    if ($program->programType->name == Program::VENDOR_FINANCING) {
      if (
        $program->programCode->name == Program::FACTORING_WITH_RECOURSE ||
        $program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE
      ) {
        foreach ($request->companies as $company) {
          $mapping = ProgramVendorConfiguration::where('buyer_id', $company)
            ->where('program_id', $program->id)
            ->first();
          $mapping->is_approved = !$mapping->is_approved;
          $mapping->status = $mapping->status == 'active' ? 'inactive' : 'active';
          $mapping->save();
        }
      } else {
        foreach ($request->companies as $company) {
          $mapping = ProgramVendorConfiguration::where('company_id', $company)
            ->where('program_id', $program->id)
            ->first();
          $mapping->is_approved = !$request->is_approved;
          $mapping->status = $mapping->status == 'active' ? 'inactive' : 'active';
          $mapping->save();
        }
      }
    } else {
      foreach ($request->companies as $company) {
        $mapping = ProgramVendorConfiguration::where('company_id', $company)
          ->where('program_id', $program->id)
          ->first();
        $mapping->is_approved = !$request->is_approved;
        $mapping->status = $mapping->status == 'active' ? 'inactive' : 'active';
        $mapping->save();
      }
    }

    return response()->json(['message' => 'Mapping updated successfully']);
  }

  public function updateStatus(Request $request, Bank $bank, Program $program, string $status)
  {
    ProgramChange::where('program_id', $program->id)->delete();

    $update_data = [];

    $bank_users = BankUser::where('bank_id', $bank->id)
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where('name', 'Program Changes Checker');
          });
        });
      })
      ->where('user_id', '!=', auth()->id())
      ->where('active', true)
      ->get();

    $program->account_status = $status;

    if ($status == 'active') {
      if (Carbon::parse($program->limit_expiry_date)->lessThan(now())) {
        if ($request->wantsJson()) {
          return response()->json(['message' => 'Cannot active. Program Limit is expired.'], 401);
        }

        toastr()->error('', 'Cannot Active. Program Limit is expired');

        return back();
      }
    }

    if ($bank_users->count() <= 0) {
      $program->save();
    } else {
      $update_data['Program Details'] = $program->getDirty();
      ProgramChange::create([
        'program_id' => $program->id,
        'changes' => $update_data,
        'user_id' => auth()->id(),
      ]);
    }

    if ($bank_users->count() > 0) {
      foreach ($bank_users as $bank_user) {
        SendMail::dispatchAfterResponse($bank_user->user->email, 'ProgramChanged', [
          'program' => $program->id,
          'url' => route('programs.show', ['bank' => $bank, 'program' => $program]),
          'name' => auth()->user()->name,
          'type' => '',
        ]);
        $bank_user->user->notify(new ProgramUpdation($program));
      }
    }

    if ($request->wantsJson()) {
      return response()->json(['message' => 'Program changes were successfully processed']);
    }

    return back();
  }

  public function bulkUpdateStatus(Request $request, Bank $bank)
  {
    // Check if user has permissions and there are no pending changes on the program
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Program Changes Checker')
    ) {
      if ($request->wantsJson()) {
        return response()->json(['message' => 'You do not have the permission to perform this action'], 403);
      }
      toastr()->error('', 'You do not have the permission to perform this action');
      return redirect()->route('programs.index', ['bank' => $bank]);
    }

    $request->validate([
      'programs' => ['required'],
      'status' => ['required'],
    ]);

    foreach ($request->programs as $program) {
      $program = Program::find($program);

      ProgramChange::where('program_id', $program->id)->delete();

      $bank_users = BankUser::where('bank_id', $bank->id)
        ->whereHas('user', function ($query) {
          $query->whereHas('roles', function ($query) {
            $query->whereHas('permissions', function ($query) {
              $query
                ->where('name', 'Add/Edit Program & Mapping')
                ->orWhere('name', 'Activate/Deactivate Program & Mapping');
            });
          });
        })
        ->where('user_id', '!=', auth()->id())
        ->where('active', true)
        ->get();

      if ($request->status == 'active') {
        $program->status = 'approved';
        $program->account_status = 'active';
        $program->save();

        // Update anchor's invoice setting to start with maker checker required if not mapped to any other programs
        $other_programs = Program::whereHas('anchor', function ($q) use ($program) {
          $q->where('companies.id', $program->anchor->id);
        })
          ->where('id', '!=', $program->id)
          ->count();

        if ($other_programs == 0) {
          $program->anchor->invoiceSetting->update([
            'maker_checker_creating_updating' => true,
          ]);
        }

        activity($bank->id)
          ->causedBy(auth()->user())
          ->performedOn($program)
          ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
          ->log('approved program');

        $users = $program->anchor->users->where('last_login', null);

        foreach ($users as $user) {
          $link['Anchor Dashboard'] = URL::temporarySignedRoute('auth.reset-password', now()->addHour(), [
            'id' => $user->id,
          ]);

          SendMail::dispatchAfterResponse($user->email, 'MappedToCompany', [
            'type' => 'Company',
            'data' => ['company' => $program->anchor->name, 'name' => $user->name, 'links' => $link],
          ]);
        }
      } else {
        $program->status = 'inactive';
        $program->account_status = 'suspended';
        $program->save();

        activity($bank->id)
          ->causedBy(auth()->user())
          ->performedOn($program)
          ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
          ->log('rejected program');
      }

      DB::table('programs')
        ->where('id', '!=', $program->id)
        ->where('name', $program->name)
        ->where('is_published', false)
        ->delete();
    }

    return response()->json(['message' => 'Programs updated successfully']);
  }

  public function deleteMapping(Bank $bank, Program $program, Company $company)
  {
    if ($program->programType->name == Program::DEALER_FINANCING) {
      $vendor_configuration = ProgramVendorConfiguration::where('program_id', $program->id)
        ->where('company_id', $company->id)
        ->first();
    } else {
      if ($program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        $vendor_configuration = ProgramVendorConfiguration::where('program_id', $program->id)
          ->where('company_id', $company->id)
          ->first();
      } else {
        $vendor_configuration = ProgramVendorConfiguration::where('program_id', $program->id)
          ->where('buyer_id', $company->id)
          ->first();
      }
    }

    // Check if program has ongoing transactions
    $utilized_amount = round($vendor_configuration->utilized_amount);
    $pipeline_amount = round($vendor_configuration->pipeline_amount);
    if ($utilized_amount > 0 || $pipeline_amount > 0) {
      return response()->json(['message' => 'Program has ongoing transactions'], 400);
    }

    if ($vendor_configuration && !$vendor_configuration->deleted_at) {
      $vendor_configuration->update([
        'deleted_at' => now(),
        'deleted_by' => auth()->id(),
      ]);

      return response()->json(['message' => 'Mapping Deletion sent for approval'], 200);
    }

    if ($program->programType->name == Program::DEALER_FINANCING) {
      ProgramVendorConfiguration::where('program_id', $program->id)
        ->where('company_id', $company->id)
        ->delete();
      ProgramVendorDiscount::where('program_id', $program->id)
        ->where('company_id', $company->id)
        ->delete();
      ProgramVendorBankDetail::where('program_id', $program->id)
        ->where('company_id', $company->id)
        ->delete();
      ProgramVendorFee::where('program_id', $program->id)
        ->where('company_id', $company->id)
        ->delete();
    } else {
      if ($program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        ProgramVendorConfiguration::where('program_id', $program->id)
          ->where('company_id', $company->id)
          ->delete();
        ProgramVendorDiscount::where('program_id', $program->id)
          ->where('company_id', $company->id)
          ->delete();
        ProgramVendorBankDetail::where('program_id', $program->id)
          ->where('company_id', $company->id)
          ->delete();
        ProgramVendorFee::where('program_id', $program->id)
          ->where('company_id', $company->id)
          ->delete();
      } else {
        ProgramVendorConfiguration::where('program_id', $program->id)
          ->where('buyer_id', $company->id)
          ->delete();
        ProgramVendorDiscount::where('program_id', $program->id)
          ->where('buyer_id', $company->id)
          ->delete();
        ProgramVendorBankDetail::where('program_id', $program->id)
          ->where('buyer_id', $company->id)
          ->delete();
        ProgramVendorFee::where('program_id', $program->id)
          ->where('buyer_id', $company->id)
          ->delete();
      }
    }

    ProgramCompanyRole::where('program_id', $program->id)
      ->where('company_id', $company->id)
      ->delete();

    if (request()->wantsJson()) {
      return response()->json(['message' => 'Mapping Deleted Successfully']);
    }

    toastr()->success('', 'Mapping deleted successfully');

    return back();
  }

  public function cancelMappingDeletion(Bank $bank, Program $program, Company $company)
  {
    if ($program->programType->name == Program::DEALER_FINANCING) {
      $vendor_configuration = ProgramVendorConfiguration::where('program_id', $program->id)
        ->where('company_id', $company->id)
        ->first();
    } else {
      if ($program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        $vendor_configuration = ProgramVendorConfiguration::where('program_id', $program->id)
          ->where('company_id', $company->id)
          ->first();
      } else {
        $vendor_configuration = ProgramVendorConfiguration::where('program_id', $program->id)
          ->where('buyer_id', $company->id)
          ->first();
      }
    }

    if ($vendor_configuration) {
      $vendor_configuration->update([
        'deleted_at' => null,
        'deleted_by' => null,
      ]);

      return response()->json(['message' => 'Mapping Deletion cancelled'], 200);
    }

    return response()->json(['message' => 'Mapping not found'], 500);
  }

  public function showMapVendor(Bank $bank, Program $program)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Add/Edit Program & Mapping')
    ) {
      toastr()->error('', 'You do not have the permission to perform this action');
      return back();
    }

    $anchor = $program->anchor;
    $vendors = $program->getVendors();
    $dealers = $bank->getDealers();

    $companies = $bank->companies
      ->where('approval_status', 'approved')
      ->where('status', 'active')
      ->sortDesc();
    $companies = $companies->filter(function ($company) use ($anchor, $vendors, $dealers) {
      $vendors_ids = collect($vendors)->pluck('id');
      $dealer_ids = collect($dealers)->pluck('id');

      return $company->id != $anchor->id &&
        !collect($vendors_ids)->contains($company->id) &&
        !collect($dealer_ids)->contains($company->id);
    });

    $program->load('discountDetails', 'fees');

    $benchmark_rates = [];

    $base_rates = BankBaseRate::active()
      ->where('bank_id', $bank->id)
      ->get();

    if ($base_rates->count() <= 0) {
      $base_rates = BaseRate::active()->get();

      foreach ($base_rates as $rate) {
        $benchmark_rates[$rate->rate_code] = $rate->rate;
      }
    } else {
      foreach ($base_rates as $rate) {
        $benchmark_rates[$rate->name] = $rate->rate;
      }
    }

    $taxes = [];

    $bank_tax_rates = BankTaxRate::active()
      ->where('bank_id', $bank->id)
      ->get();

    if ($bank_tax_rates->count() <= 0) {
      $bank_tax_rates = Tax::active()->get();

      foreach ($bank_tax_rates as $rate) {
        $taxes[$rate->name] = $rate->percentage;
      }
    } else {
      foreach ($bank_tax_rates as $rate) {
        $taxes[$rate->tax_name] = $rate->value;
      }
    }

    $banks = BankMasterList::all();

    $roles = PermissionData::where('RoleTypeName', 'Vendor')
      ->where(function ($query) use ($bank) {
        $query->where('bank_id', $bank->id)->orWhere('bank_id', null);
      })
      ->select('id', 'RoleName')
      ->get();

    $countries = json_decode(file_get_contents(public_path('assets/country-codes.json')));

    // Get max amount that can be assigned
    $max_limit = $program->max_limit_per_account;
    $sanctioned_limit = $program->max_limit_per_account;
    $program_limit = $program->program_limit;
    $assigned_amount = ProgramVendorConfiguration::where('program_id', $program->id)->sum('sanctioned_limit');
    $remainder = $program_limit - $assigned_amount;
    if ($remainder < $program->max_limit_per_account) {
      $max_limit = $remainder;
      $sanctioned_limit = $max_limit;
    }

    $notification_channels = ['email' => 'Email', 'sms' => 'SMS', 'email_and_sms' => 'Email and SMS'];

    $bank_payment_accounts = $bank->paymentAccounts;

    return view(
      'content.bank.programs.vendors.map',
      compact(
        'bank',
        'program',
        'companies',
        'benchmark_rates',
        'taxes',
        'banks',
        'roles',
        'countries',
        'sanctioned_limit',
        'max_limit',
        'notification_channels',
        'bank_payment_accounts'
      )
    );
  }

  public function editMapVendor(Bank $bank, Program $program, Company $company)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Add/Edit Program & Mapping')
    ) {
      toastr()->error('', 'You do not have the permission to perform this action');
      return back();
    }

    $program->load('discountDetails', 'fees');

    $benchmark_rates = [];

    $base_rates = BankBaseRate::active()
      ->where('bank_id', $bank->id)
      ->get();

    if ($base_rates->count() <= 0) {
      $base_rates = BaseRate::active()->get();

      foreach ($base_rates as $rate) {
        $benchmark_rates[$rate->rate_code] = $rate->rate;
      }
    } else {
      foreach ($base_rates as $rate) {
        $benchmark_rates[$rate->name] = $rate->rate;
      }
    }

    $taxes = [];

    $bank_tax_rates = BankTaxRate::active()
      ->where('bank_id', $bank->id)
      ->get();

    if ($bank_tax_rates->count() <= 0) {
      $bank_tax_rates = Tax::active()->get();

      foreach ($bank_tax_rates as $rate) {
        $taxes[$rate->name] = $rate->percentage;
      }
    } else {
      foreach ($bank_tax_rates as $rate) {
        $taxes[$rate->tax_name] = $rate->value;
      }
    }

    $banks = BankMasterList::all();

    $mapping = new \stdClass();

    $mapping->configuration = ProgramVendorConfiguration::where('company_id', $company->id)
      ->where('program_id', $program->id)
      ->first();
    $mapping->discounts = ProgramVendorDiscount::where('program_id', $program->id)
      ->where('company_id', $company->id)
      ->first();
    $mapping->fees = ProgramVendorFee::where('program_id', $program->id)
      ->where('company_id', $company->id)
      ->get();
    $mapping->contact_details = ProgramVendorContactDetail::where('program_id', $program->id)
      ->where('company_id', $company->id)
      ->get();
    $mapping->bank_details = ProgramVendorBankDetail::where('program_id', $program->id)
      ->where('company_id', $company->id)
      ->get();

    $roles = PermissionData::where('RoleTypeName', 'Vendor')
      ->where(function ($query) use ($bank) {
        $query->where('bank_id', $bank->id)->orWhere('bank_id', null);
      })
      ->select('id', 'RoleName')
      ->get();

    $countries = json_decode(file_get_contents(public_path('assets/country-codes.json')));

    // Get max amount that can be assigned
    $max_limit = $program->max_limit_per_account;
    $sanctioned_limit = $program->max_limit_per_account;
    $program_limit = $program->program_limit;
    $assigned_amount = ProgramVendorConfiguration::where('program_id', $program->id)->sum('sanctioned_limit');
    $remainder = $program_limit - $assigned_amount;
    $current_vendor_limit = ProgramVendorConfiguration::where('program_id', $program->id)
      ->where('company_id', $company->id)
      ->first();
    if ($remainder < $program->max_limit_per_account) {
      $max_limit = $remainder + $current_vendor_limit->sanctioned_limit;
      $sanctioned_limit = $max_limit;
    }

    $notification_channels = ['email' => 'Email', 'sms' => 'SMS', 'email_and_sms' => 'Email and SMS'];

    $bank_payment_accounts = $bank->paymentAccounts;

    return view(
      'content.bank.programs.vendors.edit',
      compact(
        'mapping',
        'banks',
        'taxes',
        'benchmark_rates',
        'program',
        'company',
        'bank',
        'roles',
        'countries',
        'sanctioned_limit',
        'max_limit',
        'notification_channels',
        'bank_payment_accounts'
      )
    );
  }

  public function mapVendor(Request $request, Bank $bank, Program $program)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Add/Edit Program & Mapping')
    ) {
      toastr()->error('', 'You do not have the permission to perform this action');
      return back();
    }

    // Validator for unique phone numbers when request has country code
    Validator::extend('unique_phone_number', function ($attribute, $value, $parameters, $validator) {
      return !User::where('phone_number', $parameters[0] . '' . substr($value, -9))->exists();
    });

    // Get Current Payment Account Numbers
    $payment_account_numbers = ProgramVendorConfiguration::whereHas('program', function ($query) use ($bank) {
      $query->where('bank_id', $bank->id);
    })->pluck('payment_account_number');

    $request->validate(
      [
        'vendor_id' => ['required'],
        'bank_names_as_per_banks' => ['required', 'array', 'min:1'],
        'bank_names_as_per_banks.*' => ['required', 'string'],
        'account_numbers' => ['required', 'array', 'min:1'],
        'account_numbers.*' => ['required', 'string'],
        'bank_names' => ['required', 'array', 'min:1'],
        'bank_names.*' => ['required', 'string'],
        'payment_account_number' => [
          'required',
          Rule::unique('program_vendor_configurations')->where(function ($query) use ($payment_account_numbers) {
            return $query->whereNotIn('payment_account_number', $payment_account_numbers);
          }),
        ],
        'vendor_user_name' => [
          'sometimes',
          'required_with:vendor_user_email,vendor_user_country_code,vendor_user_phone_number,vendor_user_role,vendor_user_receive_notifications',
        ],
        'vendor_user_email' => [
          'sometimes',
          'required_with:vendor_user_name,vendor_user_country_code,vendor_user_phone_number,vendor_user_role,vendor_user_receive_notifications',
        ],
        'vendor_user_country_code' => [
          'sometimes',
          'required_with:vendor_user_name,vendor_user_email,vendor_user_phone_number,vendor_user_role,vendor_user_receive_notifications',
        ],
        'vendor_user_phone_number' => [
          'sometimes',
          'required_with:vendor_user_name,vendor_user_email,vendor_user_country_code,vendor_user_role,vendor_user_receive_notifications',
          'unique_phone_number:' . $request->vendor_user_country_code,
        ],
        'vendor_user_role' => [
          'sometimes',
          'required_with:vendor_user_name,vendor_user_email,vendor_user_country_code,vendor_user_phone_number,vendor_user_receive_notifications',
        ],
        'vendor_user_receive_notifications' => [
          'sometimes',
          'required_with:vendor_user_name,vendor_user_email,vendor_user_country_code,vendor_user_phone_number,vendor_user_role',
        ],
      ],
      [
        'bank_names_as_per_banks.required' => 'Please enter at least one bank name as per banks.',
        'bank_names_as_per_banks.min' => 'Please select at least one bank name as per banks.',
        'bank_names_as_per_banks.*.required' => 'The Account Name field is required.',
        'account_numbers.required' => 'Please enter at least one account number.',
        'account_numbers.min' => 'Please enter at least one account number.',
        'account_numbers.*.required' => 'The account number is required.',
        'bank_names.required' => 'Please select at least one bank.',
        'bank_names.min' => 'Please select at least one bank.',
        'bank_names.*.required' => 'The bank name is required.',
        'vendor_user_name.required_with' => 'This field is required for the user details.',
        'vendor_user_email.required_with' => 'This field is required for the user details.',
        'vendor_user_country_code.required_with' => 'This field is required for the user details.',
        'vendor_user_phone_number.required_with' => 'This field is required for the user details.',
        'vendor_user_role.required_with' => 'This field is required for the user details.',
        'vendor_user_receive_notifications.required_with' => 'This field is required for the user details.',
        'vendor_user_phone_number.unique_phone_number' => 'This phone number is already in use',
      ]
    );

    try {
      DB::beginTransaction();

      $bank_users = BankUser::where('bank_id', $bank->id)
        ->whereHas('user', function ($query) {
          $query->whereHas('roles', function ($query) {
            $query->whereHas('permissions', function ($query) {
              $query->where(function ($query) {
                $query
                  ->where('name', 'Add/Edit Program & Mapping')
                  ->orWhere('name', 'Activate/Deactivate Program & Mapping');
              });
            });
          });
        })
        ->where('user_id', '!=', auth()->id())
        ->where('active', true)
        ->get();

      $vendor = Company::find($request->vendor_id);

      $vendor_role = ProgramRole::where('name', 'vendor')->first();

      ProgramCompanyRole::firstOrCreate([
        'program_id' => $program->id,
        'company_id' => $request->vendor_id,
        'role_id' => $vendor_role->id,
      ]);

      $vendor->programConfigurations()->create([
        'program_id' => $program->id,
        'payment_account_number' => $request->payment_account_number,
        'sanctioned_limit' => Str::replace(',', '', $request->sanctioned_limit),
        'limit_approved_date' => $request->limit_approved_date,
        'limit_expiry_date' =>
          $request->has('limit_expiry_date') && !empty($request->limit_expiry_date)
            ? Carbon::parse($request->limit_expiry_date)->format('Y-m-d')
            : null,
        'limit_review_date' =>
          $request->has('limit_review_date') && !empty($request->limit_review_date)
            ? Carbon::parse($request->limit_review_date)->format('Y-m-d')
            : null,
        'drawing_power' => Str::replace(',', '', $request->drawing_power),
        'request_auto_finance' => $request->request_auto_finance,
        'auto_approve_finance' => $request->auto_approve_finance,
        'eligibility' => $request->eligibility,
        'invoice_margin' => $request->invoice_margin,
        'schema_code' => $request->schema_code,
        'product_description' => $request->product_description,
        'vendor_code' => $request->vendor_code,
        'gst_number' => $request->gst_number,
        'classification' => $request->classification,
        'payment_terms' => $program->default_payment_terms,
        'tds' => $request->tds,
        'status' => $bank_users->count() > 0 ? 'inactive' : 'active',
        'created_by' => auth()->id(),
        'is_approved' => false,
      ]);

      $vendor->programDiscountDetails()->create([
        'program_id' => $program->id,
        'benchmark_title' => $request->benchmark_title ?? $program->discountDetails?->first()?->benchmark_title,
        'benchmark_rate' => $request->benchmark_rate,
        'reset_frequency' => $request->reset_frequency,
        'days_frequency_days' => $request->days_frequency_days,
        'business_strategy_spread' => $request->business_strategy_spread,
        'credit_spread' => $request->credit_spread,
        'total_spread' => $request->total_spread,
        'total_roi' => $request->total_roi,
        'anchor_discount_bearing' => $request->anchor_discount_bearing,
        'vendor_discount_bearing' => $request->vendor_discount_bearing,
        'penal_discount_on_principle' => $request->penal_discount_on_principle,
        'grace_period' => $request->grace_period,
        'grace_period_discount' => $request->grace_period_discount,
        'maturity_handling_on_holidays' => $request->maturity_handling_on_holidays,
      ]);

      $fee_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
        ->where('product_code_id', $program->program_code_id)
        ->where('product_type_id', $program->program_type_id)
        ->where('name', 'Fee Income Account')
        ->first();

      if (
        $request->has('fee_names') &&
        count($request->fee_names) > 0 &&
        $request->has('fee_types') &&
        count($request->fee_types) > 0 &&
        $request->has('fee_values') &&
        count($request->fee_values) > 0
      ) {
        foreach ($request->fee_names as $key => $value) {
          $vendor->programFeeDetails()->create([
            'program_id' => $program->id,
            'fee_name' => $value,
            'type' => array_key_exists($key, $request->fee_types) ? $request->fee_types[$key] : null,
            'value' => array_key_exists($key, $request->fee_values) ? $request->fee_values[$key] : null,
            'per_amount' =>
              array_key_exists($key, $request->fee_per_amount) && !empty($request->fee_per_amount[$key])
                ? $request->fee_per_amount[$key]
                : null,
            'anchor_bearing_discount' => array_key_exists($key, $request->fee_anchor_bearing_discount)
              ? $request->fee_anchor_bearing_discount[$key]
              : null,
            'vendor_bearing_discount' => array_key_exists($key, $request->fee_vendor_bearing_discount)
              ? $request->fee_vendor_bearing_discount[$key]
              : null,
            'taxes' => array_key_exists($key, $request->taxes) ? $request->taxes[$key] : null,
            'charge_type' =>
              $request->has('charge_types') &&
              !empty($request->charge_types) &&
              count($request->charge_types) > 0 &&
              array_key_exists($key, $request->charge_types)
                ? $request->charge_types[$key]
                : 'fixed',
            'account_number' =>
              $request->has('fee_account_numbers') &&
              !empty($request->fee_account_numbers) &&
              count($request->fee_account_numbers) > 0 &&
              array_key_exists($key, $request->fee_account_numbers)
                ? $request->fee_account_numbers[$key]
                : $fee_income_account->value,
          ]);
        }
      }

      if (
        $request->has('vendor_user_name') &&
        !empty($request->vendor_user_name) &&
        $request->has('vendor_user_email') &&
        !empty($request->vendor_user_email) &&
        $request->has('vendor_user_country_code') &&
        !empty($request->vendor_user_country_code) &&
        $request->has('vendor_user_phone_number') &&
        !empty($request->vendor_user_phone_number) &&
        $request->has('vendor_user_role') &&
        !empty($request->vendor_user_role)
      ) {
        $user = User::where('email', $request->vendor_user_email)
          ->orWhere('phone_number', $request->vendor_user_country_code . '' . $request->vendor_user_phone_number)
          ->first();
        if ($user) {
          $is_bank_user = BankUser::where('user_id', $user->id)->first();
          if (!$is_bank_user) {
            CompanyUser::create([
              'company_id' => $vendor->id,
              'user_id' => $user->id,
            ]);

            $permission_data = PermissionData::find($request->vendor_user_role);

            // Assign Role
            $role = Role::where('name', $permission_data->RoleName)
              ->where('guard_name', 'web')
              ->first();

            if ($role) {
              $user->assignRole($role);
            }

            $link['Bank Dashboard'] = config('app.url');

            SendMail::dispatchAfterResponse($user->email, 'MappedToCompany', [
              'type' => 'Company',
              'data' => ['company' => $vendor->name, 'name' => $user->name, 'links' => $link],
            ]);
          }
        } else {
          $user = User::create([
            'email' => $request->vendor_user_email,
            'name' => $request->vendor_user_name,
            'phone_number' => $request->vendor_user_country_code . '' . $request->vendor_user_phone_number,
            'password' => Hash::make('Secret!'),
            'receive_notifications' => $request->vendor_user_receive_notifications,
            'module' => 'company',
          ]);

          CompanyUser::create([
            'company_id' => $vendor->id,
            'user_id' => $user->id,
          ]);

          $permission_data = PermissionData::find($request->vendor_user_role);

          // Assign Role
          $role = Role::where('name', $permission_data->RoleName)
            ->where('guard_name', 'web')
            ->first();

          if ($role) {
            $user->assignRole($role);
          }

          // $link['Vendor Dashboard'] = URL::temporarySignedRoute('auth.reset-password', now()->addHour(), ['id' => $user->id]);

          // SendMail::dispatchAfterResponse($user->email, 'MappedToCompany', [
          //   'type' => 'Company',
          //   'data' => ['company' => $vendor->name, 'name' => $user->name, 'links' => $link],
          // ]);
        }
      }

      if (
        $request->has('bank_names_as_per_banks') &&
        count($request->bank_names_as_per_banks) > 0 &&
        $request->has('account_numbers') &&
        count($request->account_numbers) > 0 &&
        $request->has('bank_names') &&
        count($request->bank_names) > 0
      ) {
        foreach ($request->bank_names_as_per_banks as $key => $value) {
          // Check if bank account exists
          $bank_account_exists = ProgramVendorBankDetail::where('program_id', $program->id)
            ->where('bank_name', $request->bank_names[$key])
            ->where('account_number', $request->account_numbers[$key])
            ->first();
          if ($bank_account_exists) {
            toastr()->error('', 'Bank Account already esists');

            return back();
          }

          $vendor->programBankDetails()->create([
            'program_id' => $program->id,
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

      activity($bank->id)
        ->causedBy(auth()->user())
        ->performedOn($program)
        ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
        ->log('mapped ' . $vendor->name . ' as a vendor');

      DB::commit();

      if ($bank_users->count() > 0) {
        // Notify users of changes
        foreach ($bank_users as $bank_user) {
          SendMail::dispatchAfterResponse($bank_user->user->email, 'ProgramChanged', [
            'program' => $program->id,
            'url' => route('programs.show', ['bank' => $bank, 'program' => $program]),
            'name' => auth()->user()->name,
            'type' => 'vendor_financing',
          ]);
          $bank_user->user->notify(new ProgramUpdation($program));
        }

        toastr()->success('', 'Vendor mapping sent for approval.');
      } else {
        toastr()->success('', 'Vendor mapped successfully.');
      }

      return redirect()->route('programs.show', ['bank' => $bank, 'program' => $program]);
    } catch (\Throwable $th) {
      info($th);
      DB::rollback();
      toastr()->error('', 'An error occurred while mapping the company.');
      return back();
    }
  }

  public function updateMapVendor(Request $request, Bank $bank, Program $program, Company $company)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Add/Edit Program & Mapping')
    ) {
      toastr()->error('', 'You do not have the permission to perform this action');
      return back();
    }

    // Validator for unique phone numbers when request has country code
    Validator::extend('unique_phone_number', function ($attribute, $value, $parameters, $validator) {
      return !User::where('phone_number', $parameters[0] . '' . substr($value, -9))->exists();
    });

    // Get Current Payment Account Numbers
    $payment_account_numbers = ProgramVendorConfiguration::whereHas('program', function ($query) use ($bank) {
      $query->where('bank_id', $bank->id);
    })
      ->where('company_id', '!=', $company->id)
      ->pluck('payment_account_number');

    $request->validate(
      [
        'payment_account_number' => [
          'required',
          Rule::unique('program_vendor_configurations')
            ->ignore($company->id, 'company_id')
            ->where(function ($query) use ($payment_account_numbers) {
              return $query->whereNotIn('payment_account_number', $payment_account_numbers);
            }),
        ],
        'sanctioned_limit' => ['required'],
        'limit_approved_date' => ['required'],
        // 'limit_review_date' => ['required'],
        'request_auto_finance' => ['required'],
        'auto_approve_finance' => ['required'],
        'eligibility' => ['required'],
        'status' => ['required'],
        // 'benchmark_title' => ['required'],
        // 'benchmark_rate' => ['required'],
        // 'business_strategy_spread' => ['required'],
        // 'credit_spread' => ['required'],
        // 'total_roi' => ['required'],
        // 'anchor_discount_bearing' => ['required'],
        // 'vendor_discount_bearing' => ['required'],
        // 'penal_discount_on_principle' => ['required'],
        // 'grace_period' => ['required'],
        // 'grace_period_discount' => ['required'],
        'account_numbers' => ['required', 'array', 'min:1'],
        'bank_names_as_per_banks' => ['required', 'array', 'min:1'],
        'vendor_user_name' => [
          'sometimes',
          'required_with:vendor_user_email,vendor_user_country_code,vendor_user_phone_number,vendor_user_role,vendor_user_receive_notifications',
        ],
        'vendor_user_email' => [
          'sometimes',
          'required_with:vendor_user_name,vendor_user_country_code,vendor_user_phone_number,vendor_user_role,vendor_user_receive_notifications',
        ],
        'vendor_user_country_code' => [
          'sometimes',
          'required_with:vendor_user_name,vendor_user_email,vendor_user_phone_number,vendor_user_role,vendor_user_receive_notifications',
        ],
        'vendor_user_phone_number' => [
          'sometimes',
          'required_with:vendor_user_name,vendor_user_email,vendor_user_country_code,vendor_user_role,vendor_user_receive_notifications',
          'unique_phone_number:' . $request->vendor_user_country_code,
        ],
        'vendor_user_role' => [
          'sometimes',
          'required_with:vendor_user_name,vendor_user_email,vendor_user_country_code,vendor_user_phone_number,vendor_user_receive_notifications',
        ],
        'vendor_user_receive_notifications' => [
          'sometimes',
          'required_with:vendor_user_name,vendor_user_email,vendor_user_country_code,vendor_user_phone_number,vendor_user_role',
        ],
      ],
      [
        'payment_account_number.unique' => 'This Payment Account number is already in use',
        'vendor_user_name.required_with' => 'This field is required for the user details.',
        'vendor_user_email.required_with' => 'This field is required for the user details.',
        'vendor_user_country_code.required_with' => 'This field is required for the user details.',
        'vendor_user_phone_number.required_with' => 'This field is required for the user details.',
        'vendor_user_role.required_with' => 'This field is required for the user details.',
        'vendor_user_receive_notifications.required_with' => 'This field is required for the user details.',
        'vendor_user_phone_number.unique_phone_number' => 'This phone number is already in use',
      ]
    );

    $bank_users = BankUser::where('bank_id', $bank->id)
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where(function ($query) {
              $query
                ->where('name', 'Add/Edit Program & Mapping')
                ->orWhere('name', 'Activate/Deactivate Program & Mapping');
            });
          });
        });
      })
      ->where('user_id', '!=', auth()->id())
      ->where('active', true)
      ->get();

    try {
      DB::beginTransaction();
      $update_data = [];
      if ($bank_users->count() > 0) {
        ProgramMappingChange::where('program_id', $program->id)
          ->where('company_id', $company->id)
          ->delete();
        $new_mapping_change = new ProgramMappingChange();
        $new_mapping_change->program_id = $program->id;
        $new_mapping_change->company_id = $company->id;
        $new_mapping_change->user_id = auth()->id();
        $update_data['vendor_configuration'] = [
          'payment_account_number' => $request->payment_account_number,
          'sanctioned_limit' => Str::replace(',', '', $request->sanctioned_limit),
          'limit_approved_date' => $request->limit_approved_date,
          'limit_expiry_date' =>
            $request->has('limit_expiry_date') && !empty($request->limit_expiry_date)
              ? Carbon::parse($request->limit_expiry_date)->format('Y-m-d')
              : null,
          'limit_review_date' =>
            $request->has('limit_review_date') && !empty($request->limit_review_date)
              ? Carbon::parse($request->limit_review_date)->format('Y-m-d')
              : null,
          'drawing_power' => Str::replace(',', '', $request->drawing_power),
          'request_auto_finance' => $request->request_auto_finance,
          'auto_approve_finance' => $request->auto_approve_finance,
          'eligibility' => $request->eligibility,
          'invoice_margin' => $request->invoice_margin,
          'schema_code' => $request->schema_code,
          'product_description' => $request->product_description,
          'vendor_code' => $request->vendor_code,
          'gst_number' => $request->gst_number,
          'classification' => $request->classification,
          'tds' => $request->tds,
          'status' => $request->status,
        ];

        $update_data['vendor_discount_details'] = [
          'benchmark_title' => $request->benchmark_title,
          'benchmark_rate' => $request->benchmark_rate ?? 0,
          'reset_frequency' => $request->reset_frequency,
          'days_frequency_days' => $request->days_frequency_days ?? 0,
          'business_strategy_spread' => $request->business_strategy_spread ?? 0,
          'credit_spread' => $request->credit_spread ?? 0,
          'total_spread' => $request->total_spread ?? 0,
          'total_roi' => $request->total_roi ?? 0,
          'anchor_discount_bearing' => $request->anchor_discount_bearing ?? 0,
          'vendor_discount_bearing' => $request->vendor_discount_bearing ?? 100,
          'penal_discount_on_principle' => $request->penal_discount_on_principle ?? 0,
          'grace_period' => $request->grace_period ?? 0,
          'grace_period_discount' => $request->grace_period_discount ?? 0,
          'maturity_handling_on_holidays' => $request->maturity_handling_on_holidays ?? 'No Effect',
        ];

        $fee_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
          ->where('product_code_id', $program->program_code_id)
          ->where('product_type_id', $program->program_type_id)
          ->where('name', 'Fee Income Account')
          ->first();

        if (
          $request->has('fee_names') &&
          count($request->fee_names) > 0 &&
          $request->has('fee_types') &&
          count($request->fee_types) > 0 &&
          $request->has('fee_values') &&
          count($request->fee_values) > 0
        ) {
          foreach ($request->fee_names as $key => $value) {
            if (!empty($value) && !empty($request->fee_values[$key]) && !empty($request->fee_types[$key])) {
              $update_data['vendor_fee_details'][$key] = [
                'fee_name' => $value,
                'type' => array_key_exists($key, $request->fee_types) ? $request->fee_types[$key] : null,
                'value' => array_key_exists($key, $request->fee_values) ? $request->fee_values[$key] : null,
                'per_amount' =>
                  array_key_exists($key, $request->fee_per_amount) && !empty($request->fee_per_amount[$key])
                    ? $request->fee_per_amount[$key]
                    : null,
                'anchor_bearing_discount' => array_key_exists($key, $request->fee_anchor_bearing_discount)
                  ? $request->fee_anchor_bearing_discount[$key]
                  : null,
                'vendor_bearing_discount' => array_key_exists($key, $request->fee_vendor_bearing_discount)
                  ? $request->fee_vendor_bearing_discount[$key]
                  : null,
                'taxes' => array_key_exists($key, $request->taxes) ? $request->taxes[$key] : null,
                'charge_type' =>
                  $request->has('charge_types') &&
                  !empty($request->charge_types) &&
                  count($request->charge_types) > 0 &&
                  array_key_exists($key, $request->charge_types)
                    ? $request->charge_types[$key]
                    : 'fixed',
                'account_number' =>
                  $request->has('fee_account_numbers') &&
                  !empty($request->fee_account_numbers) &&
                  count($request->fee_account_numbers) > 0 &&
                  array_key_exists($key, $request->fee_account_numbers)
                    ? $request->fee_account_numbers[$key]
                    : $fee_income_account->value,
              ];
            }
          }
        }

        if (
          $request->has('bank_names_as_per_banks') &&
          count($request->bank_names_as_per_banks) > 0 &&
          $request->has('account_numbers') &&
          count($request->account_numbers) > 0 &&
          $request->has('bank_names') &&
          count($request->bank_names) > 0
        ) {
          foreach ($request->bank_names_as_per_banks as $key => $value) {
            if (
              !empty($value) &&
              !empty($request->account_numbers[$key]) &&
              !empty($request->bank_names[$key]) &&
              !empty($request->bank_names_as_per_banks[$key])
            ) {
              $update_data['vendor_bank_details'][$key] = [
                'name_as_per_bank' => $value,
                'account_number' => array_key_exists($key, $request->account_numbers)
                  ? $request->account_numbers[$key]
                  : null,
                'bank_name' => array_key_exists($key, $request->bank_names) ? $request->bank_names[$key] : null,
                'branch' => array_key_exists($key, $request->branches) ? $request->branches[$key] : null,
                'swift_code' => array_key_exists($key, $request->swift_codes) ? $request->swift_codes[$key] : null,
                'account_type' => array_key_exists($key, $request->account_types)
                  ? $request->account_types[$key]
                  : null,
              ];
            }
          }
        }

        $new_mapping_change->changes = $update_data;
        $new_mapping_change->save();

        // Remove rejected indicator from configuration
        $vendor_config = ProgramVendorConfiguration::where('company_id', $company->id)
          ->where('program_id', $program->id)
          ->first();
        if ($vendor_config && $vendor_config->rejected_by && $vendor_config->rejection_reason) {
          $vendor_config->update([
            'rejected_by' => null,
            'rejection_reason' => null,
          ]);
        }

        foreach ($bank_users as $bank_user) {
          SendMail::dispatch($bank_user->user->email, 'ProgramMappingChanged', [
            'vendor_configuration_id' => $vendor_config->id,
            'user_name' => auth()->user()->name,
          ])->afterCommit();
        }

        toastr()->success('', 'Mapping Changes sent for approval');
      } else {
        $company
          ->programConfigurations()
          ->where('program_id', $program->id)
          ->delete();
        $company->programConfigurations()->create([
          'program_id' => $program->id,
          'payment_account_number' => $request->payment_account_number,
          'sanctioned_limit' => Str::replace(',', '', $request->sanctioned_limit),
          'limit_approved_date' => $request->limit_approved_date,
          'limit_expiry_date' =>
            $request->has('limit_expiry_date') && !empty($request->limit_expiry_date)
              ? Carbon::parse($request->limit_expiry_date)->format('Y-m-d')
              : null,
          'limit_review_date' =>
            $request->has('limit_review_date') && !empty($request->limit_review_date)
              ? Carbon::parse($request->limit_review_date)->format('Y-m-d')
              : null,
          'drawing_power' => Str::replace(',', '', $request->drawing_power),
          'request_auto_finance' => $request->request_auto_finance,
          'auto_approve_finance' => $request->auto_approve_finance,
          'eligibility' => $request->eligibility,
          'invoice_margin' => $request->invoice_margin,
          'schema_code' => $request->schema_code,
          'product_description' => $request->product_description,
          'vendor_code' => $request->vendor_code,
          'gst_number' => $request->gst_number,
          'classification' => $request->classification,
          'tds' => $request->tds,
          'status' => $request->status,
        ]);

        $company
          ->programDiscountDetails()
          ->where('program_id', $program->id)
          ->delete();
        $company->programDiscountDetails()->create([
          'program_id' => $program->id,
          'benchmark_title' => $request->benchmark_title,
          'benchmark_rate' => $request->benchmark_rate,
          'reset_frequency' => $request->reset_frequency,
          'days_frequency_days' => $request->days_frequency_days,
          'business_strategy_spread' => $request->business_strategy_spread,
          'credit_spread' => $request->credit_spread,
          'total_spread' => $request->total_spread,
          'total_roi' => $request->total_roi,
          'anchor_discount_bearing' => $request->anchor_discount_bearing,
          'vendor_discount_bearing' => $request->vendor_discount_bearing,
          'penal_discount_on_principle' => $request->penal_discount_on_principle,
          'grace_period' => $request->grace_period,
          'grace_period_discount' => $request->grace_period_discount,
          'maturity_handling_on_holidays' => $request->maturity_handling_on_holidays,
        ]);

        $fee_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
          ->where('product_code_id', $program->program_code_id)
          ->where('product_type_id', $program->program_type_id)
          ->where('name', 'Fee Income Account')
          ->first();

        $company
          ->programFeeDetails()
          ->where('program_id', $program->id)
          ->delete();
        if (
          $request->has('fee_names') &&
          count($request->fee_names) > 0 &&
          $request->has('fee_types') &&
          count($request->fee_types) > 0 &&
          $request->has('fee_values') &&
          count($request->fee_values) > 0
        ) {
          foreach ($request->fee_names as $key => $value) {
            if (!empty($value) && !empty($request->fee_values[$key]) && !empty($request->fee_types[$key])) {
              $company->programFeeDetails()->create([
                'program_id' => $program->id,
                'fee_name' => $value,
                'type' => array_key_exists($key, $request->fee_types) ? $request->fee_types[$key] : null,
                'value' => array_key_exists($key, $request->fee_values) ? $request->fee_values[$key] : null,
                'per_amount' =>
                  array_key_exists($key, $request->fee_per_amount) && !empty($request->fee_per_amount[$key])
                    ? $request->fee_per_amount[$key]
                    : null,
                'anchor_bearing_discount' => array_key_exists($key, $request->fee_anchor_bearing_discount)
                  ? $request->fee_anchor_bearing_discount[$key]
                  : null,
                'vendor_bearing_discount' => array_key_exists($key, $request->fee_vendor_bearing_discount)
                  ? $request->fee_vendor_bearing_discount[$key]
                  : null,
                'taxes' => array_key_exists($key, $request->taxes) ? $request->taxes[$key] : null,
                'charge_type' =>
                  $request->has('charge_types') && !empty($request->charge_types) && count($request->charge_types) > 0
                    ? $request->charge_types[$key]
                    : 'fixed',
                'account_number' =>
                  $request->has('fee_account_numbers') &&
                  !empty($request->fee_account_numbers) &&
                  count($request->fee_account_numbers) > 0
                    ? $request->fee_account_numbers[$key]
                    : $fee_income_account->value,
              ]);
            }
          }
        }

        $company
          ->programBankDetails()
          ->where('program_id', $program->id)
          ->delete();
        if (
          $request->has('bank_names_as_per_banks') &&
          count($request->bank_names_as_per_banks) > 0 &&
          $request->has('account_numbers') &&
          count($request->account_numbers) > 0 &&
          $request->has('bank_names') &&
          count($request->bank_names) > 0
        ) {
          foreach ($request->bank_names_as_per_banks as $key => $value) {
            if (
              !empty($value) &&
              !empty($request->account_numbers[$key]) &&
              !empty($request->bank_names[$key]) &&
              !empty($request->bank_names_as_per_banks[$key])
            ) {
              $company->programBankDetails()->create([
                'program_id' => $program->id,
                'name_as_per_bank' => $value,
                'account_number' => array_key_exists($key, $request->account_numbers)
                  ? $request->account_numbers[$key]
                  : null,
                'bank_name' => array_key_exists($key, $request->bank_names) ? $request->bank_names[$key] : null,
                'branch' => array_key_exists($key, $request->branches) ? $request->branches[$key] : null,
                'swift_code' => array_key_exists($key, $request->swift_codes) ? $request->swift_codes[$key] : null,
                'account_type' => array_key_exists($key, $request->account_types)
                  ? $request->account_types[$key]
                  : null,
              ]);
            }
          }
        }

        activity($bank->id)
          ->causedBy(auth()->user())
          ->performedOn($program)
          ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
          ->log('updated mapping ' . $company->name . ' as a vendor');

        toastr()->success('', 'Mapping Updated Successfully');
      }

      if (
        $request->has('vendor_user_name') &&
        !empty($request->vendor_user_name) &&
        $request->has('vendor_user_email') &&
        !empty($request->vendor_user_email) &&
        $request->has('vendor_user_country_code') &&
        !empty($request->vendor_user_country_code) &&
        $request->has('vendor_user_phone_number') &&
        !empty($request->vendor_user_phone_number) &&
        $request->has('vendor_user_role') &&
        !empty($request->vendor_user_role)
      ) {
        $user = User::where('email', $request->vendor_user_email)
          ->orWhere('phone_number', $request->vendor_user_country_code . '' . $request->vendor_user_phone_number)
          ->first();
        if ($user) {
          $is_bank_user = BankUser::where('user_id', $user->id)->first();
          if (!$is_bank_user) {
            CompanyUser::create([
              'company_id' => $company->id,
              'user_id' => $user->id,
            ]);

            $permission_data = PermissionData::find($request->vendor_user_role);

            // Assign Role
            $role = Role::where('name', $permission_data->RoleName)
              ->where('guard_name', 'web')
              ->first();

            if ($role) {
              $user->assignRole($role);
            }

            // $link['Vendor Dashboard'] = config('app.url');

            // SendMail::dispatchAfterResponse($user->email, 'MappedToCompany', [
            //   'type' => 'Company',
            //   'data' => ['company' => $company->name, 'name' => $user->name, 'links' => $link],
            // ]);
          }
        } else {
          $password = Hash::make('Secret!');

          $user = User::create([
            'email' => $request->vendor_user_email,
            'name' => $request->vendor_user_name,
            'phone_number' => $request->vendor_user_country_code . '' . $request->vendor_user_phone_number,
            'password' => $password,
            'receive_notifications' => $request->vendor_user_receive_notifications,
            'module' => 'company',
          ]);

          CompanyUser::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
          ]);

          $permission_data = PermissionData::find($request->vendor_user_role);

          // Assign Role
          $role = Role::where('name', $permission_data->RoleName)
            ->where('guard_name', 'web')
            ->first();

          if ($role) {
            $user->assignRole($role);
          }

          // $link['Vendor Dashboard'] = URL::temporarySignedRoute('auth.reset-password', now()->addHour(), ['id' => $user->id]);

          // SendMail::dispatchAfterResponse($user->email, 'MappedToCompany', [
          //   'type' => 'Company',
          //   'data' => ['company' => $company->name, 'name' => $user->name, 'links' => $link],
          // ]);
        }
      }

      DB::commit();

      return redirect()->route('programs.show', ['bank' => $bank, 'program' => $program]);
    } catch (\Throwable $th) {
      info($th);
      DB::rollback();
      toastr()->error('', 'An error occurred while updating the mapping');
      return back();
    }
  }

  public function showMapBuyer(Bank $bank, Program $program)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Add/Edit Program & Mapping')
    ) {
      toastr()->error('', 'You do not have the permission to perform this action');
      return back();
    }

    $anchor = $program->anchor;
    $buyers = $program->getBuyers();
    $vendors = $bank->getVendors();

    $companies = $bank->companies
      ->where('approval_status', 'approved')
      ->where('status', 'active')
      ->sortDesc();
    $companies = $companies->filter(function ($company) use ($anchor, $buyers) {
      $buyers_ids = $buyers->pluck('id');
      return $company->id != $anchor->id && !collect($buyers_ids)->contains($company->id);
    });

    $program->load('discountDetails', 'fees');

    $benchmark_rates = [];

    $base_rates = BankBaseRate::active()
      ->where('bank_id', $bank->id)
      ->get();

    if ($base_rates->count() <= 0) {
      $base_rates = BaseRate::active()->get();

      foreach ($base_rates as $rate) {
        $benchmark_rates[$rate->rate_code] = $rate->rate;
      }
    } else {
      foreach ($base_rates as $rate) {
        $benchmark_rates[$rate->name] = $rate->rate;
      }
    }

    $taxes = [];

    $bank_tax_rates = BankTaxRate::active()
      ->where('bank_id', $bank->id)
      ->get();

    if ($bank_tax_rates->count() <= 0) {
      $bank_tax_rates = Tax::active()->get();

      foreach ($bank_tax_rates as $rate) {
        $taxes[$rate->name] = $rate->percentage;
      }
    } else {
      foreach ($bank_tax_rates as $rate) {
        $taxes[$rate->tax_name] = $rate->value;
      }
    }

    $banks = BankMasterList::all();

    // Buyer uses anchor roles
    $roles = PermissionData::where('RoleTypeName', 'Anchor')
      ->where(function ($query) use ($bank) {
        $query->where('bank_id', $bank->id)->orWhere('bank_id', null);
      })
      ->select('id', 'RoleName')
      ->get();

    $countries = json_decode(file_get_contents(public_path('assets/country-codes.json')));

    // Get max amount that can be assigned
    $max_limit = $program->max_limit_per_account;
    $sanctioned_limit = $program->max_limit_per_account;
    $program_limit = $program->program_limit;
    $assigned_amount = ProgramVendorConfiguration::where('program_id', $program->id)->sum('sanctioned_limit');
    $remainder = $program_limit - $assigned_amount;
    if ($remainder < $program->max_limit_per_account) {
      $max_limit = $remainder;
      $sanctioned_limit = $max_limit;
    }

    $notification_channels = ['email' => 'Email', 'sms' => 'SMS', 'email_and_sms' => 'Email and SMS'];

    $bank_payment_accounts = $bank->paymentAccounts;

    return view(
      'content.bank.programs.buyers.map',
      compact(
        'bank',
        'program',
        'companies',
        'benchmark_rates',
        'taxes',
        'banks',
        'roles',
        'countries',
        'sanctioned_limit',
        'max_limit',
        'notification_channels',
        'bank_payment_accounts'
      )
    );
  }

  public function editMapBuyer(Bank $bank, Program $program, Company $company)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Add/Edit Program & Mapping')
    ) {
      toastr()->error('', 'You do not have the permission to perform this action');
      return back();
    }

    $program->load('discountDetails', 'fees');

    $benchmark_rates = [];

    $base_rates = BankBaseRate::active()
      ->where('bank_id', $bank->id)
      ->get();

    if ($base_rates->count() <= 0) {
      $base_rates = BaseRate::active()->get();

      foreach ($base_rates as $rate) {
        $benchmark_rates[$rate->rate_code] = $rate->rate;
      }
    } else {
      foreach ($base_rates as $rate) {
        $benchmark_rates[$rate->name] = $rate->rate;
      }
    }

    $taxes = [];

    $bank_tax_rates = BankTaxRate::active()
      ->where('bank_id', $bank->id)
      ->get();

    if ($bank_tax_rates->count() <= 0) {
      $bank_tax_rates = Tax::active()->get();

      foreach ($bank_tax_rates as $rate) {
        $taxes[$rate->name] = $rate->percentage;
      }
    } else {
      foreach ($bank_tax_rates as $rate) {
        $taxes[$rate->tax_name] = $rate->value;
      }
    }

    $banks = BankMasterList::all();

    $mapping = new \stdClass();

    $mapping->configuration = ProgramVendorConfiguration::where('buyer_id', $company->id)
      ->where('program_id', $program->id)
      ->first();
    $mapping->discounts = ProgramVendorDiscount::where('program_id', $program->id)
      ->where('buyer_id', $company->id)
      ->first();
    $mapping->fees = ProgramVendorFee::where('program_id', $program->id)
      ->where('buyer_id', $company->id)
      ->get();
    $mapping->contact_details = ProgramVendorContactDetail::where('program_id', $program->id)
      ->where('buyer_id', $company->id)
      ->get();
    $mapping->bank_details = ProgramVendorBankDetail::where('program_id', $program->id)
      ->where('buyer_id', $company->id)
      ->get();

    // Buyer uses anchor roles
    $roles = PermissionData::where('RoleTypeName', 'Anchor')
      ->where(function ($query) use ($bank) {
        $query->where('bank_id', $bank->id)->orWhere('bank_id', null);
      })
      ->select('id', 'RoleName')
      ->get();

    $countries = json_decode(file_get_contents(public_path('assets/country-codes.json')));

    // Get max amount that can be assigned
    $max_limit = $program->max_limit_per_account;
    $sanctioned_limit = $program->max_limit_per_account;
    $program_limit = $program->program_limit;
    $assigned_amount = ProgramVendorConfiguration::where('program_id', $program->id)->sum('sanctioned_limit');
    $remainder = $program_limit - $assigned_amount;
    $current_vendor_limit = ProgramVendorConfiguration::where('program_id', $program->id)
      ->where('buyer_id', $company->id)
      ->first();
    if ($remainder < $program->max_limit_per_account) {
      $max_limit = $remainder + $current_vendor_limit->sanctioned_limit;
      $sanctioned_limit = $max_limit;
    }

    $notification_channels = ['email' => 'Email', 'sms' => 'SMS', 'email_and_sms' => 'Email and SMS'];

    $bank_payment_accounts = $bank->paymentAccounts;

    return view(
      'content.bank.programs.buyers.edit',
      compact(
        'mapping',
        'banks',
        'taxes',
        'benchmark_rates',
        'program',
        'company',
        'bank',
        'roles',
        'countries',
        'sanctioned_limit',
        'max_limit',
        'notification_channels',
        'bank_payment_accounts'
      )
    );
  }

  public function mapBuyer(Request $request, Bank $bank, Program $program)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Add/Edit Program & Mapping')
    ) {
      toastr()->error('', 'You do not have the permission to perform this action');
      return back();
    }

    // Validator for unique phone numbers when request has country code
    Validator::extend('unique_phone_number', function ($attribute, $value, $parameters, $validator) {
      return !User::where('phone_number', $parameters[0] . '' . substr($value, -9))->exists();
    });

    // Get Current Payment Account Numbers
    $payment_account_numbers = ProgramVendorConfiguration::whereHas('program', function ($query) use ($bank) {
      $query->where('bank_id', $bank->id);
    })->pluck('payment_account_number');

    $request->validate(
      [
        'buyer_id' => ['required'],
        'bank_names_as_per_banks' => ['required', 'array', 'min:1'],
        'bank_names_as_per_banks.*' => ['required', 'string'],
        'account_numbers' => ['required', 'array', 'min:1'],
        'account_numbers.*' => ['required', 'string'],
        'bank_names' => ['required', 'array', 'min:1'],
        'bank_names.*' => ['required', 'string'],
        'payment_account_number' => [
          'required',
          Rule::unique('program_vendor_configurations')->where(function ($query) use ($payment_account_numbers) {
            return $query->whereNotIn('payment_account_number', $payment_account_numbers);
          }),
        ],
        'vendor_user_name' => [
          'sometimes',
          'required_with:vendor_user_email,vendor_user_country_code,vendor_user_phone_number,vendor_user_role,vendor_user_receive_notifications',
        ],
        'vendor_user_email' => [
          'sometimes',
          'required_with:vendor_user_name,vendor_user_country_code,vendor_user_phone_number,vendor_user_role,vendor_user_receive_notifications',
        ],
        'vendor_user_country_code' => [
          'sometimes',
          'required_with:vendor_user_name,vendor_user_email,vendor_user_phone_number,vendor_user_role,vendor_user_receive_notifications',
        ],
        'vendor_user_phone_number' => [
          'sometimes',
          'required_with:vendor_user_name,vendor_user_email,vendor_user_country_code,vendor_user_role,vendor_user_receive_notifications',
          'unique_phone_number:' . $request->vendor_user_country_code,
        ],
        'vendor_user_role' => [
          'sometimes',
          'required_with:vendor_user_name,vendor_user_email,vendor_user_country_code,vendor_user_phone_number,vendor_user_receive_notifications',
        ],
        'vendor_user_receive_notifications' => [
          'sometimes',
          'required_with:vendor_user_name,vendor_user_email,vendor_user_country_code,vendor_user_phone_number,vendor_user_role',
        ],
      ],
      [
        'bank_names_as_per_banks.required' => 'Please enter at least one bank name as per banks.',
        'bank_names_as_per_banks.min' => 'Please select at least one bank name as per banks.',
        'bank_names_as_per_banks.*.required' => 'The Account Name field is required.',
        'account_numbers.required' => 'Please enter at least one account number.',
        'account_numbers.min' => 'Please enter at least one account number.',
        'account_numbers.*.required' => 'The account number is required.',
        'bank_names.required' => 'Please select at least one bank.',
        'bank_names.min' => 'Please select at least one bank.',
        'bank_names.*.required' => 'The bank name is required.',
        'vendor_user_name.required_with' => 'This field is required for the user details.',
        'vendor_user_email.required_with' => 'This field is required for the user details.',
        'vendor_user_country_code.required_with' => 'This field is required for the user details.',
        'vendor_user_phone_number.required_with' => 'This field is required for the user details.',
        'vendor_user_role.required_with' => 'This field is required for the user details.',
        'vendor_user_receive_notifications.required_with' => 'This field is required for the user details.',
        'vendor_user_phone_number.unique_phone_number' => 'This phone number is already in use',
      ]
    );

    try {
      DB::beginTransaction();

      $bank_users = BankUser::where('bank_id', $bank->id)
        ->whereHas('user', function ($query) {
          $query->whereHas('roles', function ($query) {
            $query->whereHas('permissions', function ($query) {
              $query->where(function ($query) {
                $query
                  ->where('name', 'Add/Edit Program & Mapping')
                  ->orWhere('name', 'Activate/Deactivate Program & Mapping');
              });
            });
          });
        })
        ->where('user_id', '!=', auth()->id())
        ->where('active', true)
        ->get();

      $buyer_role = ProgramRole::where('name', 'buyer')->first();

      ProgramCompanyRole::firstOrCreate([
        'program_id' => $program->id,
        'company_id' => $request->buyer_id,
        'role_id' => $buyer_role->id,
      ]);

      $anchor = $program->anchor;

      $buyer = Company::find($request->buyer_id);

      $anchor->programConfigurations()->create([
        'program_id' => $program->id,
        'payment_account_number' => $request->payment_account_number,
        'sanctioned_limit' => Str::replace(',', '', $request->sanctioned_limit),
        'limit_approved_date' => $request->limit_approved_date,
        'limit_expiry_date' =>
          $request->has('limit_expiry_date') && !empty($request->limit_expiry_date)
            ? Carbon::parse($request->limit_expiry_date)->format('Y-m-d')
            : null,
        'limit_review_date' =>
          $request->has('limit_review_date') && !empty($request->limit_review_date)
            ? Carbon::parse($request->limit_review_date)->format('Y-m-d')
            : null,
        'drawing_power' => Str::replace(',', '', $request->drawing_power),
        'request_auto_finance' => $request->request_auto_finance,
        'auto_approve_finance' => $request->auto_approve_finance,
        'eligibility' => $request->eligibility,
        'invoice_margin' => $request->invoice_margin,
        'schema_code' => $request->schema_code,
        'product_description' => $request->product_description,
        'vendor_code' => $request->vendor_code,
        'gst_number' => $request->gst_number,
        'classification' => $request->classification,
        'payment_terms' => $program->default_payment_terms,
        'tds' => $request->tds,
        'status' => $bank_users->count() > 0 ? 'inactive' : 'active',
        'buyer_id' => $request->buyer_id,
        'created_by' => auth()->id(),
        'is_approved' => false,
      ]);

      $anchor->programDiscountDetails()->create([
        'program_id' => $program->id,
        'benchmark_title' => $request->benchmark_title ?? $program->discountDetails?->first()?->benchmark_title,
        'benchmark_rate' => $request->benchmark_rate,
        'reset_frequency' => $request->reset_frequency,
        'days_frequency_days' => $request->days_frequency_days,
        'business_strategy_spread' => $request->business_strategy_spread,
        'credit_spread' => $request->credit_spread,
        'total_spread' => $request->total_spread,
        'total_roi' => $request->total_roi,
        'anchor_discount_bearing' => $request->anchor_discount_bearing,
        'vendor_discount_bearing' => $request->vendor_discount_bearing,
        'penal_discount_on_principle' => $request->penal_discount_on_principle,
        'grace_period' => $request->grace_period,
        'grace_period_discount' => $request->grace_period_discount,
        'maturity_handling_on_holidays' => $request->maturity_handling_on_holidays,
        'buyer_id' => $request->buyer_id,
      ]);

      $fee_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
        ->where('product_code_id', $program->program_code_id)
        ->where('product_type_id', $program->program_type_id)
        ->where('name', 'Fee Income Account')
        ->first();

      if (
        $request->has('fee_names') &&
        count($request->fee_names) > 0 &&
        $request->has('fee_types') &&
        count($request->fee_types) > 0 &&
        $request->has('fee_values') &&
        count($request->fee_values) > 0
      ) {
        foreach ($request->fee_names as $key => $value) {
          $anchor->programFeeDetails()->create([
            'program_id' => $program->id,
            'fee_name' => $value,
            'type' => array_key_exists($key, $request->fee_types) ? $request->fee_types[$key] : null,
            'value' => array_key_exists($key, $request->fee_values) ? $request->fee_values[$key] : null,
            'per_amount' =>
              array_key_exists($key, $request->fee_per_amount) && !empty($request->fee_per_amount[$key])
                ? $request->fee_per_amount[$key]
                : null,
            'anchor_bearing_discount' => array_key_exists($key, $request->fee_anchor_bearing_discount)
              ? $request->fee_anchor_bearing_discount[$key]
              : null,
            'vendor_bearing_discount' => array_key_exists($key, $request->fee_vendor_bearing_discount)
              ? $request->fee_vendor_bearing_discount[$key]
              : null,
            'taxes' => array_key_exists($key, $request->taxes) ? $request->taxes[$key] : null,
            'buyer_id' => $request->buyer_id,
            'charge_type' =>
              $request->has('charge_types') &&
              !empty($request->charge_types) &&
              count($request->charge_types) > 0 &&
              array_key_exists($key, $request->charge_types)
                ? $request->charge_types[$key]
                : 'fixed',
            'account_number' =>
              $request->has('fee_account_numbers') &&
              !empty($request->fee_account_numbers) &&
              count($request->fee_account_numbers) > 0 &&
              array_key_exists($key, $request->fee_account_numbers)
                ? $request->fee_account_numbers[$key]
                : $fee_income_account->value,
          ]);
        }
      }

      if (
        $request->has('vendor_user_name') &&
        !empty($request->vendor_user_name) &&
        $request->has('vendor_user_email') &&
        !empty($request->vendor_user_email) &&
        $request->has('vendor_user_country_code') &&
        !empty($request->vendor_user_country_code) &&
        $request->has('vendor_user_phone_number') &&
        !empty($request->vendor_user_phone_number) &&
        $request->has('vendor_user_role') &&
        !empty($request->vendor_user_role)
      ) {
        $user = User::where('email', $request->vendor_user_email)
          ->orWhere('phone_number', $request->vendor_user_country_code . '' . $request->vendor_user_phone_number)
          ->first();
        if ($user) {
          $is_bank_user = BankUser::where('user_id', $user->id)->first();
          if (!$is_bank_user) {
            CompanyUser::create([
              'company_id' => $buyer->id,
              'user_id' => $user->id,
            ]);

            $permission_data = PermissionData::find($request->vendor_user_role);

            // Assign Role
            $role = Role::where('name', $permission_data->RoleName)
              ->where('guard_name', 'web')
              ->first();

            if ($role) {
              $user->assignRole($role);
            }

            // $link['Buyer Dashboard'] = config('app.url');

            // SendMail::dispatchAfterResponse($user->email, 'MappedToCompany', [
            //   'type' => 'Company',
            //   'data' => ['company' => $buyer->name, 'name' => $user->name, 'links' => $link],
            // ]);
          }
        } else {
          $password = Hash::make('Secret!');

          $user = User::create([
            'email' => $request->vendor_user_email,
            'name' => $request->vendor_user_name,
            'phone_number' => $request->vendor_user_country_code . '' . $request->vendor_user_phone_number,
            'password' => $password,
            'receive_notifications' => $request->vendor_user_receive_notifications,
            'module' => 'company',
          ]);

          CompanyUser::create([
            'company_id' => $buyer->id,
            'user_id' => $user->id,
          ]);

          $permission_data = PermissionData::find($request->vendor_user_role);

          // Assign Role
          $role = Role::where('name', $permission_data->RoleName)
            ->where('guard_name', 'web')
            ->first();

          if ($role) {
            $user->assignRole($role);
          }

          // $link = URL::temporarySignedRoute('auth.reset-password', now()->addHour(), ['id' => $user->id]);

          // SendMail::dispatchAfterResponse($user->email, 'MappedToCompany', [
          //   'type' => 'Company',
          //   'data' => ['company' => $buyer->name, 'name' => $user->name, 'links' => $link],
          // ]);
        }
      }

      if (
        $request->has('bank_names_as_per_banks') &&
        !empty($request->bank_names_as_per_banks[0]) &&
        count($request->bank_names_as_per_banks) > 0 &&
        $request->has('account_numbers') &&
        !empty($request->account_numbers[0]) &&
        count($request->account_numbers) > 0 &&
        $request->has('bank_names') &&
        !empty($request->bank_names[0]) &&
        count($request->bank_names) > 0
      ) {
        foreach ($request->bank_names_as_per_banks as $key => $value) {
          $anchor->programBankDetails()->create([
            'program_id' => $program->id,
            'name_as_per_bank' => $value,
            'account_number' => array_key_exists($key, $request->account_numbers)
              ? $request->account_numbers[$key]
              : null,
            'bank_name' => array_key_exists($key, $request->bank_names) ? $request->bank_names[$key] : null,
            'branch' => array_key_exists($key, $request->branches) ? $request->branches[$key] : null,
            'swift_code' => array_key_exists($key, $request->swift_codes) ? $request->swift_codes[$key] : null,
            'account_type' => array_key_exists($key, $request->account_types) ? $request->account_types[$key] : null,
            'buyer_id' => $request->buyer_id,
          ]);
        }
      }

      $invoice_setting = $buyer->invoiceSetting;

      if ($buyer->invoiceSetting) {
        $invoice_setting = $buyer->invoiceSetting()->create();
      }

      $invoice_setting->update([
        'default_payment_terms' => $program->default_payment_terms,
      ]);

      $buyer->notify(new NewProgramMapping($program, 'buyer'));

      activity($bank->id)
        ->causedBy(auth()->user())
        ->performedOn($program)
        ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
        ->log('mapped ' . $buyer->name . ' as buyer');

      DB::commit();

      if ($bank_users->count() > 0) {
        // Notify users of changes
        foreach ($bank_users as $bank_user) {
          SendMail::dispatchAfterResponse($bank_user->user->email, 'ProgramChanged', [
            'program' => $program->id,
            'url' => route('programs.show', ['bank' => $bank, 'program' => $program]),
            'name' => auth()->user()->name,
            'type' => 'vendor_financing',
          ]);
          $bank_user->user->notify(new ProgramUpdation($program));
        }

        toastr()->success('', 'Buyer mapping sent for approval');
      } else {
        toastr()->success('', 'Buyer mapped successfully');
      }

      return redirect()->route('programs.show', ['bank' => $bank, 'program' => $program]);
    } catch (\Throwable $th) {
      info($th);
      DB::rollback();
      toastr()->error('', 'An error occurred while mapping the company.');
      return back();
    }
  }

  public function updateMapBuyer(Request $request, Bank $bank, Program $program, Company $company)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Add/Edit Program & Mapping')
    ) {
      toastr()->error('', 'You do not have the permission to perform this action');
      return back();
    }

    // Validator for unique phone numbers when request has country code
    Validator::extend('unique_phone_number', function ($attribute, $value, $parameters, $validator) {
      return !User::where('phone_number', $parameters[0] . '' . substr($value, -9))->exists();
    });

    // Get Current Payment Account Numbers
    $payment_account_numbers = ProgramVendorConfiguration::whereHas('program', function ($query) use ($bank) {
      $query->where('bank_id', $bank->id);
    })
      ->where('buyer_id', '!=', $company->id)
      ->pluck('payment_account_number');

    $request->validate(
      [
        'payment_account_number' => [
          'required',
          Rule::unique('program_vendor_configurations')
            ->ignore($company->id, 'buyer_id')
            ->where(function ($query) use ($payment_account_numbers) {
              return $query->whereNotIn('payment_account_number', $payment_account_numbers);
            }),
        ],
        'sanctioned_limit' => ['required'],
        'limit_approved_date' => ['required'],
        // 'limit_review_date' => ['required'],
        'request_auto_finance' => ['required'],
        'auto_approve_finance' => ['required'],
        'eligibility' => ['required'],
        'status' => ['required'],
        // 'benchmark_title' => ['required'],
        // 'benchmark_rate' => ['required'],
        // 'business_strategy_spread' => ['required'],
        // 'credit_spread' => ['required'],
        // 'total_roi' => ['required'],
        // 'anchor_discount_bearing' => ['required'],
        // 'vendor_discount_bearing' => ['required'],
        // 'penal_discount_on_principle' => ['required'],
        // 'grace_period' => ['required'],
        // 'grace_period_discount' => ['required'],
        'account_numbers' => ['required', 'array', 'min:1'],
        'bank_names_as_per_banks' => ['required', 'array', 'min:1'],
        'vendor_user_name' => [
          'sometimes',
          'required_with:vendor_user_email,vendor_user_country_code,vendor_user_phone_number,vendor_user_role,vendor_user_receive_notifications',
        ],
        'vendor_user_email' => [
          'sometimes',
          'required_with:vendor_user_name,vendor_user_country_code,vendor_user_phone_number,vendor_user_role,vendor_user_receive_notifications',
        ],
        'vendor_user_country_code' => [
          'sometimes',
          'required_with:vendor_user_name,vendor_user_email,vendor_user_phone_number,vendor_user_role,vendor_user_receive_notifications',
        ],
        'vendor_user_phone_number' => [
          'sometimes',
          'required_with:vendor_user_name,vendor_user_email,vendor_user_country_code,vendor_user_role,vendor_user_receive_notifications',
          'unique_phone_number:' . $request->vendor_user_country_code,
        ],
        'vendor_user_role' => [
          'sometimes',
          'required_with:vendor_user_name,vendor_user_email,vendor_user_country_code,vendor_user_phone_number,vendor_user_receive_notifications',
        ],
        'vendor_user_receive_notifications' => [
          'sometimes',
          'required_with:vendor_user_name,vendor_user_email,vendor_user_country_code,vendor_user_phone_number,vendor_user_role',
        ],
      ],
      [
        'vendor_user_name.required_with' => 'This field is required for the user details.',
        'vendor_user_email.required_with' => 'This field is required for the user details.',
        'vendor_user_country_code.required_with' => 'This field is required for the user details.',
        'vendor_user_phone_number.required_with' => 'This field is required for the user details.',
        'vendor_user_role.required_with' => 'This field is required for the user details.',
        'vendor_user_receive_notifications.required_with' => 'This field is required for the user details.',
        'vendor_user_country_code.unique_phone_number' => 'This phone number is already is use',
      ]
    );

    $bank_users = BankUser::where('bank_id', $bank->id)
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where(function ($query) {
              $query
                ->where('name', 'Add/Edit Program & Mapping')
                ->orWhere('name', 'Activate/Deactivate Program & Mapping');
            });
          });
        });
      })
      ->where('user_id', '!=', auth()->id())
      ->where('active', true)
      ->get();

    try {
      DB::beginTransaction();
      $update_data = [];
      if ($bank_users->count() > 0) {
        ProgramMappingChange::where('program_id', $program->id)
          ->where('company_id', $company->id)
          ->delete();
        $new_mapping_change = new ProgramMappingChange();
        $new_mapping_change->program_id = $program->id;
        $new_mapping_change->company_id = $company->id;
        $new_mapping_change->user_id = auth()->id();
        $update_data['vendor_configuration'] = [
          'payment_account_number' => $request->payment_account_number,
          'sanctioned_limit' => Str::replace(',', '', $request->sanctioned_limit),
          'limit_approved_date' => $request->limit_approved_date,
          'limit_expiry_date' =>
            $request->has('limit_expiry_date') && !empty($request->limit_expiry_date)
              ? Carbon::parse($request->limit_expiry_date)->format('Y-m-d')
              : null,
          'limit_review_date' =>
            $request->has('limit_review_date') && !empty($request->limit_review_date)
              ? Carbon::parse($request->limit_review_date)->format('Y-m-d')
              : null,
          'drawing_power' => Str::replace(',', '', $request->drawing_power),
          'request_auto_finance' => $request->request_auto_finance,
          'auto_approve_finance' => $request->auto_approve_finance,
          'eligibility' => $request->eligibility,
          'invoice_margin' => $request->invoice_margin,
          'schema_code' => $request->schema_code,
          'product_description' => $request->product_description,
          'vendor_code' => $request->vendor_code,
          'gst_number' => $request->gst_number,
          'classification' => $request->classification,
          'tds' => $request->tds,
          'status' => $request->status,
        ];

        $update_data['vendor_discount_details'] = [
          'benchmark_title' => $request->benchmark_title,
          'benchmark_rate' => $request->benchmark_rate ?? 0,
          'reset_frequency' => $request->reset_frequency,
          'days_frequency_days' => $request->days_frequency_days,
          'business_strategy_spread' => $request->business_strategy_spread ?? 0,
          'credit_spread' => $request->credit_spread ?? 0,
          'total_spread' => $request->total_spread ?? 0,
          'total_roi' => $request->total_roi ?? 0,
          'anchor_discount_bearing' => $request->anchor_discount_bearing ?? 0,
          'vendor_discount_bearing' => $request->vendor_discount_bearing ?? 100,
          'penal_discount_on_principle' => $request->penal_discount_on_principle ?? 0,
          'grace_period' => $request->grace_period ?? 0,
          'grace_period_discount' => $request->grace_period_discount ?? 0,
          'maturity_handling_on_holidays' => $request->maturity_handling_on_holidays ?? 'No Effect',
        ];

        $fee_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
          ->where('product_code_id', $program->program_code_id)
          ->where('product_type_id', $program->program_type_id)
          ->where('name', 'Fee Income Account')
          ->first();

        if (
          $request->has('fee_names') &&
          count($request->fee_names) > 0 &&
          $request->has('fee_types') &&
          count($request->fee_types) > 0 &&
          $request->has('fee_values') &&
          count($request->fee_values) > 0
        ) {
          foreach ($request->fee_names as $key => $value) {
            if (!empty($value) && !empty($request->fee_values[$key]) && !empty($request->fee_types[$key])) {
              $update_data['vendor_fee_details'][$key] = [
                'fee_name' => $value,
                'type' => array_key_exists($key, $request->fee_types) ? $request->fee_types[$key] : null,
                'value' => array_key_exists($key, $request->fee_values) ? $request->fee_values[$key] : null,
                'per_amount' =>
                  array_key_exists($key, $request->fee_per_amount) && !empty($request->fee_per_amount[$key])
                    ? $request->fee_per_amount[$key]
                    : null,
                'anchor_bearing_discount' => array_key_exists($key, $request->fee_anchor_bearing_discount)
                  ? $request->fee_anchor_bearing_discount[$key]
                  : null,
                'vendor_bearing_discount' => array_key_exists($key, $request->fee_vendor_bearing_discount)
                  ? $request->fee_vendor_bearing_discount[$key]
                  : null,
                'taxes' => array_key_exists($key, $request->taxes) ? $request->taxes[$key] : null,
                'charge_type' =>
                  $request->has('charge_types') &&
                  !empty($request->charge_types) &&
                  count($request->charge_types) > 0 &&
                  array_key_exists($key, $request->charge_types)
                    ? $request->charge_types[$key]
                    : 'fixed',
                'account_number' =>
                  $request->has('fee_account_numbers') &&
                  !empty($request->fee_account_numbers) &&
                  count($request->fee_account_numbers) > 0 &&
                  array_key_exists($key, $request->fee_account_numbers)
                    ? $request->fee_account_numbers[$key]
                    : $fee_income_account->value,
              ];
            }
          }
        }

        if (
          $request->has('bank_names_as_per_banks') &&
          count($request->bank_names_as_per_banks) > 0 &&
          $request->has('account_numbers') &&
          count($request->account_numbers) > 0 &&
          $request->has('bank_names') &&
          count($request->bank_names) > 0
        ) {
          foreach ($request->bank_names_as_per_banks as $key => $value) {
            if (
              !empty($value) &&
              !empty($request->account_numbers[$key]) &&
              !empty($request->bank_names[$key]) &&
              !empty($request->bank_names_as_per_banks[$key])
            ) {
              $update_data['vendor_bank_details'][$key] = [
                'name_as_per_bank' => $value,
                'account_number' => array_key_exists($key, $request->account_numbers)
                  ? $request->account_numbers[$key]
                  : null,
                'bank_name' => array_key_exists($key, $request->bank_names) ? $request->bank_names[$key] : null,
                'branch' => array_key_exists($key, $request->branches) ? $request->branches[$key] : null,
                'swift_code' => array_key_exists($key, $request->swift_codes) ? $request->swift_codes[$key] : null,
                'account_type' => array_key_exists($key, $request->account_types)
                  ? $request->account_types[$key]
                  : null,
              ];
            }
          }
        }

        $new_mapping_change->changes = $update_data;
        $new_mapping_change->save();

        // Remove rejected indicator from configuration
        $vendor_config = ProgramVendorConfiguration::where('buyer_id', $company->id)
          ->where('program_id', $program->id)
          ->first();
        if ($vendor_config && $vendor_config->rejected_by && $vendor_config->rejection_reason) {
          $vendor_config->update([
            'rejected_by' => null,
            'rejection_reason' => null,
          ]);
        }

        toastr()->success('', 'Mapping Changes sent for approval');
      } else {
        $program
          ->vendorConfigurations()
          ->where('buyer_id', $company->id)
          ->delete();
        $program->vendorConfigurations()->create([
          'company_id' => $program->anchor->id,
          'buyer_id' => $company->id,
          'payment_account_number' => $request->payment_account_number,
          'sanctioned_limit' => Str::replace(',', '', $request->sanctioned_limit),
          'limit_approved_date' => $request->limit_approved_date,
          'limit_expiry_date' =>
            $request->has('limit_expiry_date') && !empty($request->limit_expiry_date)
              ? Carbon::parse($request->limit_expiry_date)->format('Y-m-d')
              : null,
          'limit_review_date' =>
            $request->has('limit_review_date') && !empty($request->limit_review_date)
              ? Carbon::parse($request->limit_review_date)->format('Y-m-d')
              : null,
          'drawing_power' => Str::replace(',', '', $request->drawing_power),
          'request_auto_finance' => $request->request_auto_finance,
          'auto_approve_finance' => $request->auto_approve_finance,
          'eligibility' => $request->eligibility,
          'invoice_margin' => $request->invoice_margin,
          'schema_code' => $request->schema_code,
          'product_description' => $request->product_description,
          'vendor_code' => $request->vendor_code,
          'gst_number' => $request->gst_number,
          'classification' => $request->classification,
          'tds' => $request->tds,
          'status' => $request->status,
        ]);

        $program
          ->vendorDiscountDetails()
          ->where('buyer_id', $company->id)
          ->delete();
        $program->vendorDiscountDetails()->create([
          'company_id' => $program->anchor->id,
          'buyer_id' => $company->id,
          'benchmark_title' => $request->benchmark_title,
          'benchmark_rate' => $request->benchmark_rate,
          'reset_frequency' => $request->reset_frequency,
          'days_frequency_days' => $request->days_frequency_days,
          'business_strategy_spread' => $request->business_strategy_spread,
          'credit_spread' => $request->credit_spread,
          'total_spread' => $request->total_spread,
          'total_roi' => $request->total_roi,
          'anchor_discount_bearing' => $request->anchor_discount_bearing,
          'vendor_discount_bearing' => $request->vendor_discount_bearing,
          'penal_discount_on_principle' => $request->penal_discount_on_principle,
          'grace_period' => $request->grace_period,
          'grace_period_discount' => $request->grace_period_discount,
          'maturity_handling_on_holidays' => $request->maturity_handling_on_holidays,
        ]);

        $fee_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
          ->where('product_code_id', $program->program_code_id)
          ->where('product_type_id', $program->program_type_id)
          ->where('name', 'Fee Income Account')
          ->first();

        $program
          ->vendorFeeDetails()
          ->where('buyer_id', $company->id)
          ->delete();
        if (
          $request->has('fee_names') &&
          count($request->fee_names) > 0 &&
          $request->has('fee_types') &&
          count($request->fee_types) > 0 &&
          $request->has('fee_values') &&
          count($request->fee_values) > 0
        ) {
          foreach ($request->fee_names as $key => $value) {
            if (!empty($value) && !empty($request->fee_values[$key]) && !empty($request->fee_types[$key])) {
              $program->vendorFeeDetails()->create([
                'company_id' => $program->anchor->id,
                'buyer_id' => $company->id,
                'fee_name' => $value,
                'type' => array_key_exists($key, $request->fee_types) ? $request->fee_types[$key] : null,
                'value' => array_key_exists($key, $request->fee_values) ? $request->fee_values[$key] : null,
                'per_amount' =>
                  array_key_exists($key, $request->fee_per_amount) && !empty($request->fee_per_amount[$key])
                    ? $request->fee_per_amount[$key]
                    : null,
                'anchor_bearing_discount' => array_key_exists($key, $request->fee_anchor_bearing_discount)
                  ? $request->fee_anchor_bearing_discount[$key]
                  : null,
                'vendor_bearing_discount' => array_key_exists($key, $request->fee_vendor_bearing_discount)
                  ? $request->fee_vendor_bearing_discount[$key]
                  : null,
                'taxes' => array_key_exists($key, $request->taxes) ? $request->taxes[$key] : null,
                'charge_type' =>
                  $request->has('charge_types') && !empty($request->charge_types) && count($request->charge_types) > 0
                    ? $request->charge_types[$key]
                    : 'fixed',
                'account_number' =>
                  $request->has('fee_account_numbers') &&
                  !empty($request->fee_account_numbers) &&
                  count($request->fee_account_numbers) > 0
                    ? $request->fee_account_numbers[$key]
                    : $fee_income_account->value,
              ]);
            }
          }
        }

        $program
          ->vendorBankDetails()
          ->where('buyer_id', $company->id)
          ->delete();
        if (
          $request->has('bank_names_as_per_banks') &&
          count($request->bank_names_as_per_banks) > 0 &&
          $request->has('account_numbers') &&
          count($request->account_numbers) > 0 &&
          $request->has('bank_names') &&
          count($request->bank_names) > 0
        ) {
          foreach ($request->bank_names_as_per_banks as $key => $value) {
            if (
              !empty($value) &&
              !empty($request->account_numbers[$key]) &&
              !empty($request->bank_names[$key]) &&
              !empty($request->bank_names_as_per_banks[$key])
            ) {
              $program->vendorBankDetails()->create([
                'company_id' => $program->anchor->id,
                'buyer_id' => $company->id,
                'name_as_per_bank' => $value,
                'account_number' => array_key_exists($key, $request->account_numbers)
                  ? $request->account_numbers[$key]
                  : null,
                'bank_name' => array_key_exists($key, $request->bank_names) ? $request->bank_names[$key] : null,
                'branch' => array_key_exists($key, $request->branches) ? $request->branches[$key] : null,
                'swift_code' => array_key_exists($key, $request->swift_codes) ? $request->swift_codes[$key] : null,
                'account_type' => array_key_exists($key, $request->account_types)
                  ? $request->account_types[$key]
                  : null,
              ]);
            }
          }
        }

        $invoice_setting = $company->invoiceSetting;

        if ($company->invoiceSetting) {
          $invoice_setting = $company->invoiceSetting()->create();
        }

        $invoice_setting->update([
          'default_payment_terms' => $program->default_payment_terms,
        ]);

        activity($bank->id)
          ->causedBy(auth()->user())
          ->performedOn($program)
          ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
          ->log('updated mapping ' . $company->name . ' as a vendor');

        toastr()->success('', 'Mapping Updated Successfully');
      }

      if (
        $request->has('vendor_user_name') &&
        !empty($request->vendor_user_name) &&
        $request->has('vendor_user_email') &&
        !empty($request->vendor_user_email) &&
        $request->has('vendor_user_country_code') &&
        !empty($request->vendor_user_country_code) &&
        $request->has('vendor_user_phone_number') &&
        !empty($request->vendor_user_phone_number) &&
        $request->has('vendor_user_role') &&
        !empty($request->vendor_user_role)
      ) {
        $user = User::where('email', $request->vendor_user_email)
          ->orWhere('phone_number', $request->vendor_user_country_code . '' . $request->vendor_user_phone_number)
          ->first();
        if ($user) {
          $is_bank_user = BankUser::where('user_id', $user->id)->first();
          if (!$is_bank_user) {
            CompanyUser::create([
              'company_id' => $company->id,
              'user_id' => $user->id,
            ]);

            $permission_data = PermissionData::find($request->vendor_user_role);

            // Assign Role
            $role = Role::where('name', $permission_data->RoleName)
              ->where('guard_name', 'web')
              ->first();

            if ($role) {
              $user->assignRole($role);
            }

            // $link['Buyer Dashboard'] = config('app.url');

            // SendMail::dispatchAfterResponse($user->email, 'MappedToCompany', [
            //   'type' => 'Company',
            //   'data' => ['company' => $company->name, 'name' => $user->name, 'links' => $link],
            // ]);
          }
        } else {
          $user = User::create([
            'email' => $request->vendor_user_email,
            'name' => $request->vendor_user_name,
            'phone_number' => $request->vendor_user_country_code . '' . $request->vendor_user_phone_number,
            'password' => Hash::make('Secret!'),
            'receive_notifications' => $request->vendor_user_receive_notifications,
            'module' => 'company',
          ]);

          CompanyUser::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
          ]);

          $permission_data = PermissionData::find($request->vendor_user_role);

          // Assign Role
          $role = Role::where('name', $permission_data->RoleName)
            ->where('guard_name', 'web')
            ->first();

          if ($role) {
            $user->assignRole($role);
          }

          // $link['Buyer Dashobard'] = URL::temporarySignedRoute('auth.reset-password', now()->addHour(), ['id' => $user->id]);

          // SendMail::dispatchAfterResponse($user->email, 'MappedToCompany', [
          //   'type' => 'Company',
          //   'data' => ['company' => $company->name, 'name' => $user->name, 'links' => $link],
          // ]);
        }
      }

      DB::commit();
      return redirect()->route('programs.show', ['bank' => $bank, 'program' => $program]);
    } catch (\Throwable $th) {
      DB::rollBack();
      info($th);
      toastr()->error('', 'An error occurred while updating the mapping');
      return back();
    }
  }

  public function showMapDealer(Bank $bank, Program $program)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Add/Edit Program & Mapping')
    ) {
      toastr()->error('', 'You do not have the permission to perform this action');
      return back();
    }

    $anchor = $program->anchor;
    $buyers = $program->getDealers();
    $vendors = $bank->getVendors();

    $companies = $bank->companies
      ->where('approval_status', 'approved')
      ->where('status', 'active')
      ->sortDesc();
    $companies = $companies->filter(function ($company) use ($anchor, $buyers, $vendors) {
      $buyers_ids = $buyers->pluck('id');
      $vendor_ids = $vendors->pluck('id');

      return $company->id != $anchor->id &&
        !collect($buyers_ids)->contains($company->id) &&
        !collect($vendor_ids)->contains($company->id);
    });

    $program->load('discountDetails', 'dealerDiscountRates', 'fees');

    $benchmark_rates = [];

    $base_rates = BankBaseRate::active()
      ->where('bank_id', $bank->id)
      ->get();

    if ($base_rates->count() <= 0) {
      $base_rates = BaseRate::active()->get();

      foreach ($base_rates as $rate) {
        $benchmark_rates[$rate->rate_code] = $rate->rate;
      }
    } else {
      foreach ($base_rates as $rate) {
        $benchmark_rates[$rate->name] = $rate->rate;
      }
    }

    $taxes = [];

    $bank_tax_rates = BankTaxRate::active()
      ->where('bank_id', $bank->id)
      ->get();

    if ($bank_tax_rates->count() <= 0) {
      $bank_tax_rates = Tax::active()->get();

      foreach ($bank_tax_rates as $rate) {
        $taxes[$rate->name] = $rate->percentage;
      }
    } else {
      foreach ($bank_tax_rates as $rate) {
        $taxes[$rate->tax_name] = $rate->value;
      }
    }

    $banks = BankMasterList::all();

    $roles = PermissionData::where('RoleTypeName', 'Dealer')
      ->where(function ($query) use ($bank) {
        $query->where('bank_id', $bank->id)->orWhere('bank_id', null);
      })
      ->select('id', 'RoleName')
      ->get();

    $countries = json_decode(file_get_contents(public_path('assets/country-codes.json')));

    // Get max amount that can be assigned
    $max_limit = $program->max_limit_per_account;
    $sanctioned_limit = $program->max_limit_per_account;
    $program_limit = $program->program_limit;
    $assigned_amount = ProgramVendorConfiguration::where('program_id', $program->id)->sum('sanctioned_limit');
    $remainder = $program_limit - $assigned_amount;
    if ($remainder < $program->max_limit_per_account) {
      $max_limit = $remainder;
      $sanctioned_limit = $max_limit;
    }

    $notification_channels = ['email' => 'Email', 'sms' => 'SMS', 'email_and_sms' => 'Email and SMS'];

    $bank_payment_accounts = $bank->paymentAccounts;

    return view(
      'content.bank.programs.dealers.map',
      compact(
        'bank',
        'program',
        'companies',
        'benchmark_rates',
        'taxes',
        'banks',
        'roles',
        'countries',
        'sanctioned_limit',
        'max_limit',
        'notification_channels',
        'bank_payment_accounts'
      )
    );
  }

  public function editMapDealer(Bank $bank, Program $program, Company $company)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Add/Edit Program & Mapping')
    ) {
      toastr()->error('', 'You do not have the permission to perform this action');
      return back();
    }

    $program->load('discountDetails', 'dealerDiscountRates', 'fees');

    $benchmark_rates = [];

    $base_rates = BankBaseRate::active()
      ->where('bank_id', $bank->id)
      ->get();

    if ($base_rates->count() <= 0) {
      $base_rates = BaseRate::active()->get();

      foreach ($base_rates as $rate) {
        $benchmark_rates[$rate->rate_code] = $rate->rate;
      }
    } else {
      foreach ($base_rates as $rate) {
        $benchmark_rates[$rate->name] = $rate->rate;
      }
    }

    $taxes = [];

    $bank_tax_rates = BankTaxRate::active()
      ->where('bank_id', $bank->id)
      ->get();

    if ($bank_tax_rates->count() <= 0) {
      $bank_tax_rates = Tax::active()->get();

      foreach ($bank_tax_rates as $rate) {
        $taxes[$rate->name] = $rate->percentage;
      }
    } else {
      foreach ($bank_tax_rates as $rate) {
        $taxes[$rate->tax_name] = $rate->value;
      }
    }

    $banks = BankMasterList::all();

    $mapping = new \stdClass();

    $mapping->configuration = ProgramVendorConfiguration::where('company_id', $company->id)
      ->where('program_id', $program->id)
      ->first();
    $mapping->discount = ProgramVendorDiscount::where('program_id', $program->id)
      ->where('company_id', $company->id)
      ->get();
    $mapping->fees = ProgramVendorFee::where('program_id', $program->id)
      ->where('company_id', $company->id)
      ->get();
    $mapping->contact_details = ProgramVendorContactDetail::where('program_id', $program->id)
      ->where('company_id', $company->id)
      ->get();
    $mapping->bank_details = ProgramVendorBankDetail::where('program_id', $program->id)
      ->where('company_id', $company->id)
      ->get();

    $roles = PermissionData::where('RoleTypeName', 'Dealer')
      ->where(function ($query) use ($bank) {
        $query->where('bank_id', $bank->id)->orWhere('bank_id', null);
      })
      ->select('id', 'RoleName')
      ->get();

    $countries = json_decode(file_get_contents(public_path('assets/country-codes.json')));

    // Get max amount that can be assigned
    $max_limit = $program->max_limit_per_account;
    $sanctioned_limit = $program->max_limit_per_account;
    $program_limit = $program->program_limit;
    $assigned_amount = ProgramVendorConfiguration::where('program_id', $program->id)->sum('sanctioned_limit');
    $remainder = $program_limit - $assigned_amount;
    $current_vendor_limit = ProgramVendorConfiguration::where('program_id', $program->id)
      ->where('company_id', $company->id)
      ->first();
    if ($remainder < $program->max_limit_per_account) {
      $max_limit = $remainder + $current_vendor_limit->sanctioned_limit;
      $sanctioned_limit = $max_limit;
    }

    $notification_channels = ['email' => 'Email', 'sms' => 'SMS', 'email_and_sms' => 'Email and SMS'];

    $bank_payment_accounts = $bank->paymentAccounts;

    return view(
      'content.bank.programs.dealers.edit',
      compact(
        'mapping',
        'banks',
        'taxes',
        'benchmark_rates',
        'program',
        'company',
        'bank',
        'roles',
        'countries',
        'sanctioned_limit',
        'max_limit',
        'notification_channels',
        'bank_payment_accounts'
      )
    );
  }

  public function mapDealer(Request $request, Bank $bank, Program $program)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Add/Edit Program & Mapping')
    ) {
      toastr()->error('', 'You do not have the permission to perform this action');
      return back();
    }

    // Validator for unique phone numbers when request has country code
    Validator::extend('unique_phone_number', function ($attribute, $value, $parameters, $validator) {
      return !User::where('phone_number', $parameters[0] . '' . substr($value, -9))->exists();
    });

    // Get Current Payment Account Numbers
    $payment_account_numbers = ProgramVendorConfiguration::whereHas('program', function ($query) use ($bank) {
      $query->where('bank_id', $bank->id);
    })->pluck('payment_account_number');

    $request->validate(
      [
        'dealer_id' => ['required'],
        'bank_names_as_per_banks' => ['required', 'array', 'min:1'],
        'bank_names_as_per_banks.*' => ['required', 'string'],
        'account_numbers' => ['required', 'array', 'min:1'],
        'account_numbers.*' => ['required', 'string'],
        'bank_names' => ['required', 'array', 'min:1'],
        'bank_names.*' => ['required', 'string'],
        'payment_account_number' => [
          'required',
          Rule::unique('program_vendor_configurations')->where(function ($query) use ($payment_account_numbers) {
            return $query->whereNotIn('payment_account_number', $payment_account_numbers);
          }),
        ],
        'vendor_user_name' => [
          'sometimes',
          'required_with:vendor_user_email,vendor_user_country_code,vendor_user_phone_number,vendor_user_role,vendor_user_receive_notifications',
        ],
        'vendor_user_email' => [
          'sometimes',
          'required_with:vendor_user_name,vendor_user_country_code,vendor_user_phone_number,vendor_user_role,vendor_user_receive_notifications',
        ],
        'vendor_user_country_code' => [
          'sometimes',
          'required_with:vendor_user_name,vendor_user_email,vendor_user_phone_number,vendor_user_role,vendor_user_receive_notifications',
        ],
        'vendor_user_phone_number' => [
          'sometimes',
          'required_with:vendor_user_name,vendor_user_email,vendor_user_country_code,vendor_user_role,vendor_user_receive_notifications',
          'unique_phone_number:' . $request->vendor_user_country_code,
        ],
        'vendor_user_role' => [
          'sometimes',
          'required_with:vendor_user_name,vendor_user_email,vendor_user_country_code,vendor_user_phone_number,vendor_user_receive_notifications',
        ],
        'vendor_user_receive_notifications' => [
          'sometimes',
          'required_with:vendor_user_name,vendor_user_email,vendor_user_country_code,vendor_user_phone_number,vendor_user_role',
        ],
      ],
      [
        'bank_names_as_per_banks.required' => 'Please enter at least one bank name as per banks.',
        'bank_names_as_per_banks.min' => 'Please select at least one bank name as per banks.',
        'bank_names_as_per_banks.*.required' => 'The Account Name field is required.',
        'account_numbers.required' => 'Please enter at least one account number.',
        'account_numbers.min' => 'Please enter at least one account number.',
        'account_numbers.*.required' => 'The account number is required.',
        'bank_names.required' => 'Please select at least one bank.',
        'bank_names.min' => 'Please select at least one bank.',
        'bank_names.*.required' => 'The bank name is required.',
        'vendor_user_name.required_with' => 'This field is required for the user details.',
        'vendor_user_email.required_with' => 'This field is required for the user details.',
        'vendor_user_country_code.required_with' => 'This field is required for the user details.',
        'vendor_user_phone_number.required_with' => 'This field is required for the user details.',
        'vendor_user_role.required_with' => 'This field is required for the user details.',
        'vendor_user_receive_notifications.required_with' => 'This field is required for the user details.',
      ]
    );

    try {
      DB::beginTransaction();

      $bank_users = BankUser::where('bank_id', $bank->id)
        ->whereHas('user', function ($query) {
          $query->whereHas('roles', function ($query) {
            $query->whereHas('permissions', function ($query) {
              $query->where(function ($query) {
                $query
                  ->where('name', 'Add/Edit Program & Mapping')
                  ->orWhere('name', 'Activate/Deactivate Program & Mapping');
              });
            });
          });
        })
        ->where('user_id', '!=', auth()->id())
        ->where('active', true)
        ->get();

      $dealer_role = ProgramRole::where('name', 'dealer')->first();

      $dealer = Company::find($request->dealer_id);

      ProgramCompanyRole::firstOrCreate([
        'program_id' => $program->id,
        'company_id' => $dealer->id,
        'role_id' => $dealer_role->id,
      ]);

      $dealer->programConfigurations()->create([
        'program_id' => $program->id,
        'payment_account_number' => $request->payment_account_number,
        'sanctioned_limit' => Str::replace(',', '', $request->sanctioned_limit),
        'limit_approved_date' => $request->limit_approved_date,
        'limit_expiry_date' =>
          $request->has('limit_expiry_date') && !empty($request->limit_expiry_date)
            ? Carbon::parse($request->limit_expiry_date)->format('Y-m-d')
            : null,
        'limit_review_date' =>
          $request->has('limit_review_date') && !empty($request->limit_review_date)
            ? Carbon::parse($request->limit_review_date)->format('Y-m-d')
            : null,
        'drawing_power' => Str::replace(',', '', $request->drawing_power),
        'request_auto_finance' => $request->request_auto_finance,
        'auto_approve_finance' => $request->auto_approve_finance,
        'eligibility' => $request->eligibility,
        'invoice_margin' => $request->invoice_margin,
        'schema_code' => $request->schema_code,
        'product_description' => $request->product_description,
        'vendor_code' => $request->vendor_code,
        'gst_number' => $request->gst_number,
        'classification' => $request->classification,
        'payment_terms' => $program->default_payment_terms,
        'tds' => $request->tds,
        'status' => 'inactive',
        'created_by' => auth()->id(),
        'is_approved' => false,
      ]);

      foreach ($request->from_day as $key => $from_day) {
        if (
          array_key_exists($key, $request->to_day) &&
          array_key_exists($key, $request->dealer_business_strategy_spread) &&
          array_key_exists($key, $request->dealer_credit_spread) &&
          array_key_exists($key, $request->dealer_total_spread) &&
          array_key_exists($key, $request->dealer_total_roi)
        ) {
          $dealer->programDiscountDetails()->create([
            'program_id' => $program->id,
            'benchmark_title' => $request->benchmark_title ?? $program->discountDetails?->first()?->benchmark_title,
            'benchmark_rate' => $request->benchmark_rate,
            'from_day' => $from_day,
            'to_day' => $request->to_day[$key],
            'business_strategy_spread' => $request->dealer_business_strategy_spread[$key],
            'credit_spread' => $request->dealer_credit_spread[$key],
            'total_spread' => $request->dealer_total_spread[$key],
            'total_roi' => $request->dealer_total_roi[$key],
            'limit_block_overdue_days' => $request->limit_block_overdue_days,
            'penal_discount_on_principle' => $request->penal_discount_on_principle,
            'grace_period' => $request->grace_period,
            'grace_period_discount' => $request->grace_period_discount,
            'maturity_handling_on_holidays' => $request->maturity_handling_on_holidays,
            'discount_on_posted_discount_spread' => $request->discount_posted_spread,
            'discount_on_posted_discount' => $request->discount_posted,
          ]);
        }
      }

      $fee_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
        ->where('product_code_id', null)
        ->where('product_type_id', $program->program_type_id)
        ->where('name', 'Fee Income Account')
        ->first();

      if (
        $request->has('fee_names') &&
        count($request->fee_names) > 0 &&
        $request->has('fee_types') &&
        count($request->fee_types) > 0 &&
        $request->has('fee_values') &&
        count($request->fee_values) > 0
      ) {
        foreach ($request->fee_names as $key => $value) {
          $dealer->programFeeDetails()->create([
            'program_id' => $program->id,
            'fee_name' => $value,
            'type' => array_key_exists($key, $request->fee_types) ? $request->fee_types[$key] : null,
            'value' => array_key_exists($key, $request->fee_values) ? $request->fee_values[$key] : null,
            'per_amount' =>
              array_key_exists($key, $request->fee_per_amount) && !empty($request->fee_per_amount[$key])
                ? $request->fee_per_amount[$key]
                : null,
            'dealer_bearing' => 100,
            'taxes' => array_key_exists($key, $request->taxes) ? $request->taxes[$key] : null,
            'charge_type' =>
              $request->has('charge_types') &&
              !empty($request->charge_types) &&
              count($request->charge_types) > 0 &&
              array_key_exists($key, $request->charge_types)
                ? $request->charge_types[$key]
                : 'fixed',
            'account_number' =>
              $request->has('fee_account_numbers') &&
              !empty($request->fee_account_numbers) &&
              count($request->fee_account_numbers) > 0 &&
              array_key_exists($key, $request->fee_account_numbers)
                ? $request->fee_account_numbers[$key]
                : $fee_income_account->value,
          ]);
        }
      }

      if (
        $request->has('vendor_user_name') &&
        !empty($request->vendor_user_name) &&
        $request->has('vendor_user_email') &&
        !empty($request->vendor_user_email) &&
        $request->has('vendor_user_country_code') &&
        !empty($request->vendor_user_country_code) &&
        $request->has('vendor_user_phone_number') &&
        !empty($request->vendor_user_phone_number) &&
        $request->has('vendor_user_role') &&
        !empty($request->vendor_user_role)
      ) {
        $user = User::where('email', $request->vendor_user_email)
          ->orWhere('phone_number', $request->vendor_user_country_code . '' . $request->vendor_user_phone_number)
          ->first();
        if ($user) {
          $is_bank_user = BankUser::where('user_id', $user->id)->first();
          if (!$is_bank_user) {
            CompanyUser::create([
              'company_id' => $dealer->id,
              'user_id' => $user->id,
            ]);

            $permission_data = PermissionData::find($request->vendor_user_role);

            // Assign Role
            $role = Role::where('name', $permission_data->RoleName)
              ->where('guard_name', 'web')
              ->first();

            if ($role) {
              $user->assignRole($role);
            }

            // $link['Dealer Dashboard'] = config('app.url');

            // SendMail::dispatchAfterResponse($user->email, 'MappedToCompany', [
            //   'type' => 'Company',
            //   'data' => ['company' => $dealer->name, 'name' => $user->name, 'links' => $link],
            // ]);
          }
        } else {
          $user = User::create([
            'email' => $request->vendor_user_email,
            'name' => $request->vendor_user_name,
            'phone_number' => $request->vendor_user_country_code . '' . $request->vendor_user_phone_number,
            'password' => Hash::make('Secret!'),
            'receive_notifications' => $request->vendor_user_receive_notifications,
            'module' => 'company',
          ]);

          CompanyUser::create([
            'company_id' => $dealer->id,
            'user_id' => $user->id,
          ]);

          $permission_data = PermissionData::find($request->vendor_user_role);

          // Assign Role
          $role = Role::where('name', $permission_data->RoleName)
            ->where('guard_name', 'web')
            ->first();

          if ($role) {
            $user->assignRole($role);
          }

          // $link['Dealer Dashboard'] = URL::temporarySignedRoute('auth.reset-password', now()->addHour(), ['id' => $user->id]);

          // SendMail::dispatchAfterResponse($user->email, 'MappedToCompany', [
          //   'type' => 'Company',
          //   'data' => ['company' => $dealer->name, 'name' => $user->name, 'links' => $link],
          // ]);
        }
      }

      if (
        $request->has('bank_names_as_per_banks') &&
        !empty($request->bank_names_as_per_banks[0]) &&
        count($request->bank_names_as_per_banks) > 0 &&
        $request->has('account_numbers') &&
        !empty($request->account_numbers[0]) &&
        count($request->account_numbers) > 0 &&
        $request->has('bank_names') &&
        !empty($request->bank_names[0]) &&
        count($request->bank_names) > 0
      ) {
        foreach ($request->bank_names_as_per_banks as $key => $value) {
          $dealer->programBankDetails()->create([
            'program_id' => $program->id,
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

      $dealer->notify(new NewProgramMapping($program, 'dealer'));

      activity($bank->id)
        ->causedBy(auth()->user())
        ->performedOn($program)
        ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
        ->log('mapped ' . $dealer->name . ' as dealer');

      DB::commit();

      if ($bank_users->count() > 0) {
        // Notify users of changes
        foreach ($bank_users as $bank_user) {
          SendMail::dispatchAfterResponse($bank_user->user->email, 'ProgramChanged', [
            'program' => $program->id,
            'url' => route('programs.show', ['bank' => $bank, 'program' => $program]),
            'name' => auth()->user()->name,
            'type' => 'vendor_financing',
          ]);
          $bank_user->user->notify(new ProgramUpdation($program));
        }

        toastr()->success('', 'Mapping sent for approval');
      } else {
        toastr()->success('', 'Dealer mapped successfully.');
      }

      return redirect()->route('programs.show', ['bank' => $bank, 'program' => $program]);
    } catch (\Throwable $th) {
      info($th);
      DB::rollback();
      toastr()->error('', 'An error occurred while mapping the company.');
      return back();
    }
  }

  public function updateMapDealer(Request $request, Bank $bank, Program $program, Company $company)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Add/Edit Program & Mapping')
    ) {
      toastr()->error('', 'You do not have the permission to perform this action');
      return back();
    }

    // Validator for unique phone numbers when request has country code
    Validator::extend('unique_phone_number', function ($attribute, $value, $parameters, $validator) {
      return !User::where('phone_number', $parameters[0] . '' . substr($value, -9))->exists();
    });

    // Get Current Payment Account Numbers
    $payment_account_numbers = ProgramVendorConfiguration::whereHas('program', function ($query) use ($bank) {
      $query->where('bank_id', $bank->id);
    })
      ->where('company_id', '!=', $company->id)
      ->pluck('payment_account_number');

    $request->validate(
      [
        'payment_account_number' => [
          'required',
          Rule::unique('program_vendor_configurations')
            ->ignore($company->id, 'company_id')
            ->where(function ($query) use ($payment_account_numbers) {
              $query->whereNotIn('payment_account_number', $payment_account_numbers);
            }),
        ],
        'sanctioned_limit' => ['required'],
        'limit_approved_date' => ['required'],
        // 'limit_review_date' => ['required'],
        'request_auto_finance' => ['required'],
        'auto_approve_finance' => ['required'],
        'payment_account_number' => ['required'],
        'eligibility' => ['required'],
        'status' => ['required'],
        'benchmark_title' => ['required'],
        'benchmark_rate' => ['required'],
        'dealer_business_strategy_spread' => ['required'],
        'dealer_credit_spread' => ['required'],
        'dealer_total_roi' => ['required'],
        'penal_discount_on_principle' => ['required'],
        'grace_period' => ['required'],
        'grace_period_discount' => ['required'],
        'account_numbers' => ['required', 'array', 'min:1'],
        'bank_names_as_per_banks' => ['required', 'array', 'min:1'],
        'vendor_user_name' => [
          'sometimes',
          'required_with:vendor_user_email,vendor_user_country_code,vendor_user_phone_number,vendor_user_role,vendor_user_receive_notifications',
        ],
        'vendor_user_email' => [
          'sometimes',
          'required_with:vendor_user_name,vendor_user_country_code,vendor_user_phone_number,vendor_user_role,vendor_user_receive_notifications',
        ],
        'vendor_user_country_code' => [
          'sometimes',
          'required_with:vendor_user_name,vendor_user_email,vendor_user_phone_number,vendor_user_role,vendor_user_receive_notifications',
        ],
        'vendor_user_phone_number' => [
          'sometimes',
          'required_with:vendor_user_name,vendor_user_email,vendor_user_country_code,vendor_user_role,vendor_user_receive_notifications',
          'unique_phone_number:' . $request->vendor_user_country_code,
        ],
        'vendor_user_role' => [
          'sometimes',
          'required_with:vendor_user_name,vendor_user_email,vendor_user_country_code,vendor_user_phone_number,vendor_user_receive_notifications',
        ],
        'vendor_user_receive_notifications' => [
          'sometimes',
          'required_with:vendor_user_name,vendor_user_email,vendor_user_country_code,vendor_user_phone_number,vendor_user_role',
        ],
      ],
      [
        'vendor_user_name.required_with' => 'This field is required for the user details.',
        'vendor_user_email.required_with' => 'This field is required for the user details.',
        'vendor_user_country_code.required_with' => 'This field is required for the user details.',
        'vendor_user_phone_number.required_with' => 'This field is required for the user details.',
        'vendor_user_role.required_with' => 'This field is required for the user details.',
        'vendor_user_receive_notifications.required_with' => 'This field is required for the user details.',
        'vendor_user_phone_number.unique_phone_number' => 'This phone number is already in use',
      ]
    );

    $bank_users = BankUser::where('bank_id', $bank->id)
      ->whereHas('user', function ($query) {
        $query->whereHas('roles', function ($query) {
          $query->whereHas('permissions', function ($query) {
            $query->where(function ($query) {
              $query
                ->where('name', 'Add/Edit Program & Mapping')
                ->orWhere('name', 'Activate/Deactivate Program & Mapping');
            });
          });
        });
      })
      ->where('user_id', '!=', auth()->id())
      ->where('active', true)
      ->get();

    try {
      DB::beginTransaction();
      $update_data = [];
      if ($bank_users->count() > 0) {
        ProgramMappingChange::where('program_id', $program->id)
          ->where('company_id', $company->id)
          ->delete();
        $new_mapping_change = new ProgramMappingChange();
        $new_mapping_change->program_id = $program->id;
        $new_mapping_change->company_id = $company->id;
        $new_mapping_change->user_id = auth()->id();
        $update_data['vendor_configuration'] = [
          'payment_account_number' => $request->payment_account_number,
          'sanctioned_limit' => Str::replace(',', '', $request->sanctioned_limit),
          'limit_approved_date' => $request->limit_approved_date,
          'limit_expiry_date' => $request->limit_expiry_date,
          'limit_review_date' =>
            $request->has('limit_review_date') && !empty($request->limit_review_date)
              ? Carbon::parse($request->limit_review_date)->format('Y-m-d')
              : null,
          'drawing_power' => Str::replace(',', '', $request->drawing_power),
          'request_auto_finance' => $request->request_auto_finance,
          'auto_approve_finance' => $request->auto_approve_finance,
          'eligibility' => $request->eligibility,
          'invoice_margin' => $request->invoice_margin,
          'schema_code' => $request->schema_code,
          'product_description' => $request->product_description,
          'vendor_code' => $request->vendor_code,
          'gst_number' => $request->gst_number,
          'classification' => $request->classification,
          'tds' => $request->tds,
          'status' => $request->status,
        ];

        foreach ($request->from_day as $key => $from_day) {
          if (
            array_key_exists($key, $request->to_day) &&
            array_key_exists($key, $request->dealer_business_strategy_spread) &&
            array_key_exists($key, $request->dealer_credit_spread) &&
            array_key_exists($key, $request->dealer_total_spread) &&
            array_key_exists($key, $request->dealer_total_roi)
          ) {
            $update_data['vendor_discount_details'][$key] = [
              'benchmark_title' => $request->benchmark_title,
              'benchmark_rate' => $request->benchmark_rate,
              'from_day' => $from_day,
              'to_day' => $request->to_day[$key],
              'business_strategy_spread' => $request->dealer_business_strategy_spread[$key],
              'credit_spread' => $request->dealer_credit_spread[$key],
              'total_spread' => $request->dealer_total_spread[$key],
              'total_roi' => $request->dealer_total_roi[$key],
              'limit_block_overdue_days' => $request->limit_block_overdue_days,
              'penal_discount_on_principle' => $request->penal_discount_on_principle,
              'grace_period' => $request->grace_period,
              'grace_period_discount' => $request->grace_period_discount,
              'maturity_handling_on_holidays' => $request->maturity_handling_on_holidays,
              'discount_on_posted_discount_spread' => $request->discount_posted_spread,
              'discount_on_posted_discount' => $request->discount_posted,
            ];
          }
        }

        $fee_income_account = BankProductsConfiguration::where('bank_id', $bank->id)
          ->where('product_code_id', null)
          ->where('product_type_id', $program->program_type_id)
          ->where('name', 'Fee Income Account')
          ->first();

        if (
          $request->has('fee_names') &&
          count($request->fee_names) > 0 &&
          $request->has('fee_types') &&
          count($request->fee_types) > 0 &&
          $request->has('fee_values') &&
          count($request->fee_values) > 0
        ) {
          foreach ($request->fee_names as $key => $value) {
            if (!empty($value) && !empty($request->fee_values[$key]) && !empty($request->fee_types[$key])) {
              $update_data['vendor_fee_details'][$key] = [
                'fee_name' => $value,
                'type' => array_key_exists($key, $request->fee_types) ? $request->fee_types[$key] : null,
                'value' => array_key_exists($key, $request->fee_values) ? $request->fee_values[$key] : null,
                'per_amount' =>
                  array_key_exists($key, $request->fee_per_amount) && !empty($request->fee_per_amount[$key])
                    ? $request->fee_per_amount[$key]
                    : null,
                'dealer_bearing' => array_key_exists($key, $request->fee_dealer_bearing)
                  ? $request->fee_dealer_bearing[$key]
                  : null,
                'taxes' => array_key_exists($key, $request->taxes) ? $request->taxes[$key] : null,
                'charge_type' =>
                  $request->has('charge_types') &&
                  !empty($request->charge_types) &&
                  count($request->charge_types) > 0 &&
                  array_key_exists($key, $request->charge_types)
                    ? $request->charge_types[$key]
                    : 'fixed',
                'account_number' =>
                  $request->has('fee_account_numbers') &&
                  !empty($request->fee_account_numbers) &&
                  count($request->fee_account_numbers) > 0 &&
                  array_key_exists($key, $request->fee_account_numbers)
                    ? $request->fee_account_numbers[$key]
                    : $fee_income_account->value,
              ];
            }
          }
        }

        if (
          $request->has('bank_names_as_per_banks') &&
          count($request->bank_names_as_per_banks) > 0 &&
          $request->has('account_numbers') &&
          count($request->account_numbers) > 0 &&
          $request->has('bank_names') &&
          count($request->bank_names) > 0
        ) {
          foreach ($request->bank_names_as_per_banks as $key => $value) {
            if (
              !empty($value) &&
              !empty($request->account_numbers[$key]) &&
              !empty($request->bank_names[$key]) &&
              !empty($request->bank_names_as_per_banks[$key])
            ) {
              $update_data['vendor_bank_details'][$key] = [
                'name_as_per_bank' => $value,
                'account_number' => array_key_exists($key, $request->account_numbers)
                  ? $request->account_numbers[$key]
                  : null,
                'bank_name' => array_key_exists($key, $request->bank_names) ? $request->bank_names[$key] : null,
                'branch' => array_key_exists($key, $request->branches) ? $request->branches[$key] : null,
                'swift_code' => array_key_exists($key, $request->swift_codes) ? $request->swift_codes[$key] : null,
                'account_type' => array_key_exists($key, $request->account_types)
                  ? $request->account_types[$key]
                  : null,
              ];
            }
          }
        }

        $new_mapping_change->changes = $update_data;
        $new_mapping_change->save();

        // // Remove rejected indicator from configuration
        $vendor_config = ProgramVendorConfiguration::where('company_id', $company->id)
          ->where('program_id', $program->id)
          ->first();
        if ($vendor_config && $vendor_config->rejected_by && $vendor_config->rejection_reason) {
          $vendor_config->update([
            'rejected_by' => null,
            'rejection_reason' => null,
          ]);
        }

        toastr()->success('', 'Mapping Changes sent for approval');
      } else {
        $company
          ->programConfigurations()
          ->where('program_id', $program->id)
          ->delete();
        $company->programConfigurations()->create([
          'program_id' => $program->id,
          'payment_account_number' => $request->payment_account_number,
          'sanctioned_limit' => Str::replace(',', '', $request->sanctioned_limit),
          'limit_approved_date' => $request->limit_approved_date,
          'limit_expiry_date' => $request->limit_expiry_date,
          'limit_review_date' =>
            $request->has('limit_review_date') && !empty($request->limit_review_date)
              ? Carbon::parse($request->limit_review_date)->format('Y-m-d')
              : null,
          'drawing_power' => $request->drawing_power,
          'request_auto_finance' => $request->request_auto_finance,
          'auto_approve_finance' => $request->auto_approve_finance,
          'eligibility' => $request->eligibility,
          'invoice_margin' => $request->invoice_margin,
          'schema_code' => $request->schema_code,
          'product_description' => $request->product_description,
          'vendor_code' => $request->vendor_code,
          'gst_number' => $request->gst_number,
          'classification' => $request->classification,
          'tds' => $request->tds,
          'status' => $request->status,
        ]);

        $company
          ->programDiscountDetails()
          ->where('program_id', $program->id)
          ->delete();
        foreach ($request->from_day as $key => $from_day) {
          if (
            array_key_exists($key, $request->to_day) &&
            array_key_exists($key, $request->dealer_business_strategy_spread) &&
            array_key_exists($key, $request->dealer_credit_spread) &&
            array_key_exists($key, $request->dealer_total_spread) &&
            array_key_exists($key, $request->dealer_total_roi)
          ) {
            $company->programDiscountDetails()->create([
              'program_id' => $program->id,
              'benchmark_title' => $request->benchmark_title,
              'benchmark_rate' => $request->benchmark_rate,
              'from_day' => $from_day,
              'to_day' => $request->to_day[$key],
              'business_strategy_spread' => $request->dealer_business_strategy_spread[$key],
              'credit_spread' => $request->dealer_credit_spread[$key],
              'total_spread' => $request->dealer_total_spread[$key],
              'total_roi' => $request->dealer_total_roi[$key],
              'limit_block_overdue_days' => $request->limit_block_overdue_days,
              'penal_discount_on_principle' => $request->penal_discount_on_principle,
              'grace_period' => $request->grace_period,
              'grace_period_discount' => $request->grace_period_discount,
              'maturity_handling_on_holidays' => $request->maturity_handling_on_holidays,
              'discount_on_posted_discount_spread' => $request->discount_posted_spread,
              'discount_on_posted_discount' => $request->discount_posted,
            ]);
          }
        }

        $company
          ->programFeeDetails()
          ->where('program_id', $program->id)
          ->delete();
        if (
          $request->has('fee_names') &&
          count($request->fee_names) > 0 &&
          $request->has('fee_types') &&
          count($request->fee_types) > 0 &&
          $request->has('fee_values') &&
          count($request->fee_values) > 0
        ) {
          foreach ($request->fee_names as $key => $value) {
            $company->programFeeDetails()->create([
              'program_id' => $program->id,
              'fee_name' => $value,
              'type' => array_key_exists($key, $request->fee_types) ? $request->fee_types[$key] : null,
              'value' => array_key_exists($key, $request->fee_values) ? $request->fee_values[$key] : null,
              'per_amount' =>
                array_key_exists($key, $request->fee_per_amount) && !empty($request->fee_per_amount[$key])
                  ? $request->fee_per_amount[$key]
                  : null,
              'dealer_bearing' => array_key_exists($key, $request->fee_dealer_bearing_discount)
                ? $request->fee_dealer_bearing_discount[$key]
                : null,
              'taxes' => array_key_exists($key, $request->taxes) ? $request->taxes[$key] : null,
            ]);
          }
        }

        $company
          ->programBankDetails()
          ->where('program_id', $program->id)
          ->delete();
        if (
          $request->has('bank_names_as_per_banks') &&
          count($request->bank_names_as_per_banks) > 0 &&
          $request->has('account_numbers') &&
          count($request->account_numbers) > 0 &&
          $request->has('bank_names') &&
          count($request->bank_names) > 0
        ) {
          foreach ($request->bank_names_as_per_banks as $key => $value) {
            $company->programBankDetails()->create([
              'program_id' => $program->id,
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

        activity($bank->id)
          ->causedBy(auth()->user())
          ->performedOn($program)
          ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
          ->log('updated mapping for ' . $company->name . ' as dealer');

        toastr()->success('', 'Mapping updated successfully.');
      }

      if (
        $request->has('vendor_user_name') &&
        !empty($request->vendor_user_name) &&
        $request->has('vendor_user_email') &&
        !empty($request->vendor_user_email) &&
        $request->has('vendor_user_country_code') &&
        !empty($request->vendor_user_country_code) &&
        $request->has('vendor_user_phone_number') &&
        !empty($request->vendor_user_phone_number) &&
        $request->has('vendor_user_role') &&
        !empty($request->vendor_user_role)
      ) {
        $user = User::where('email', $request->vendor_user_email)
          ->orWhere('phone_number', $request->vendor_user_country_code . '' . $request->vendor_user_phone_number)
          ->first();
        if ($user) {
          $is_bank_user = BankUser::where('user_id', $user->id)->first();
          if (!$is_bank_user) {
            CompanyUser::create([
              'company_id' => $company->id,
              'user_id' => $user->id,
            ]);

            $permission_data = PermissionData::find($request->vendor_user_role);

            // Assign Role
            $role = Role::where('name', $permission_data->RoleName)
              ->where('guard_name', 'web')
              ->first();

            if ($role) {
              $user->assignRole($role);
            }

            // $link['Dealer Dashboard'] = config('app.url');

            // SendMail::dispatchAfterResponse($user->email, 'MappedToCompany', [
            //   'type' => 'Company',
            //   'data' => ['company' => $company->name, 'name' => $user->name, 'links' => $link],
            // ]);
          }
        } else {
          $user = User::create([
            'email' => $request->vendor_user_email,
            'name' => $request->vendor_user_name,
            'phone_number' => $request->vendor_user_country_code . '' . $request->vendor_user_phone_number,
            'password' => Hash::make('Secret!'),
            'receive_notifications' => $request->vendor_user_receive_notifications,
            'module' => 'company',
          ]);

          CompanyUser::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
          ]);

          $permission_data = PermissionData::find($request->vendor_user_role);

          // Assign Role
          $role = Role::where('name', $permission_data->RoleName)
            ->where('guard_name', 'web')
            ->first();

          if ($role) {
            $user->assignRole($role);
          }

          // $link['Dealer Dashobard'] = URL::temporarySignedRoute('auth.reset-password', now()->addHour(), ['id' => $user->id]);

          // SendMail::dispatchAfterResponse($user->email, 'MappedToCompany', [
          //   'type' => 'Company',
          //   'data' => ['company' => $company->name, 'name' => $user->name, 'links' => $link],
          // ]);
        }
      }

      DB::commit();

      return redirect()->route('programs.show', ['bank' => $bank, 'program' => $program]);
    } catch (\Throwable $th) {
      info($th);
      DB::rollback();
      toastr()->error('', 'An error occurred while updating the mapping');
      return back();
    }
  }

  public function approveProgramConfigurationChanges(Bank $bank, Program $program, Company $company, $status)
  {
    if ($status == 'approve') {
      $program_mapping_changes = ProgramMappingChange::where('program_id', $program->id)
        ->where('company_id', $company->id)
        ->get();

      if ($program->programType->name == Program::VENDOR_FINANCING) {
        if ($program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
          foreach ($program_mapping_changes as $program_mapping_change) {
            foreach ($program_mapping_change->changes as $key => $change) {
              if ($key == 'vendor_configuration') {
                $vendor_configuration = ProgramVendorConfiguration::where('company_id', $company->id)
                  ->where('program_id', $program->id)
                  ->first();
                foreach ($change as $column => $setting) {
                  $vendor_configuration->update([$column => $setting]);
                }
                if ($vendor_configuration->rejected_by && $vendor_configuration->rejection_reason) {
                  $vendor_configuration->update([
                    'is_approved' => true,
                    'status' => 'active',
                    'rejected_by' => null,
                    'rejection_reason' => null,
                  ]);
                }
              }
              if ($key == 'vendor_discount_details') {
                $vendor_discount_details = ProgramVendorDiscount::where('company_id', $company->id)
                  ->where('program_id', $program->id)
                  ->first();
                foreach ($change as $column => $setting) {
                  $vendor_discount_details->update([$column => $setting]);
                }
              }
              if ($key == 'vendor_fee_details') {
                $vendor_fee_details = ProgramVendorFee::where('company_id', $company->id)
                  ->where('program_id', $program->id)
                  ->delete();
                foreach ($change as $setting_change) {
                  $vendor_fee_details = new ProgramVendorFee();
                  $vendor_fee_details->program_id = $program->id;
                  $vendor_fee_details->company_id = $company->id;
                  foreach ($setting_change as $column => $setting) {
                    $vendor_fee_details->$column = $setting;
                  }
                  $vendor_fee_details->save();
                }
              }
              if ($key == 'vendor_bank_details') {
                $vendor_bank_details = ProgramVendorBankDetail::where('company_id', $company->id)
                  ->where('program_id', $program->id)
                  ->delete();
                foreach ($change as $setting_change) {
                  $vendor_bank_details = new ProgramVendorBankDetail();
                  $vendor_bank_details->program_id = $program->id;
                  $vendor_bank_details->company_id = $company->id;
                  foreach ($setting_change as $column => $setting) {
                    $vendor_bank_details->$column = $setting;
                  }
                  $vendor_bank_details->save();
                }
              }
            }
            activity($bank->id)
              ->causedBy($program_mapping_change->user)
              ->performedOn($program)
              ->withProperties([
                'ip' => request()->ip(),
                'device_info' => request()->userAgent(),
                'user_type' => 'Bank',
              ])
              ->log('updated mapping ' . $company->name . ' as a vendor');
            $program_mapping_change->delete();
          }
        } else {
          foreach ($program_mapping_changes as $program_mapping_change) {
            foreach ($program_mapping_change->changes as $key => $change) {
              if ($key == 'vendor_configuration') {
                $vendor_configuration = ProgramVendorConfiguration::where('buyer_id', $company->id)
                  ->where('program_id', $program->id)
                  ->first();
                foreach ($change as $column => $setting) {
                  $vendor_configuration->update([$column => $setting]);
                }
              }
              if ($key == 'vendor_discount_details') {
                $vendor_discount_details = ProgramVendorDiscount::where('buyer_id', $company->id)
                  ->where('program_id', $program->id)
                  ->first();
                foreach ($change as $column => $setting) {
                  $vendor_discount_details->update([$column => $setting]);
                }
              }
              if ($key == 'vendor_fee_details') {
                $vendor_fee_details = ProgramVendorFee::where('buyer_id', $company->id)
                  ->where('program_id', $program->id)
                  ->delete();
                foreach ($change as $setting_change) {
                  $vendor_fee_details = new ProgramVendorFee();
                  $vendor_fee_details->program_id = $program->id;
                  $vendor_fee_details->company_id = $program->anchor->id;
                  $vendor_fee_details->buyer_id = $company->id;
                  foreach ($setting_change as $column => $setting) {
                    $vendor_fee_details->$column = $setting;
                  }
                  $vendor_fee_details->save();
                }
              }
              if ($key == 'vendor_bank_details') {
                $vendor_bank_details = ProgramVendorBankDetail::where('buyer_id', $company->id)
                  ->where('program_id', $program->id)
                  ->delete();
                foreach ($change as $setting_change) {
                  $vendor_bank_details = new ProgramVendorBankDetail();
                  $vendor_bank_details->program_id = $program->id;
                  $vendor_bank_details->company_id = $program->anchor->id;
                  $vendor_bank_details->buyer_id = $company->id;
                  foreach ($setting_change as $column => $setting) {
                    $vendor_bank_details->$column = $setting;
                  }
                  $vendor_bank_details->save();
                }
              }
            }
            activity($bank->id)
              ->causedBy($program_mapping_change->user)
              ->performedOn($program)
              ->withProperties([
                'ip' => request()->ip(),
                'device_info' => request()->userAgent(),
                'user_type' => 'Bank',
              ])
              ->log('updated mapping ' . $company->name . ' as a vendor');
            $program_mapping_change->delete();
          }
        }
      } else {
        foreach ($program_mapping_changes as $program_mapping_change) {
          foreach ($program_mapping_change->changes as $key => $change) {
            if ($key == 'vendor_configuration') {
              $vendor_configuration = ProgramVendorConfiguration::where('company_id', $company->id)
                ->where('program_id', $program->id)
                ->first();
              foreach ($change as $column => $setting) {
                $vendor_configuration->update([$column => $setting]);
              }
            }
            if ($key == 'vendor_discount_details') {
              $vendor_discount_details = ProgramVendorDiscount::where('company_id', $company->id)
                ->where('program_id', $program->id)
                ->delete();
              foreach ($change as $setting_change) {
                $vendor_discount_details = new ProgramVendorDiscount();
                $vendor_discount_details->program_id = $program->id;
                $vendor_discount_details->company_id = $company->id;
                foreach ($setting_change as $column => $setting) {
                  $vendor_discount_details->$column = $setting;
                }
                $vendor_discount_details->save();
              }
            }
            if ($key == 'vendor_fee_details') {
              $vendor_fee_details = ProgramVendorFee::where('company_id', $company->id)
                ->where('program_id', $program->id)
                ->delete();
              foreach ($change as $setting_change) {
                $vendor_fee_details = new ProgramVendorFee();
                $vendor_fee_details->program_id = $program->id;
                $vendor_fee_details->company_id = $company->id;
                foreach ($setting_change as $column => $setting) {
                  $vendor_fee_details->$column = $setting;
                }
                $vendor_fee_details->save();
              }
            }
            if ($key == 'vendor_bank_details') {
              $vendor_bank_details = ProgramVendorBankDetail::where('company_id', $company->id)
                ->where('program_id', $program->id)
                ->delete();
              foreach ($change as $setting_change) {
                $vendor_bank_details = new ProgramVendorBankDetail();
                $vendor_bank_details->program_id = $program->id;
                $vendor_bank_details->company_id = $company->id;
                foreach ($setting_change as $column => $setting) {
                  $vendor_bank_details->$column = $setting;
                }
                $vendor_bank_details->save();
              }
            }
          }
          activity($bank->id)
            ->causedBy($program_mapping_change->user)
            ->performedOn($program)
            ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Bank'])
            ->log('updated mapping ' . $company->name . ' as a vendor');
          $program_mapping_change->delete();
        }
      }
      // Send Notification to new users
      $users = $company->users->where('last_login', null);

      foreach ($users as $user) {
        $link['Dashobard'] = URL::temporarySignedRoute('auth.reset-password', now()->addHour(), ['id' => $user->id]);

        SendMail::dispatchAfterResponse($user->email, 'MappedToCompany', [
          'type' => 'Company',
          'data' => ['company' => $company->name, 'name' => $user->name, 'links' => $link],
        ]);
      }
    } else {
      ProgramMappingChange::where('program_id', $program->id)
        ->where('company_id', $company->id)
        ->delete();

      // Delete mapped users
      $users = $company->users->where('last_login', null);
      foreach ($users as $user) {
        $user->delete();
      }
    }

    return response()->json(['message' => 'Changes updated successfully']);
  }

  public function bulkApproveProgramConfiguration(Bank $bank, Program $program, Request $request)
  {
    if (
      !auth()
        ->user()
        ->hasPermissionTo('Add/Edit Program & Mapping')
    ) {
      toastr()->error('', 'You do not have the permission to perform this action');
      return back();
    }

    $request->validate([
      'companies' => ['required', 'array', 'min:1'],
    ]);

    foreach ($request->companies as $company_id) {
      $company = Company::find($company_id);
      if ($company) {
        $this->updateApprovalMappingStatus($request, $bank, $program, $company);
      }
    }

    if ($request->wantsJson()) {
      return response()->json(['message' => 'Program configurations approved successfully.']);
    }

    toastr()->success('', 'Program configurations approved successfully.');
    return redirect()->route('programs.show', ['bank' => $bank, 'program' => $program]);
  }

  public function blockVendorConfiguration(Bank $bank, Program $program, Company $company)
  {
    if ($program->programType->name === Program::DEALER_FINANCING) {
      $program_vendor_configuration = ProgramVendorConfiguration::where('company_id', $company->id)
        ->where('program_id', $program->id)
        ->first();
      ProgramMappingChange::where('program_id', $program->id)
        ->where('company_id', $company->id)
        ->delete();
    } else {
      if ($program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE) {
        $program_vendor_configuration = ProgramVendorConfiguration::where('company_id', $company->id)
          ->where('program_id', $program->id)
          ->first();
        ProgramMappingChange::where('program_id', $program->id)
          ->where('company_id', $company->id)
          ->delete();
      } else {
        $program_vendor_configuration = ProgramVendorConfiguration::where('buyer_id', $company->id)
          ->where('program_id', $program->id)
          ->first();
        ProgramMappingChange::where('program_id', $program->id)
          ->where('buyer_id', $company->id)
          ->delete();
      }
    }

    $new_mapping_change = new ProgramMappingChange();
    $new_mapping_change->program_id = $program->id;
    $new_mapping_change->company_id = $company->id;
    $new_mapping_change->user_id = auth()->id();
    $update_data['vendor_configuration'] = [
      'is_blocked' => !$program_vendor_configuration->is_blocked,
    ];
    $new_mapping_change->changes = $update_data;
    $new_mapping_change->save();

    return response()->json(['message' => 'OD Account change sent for approval'], 200);
  }

  public function checkProgramReview()
  {
    $mappings = ProgramVendorConfiguration::with('buyer', 'company')
      ->whereDate('limit_review_date', '<=', now()->format('Y-m-d'))
      ->get();

    foreach ($mappings as $mapping) {
      $mapping->program->bank->notify(new ProgramReview($mapping->id));
    }
  }
}
