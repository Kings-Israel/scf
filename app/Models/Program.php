<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Oddvalue\LaravelDrafts\Concerns\HasDrafts;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Program extends Model
{
  use HasFactory, HasDrafts;

  // Set program types and codes
  const VENDOR_FINANCING = 'Vendor Financing';
  const VENDOR_FINANCING_RECEIVABLE = 'Vendor Financing Receivable';
  const FACTORING_WITH_RECOURSE = 'Factoring With Recourse';
  const FACTORING_WITHOUT_RECOURSE = 'Factoring Without Recourse';
  const DEALER_FINANCING = 'Dealer Financing';

  /**
   * The attributes that should be cast to native types.
   *
   * @var array
   */
  protected $casts = [
    'request_auto_finance' => 'bool',
    'auto_debit_anchor_financed_invoices' => 'bool',
    'auto_debit_anchor_non_financed_invoices' => 'bool',
    'anchor_can_change_due_date' => 'bool',
    'anchor_can_change_payment_term' => 'bool',
    'mandatory_invoice_attachment' => 'bool',
    'is_published' => 'bool',
  ];

  /**
   * The accessors to append to the model's array form.
   *
   * @var array
   */
  protected $appends = [
    'utilized',
    'pipeline',
    'utilized_percentage_ratio',
    'anchor',
    'can_activate',
    'can_edit',
    'can_view',
    'can_approve',
    'can_delete',
  ];

  /**
   * The attributes that aren't mass assignable.
   *
   * @var array
   */
  protected $guarded = [];

  /**
   * Get the programType that owns the Program
   */
  public function programType(): BelongsTo
  {
    return $this->belongsTo(ProgramType::class);
  }

  /**
   * Get the programCode that owns the Program
   */
  public function programCode(): BelongsTo
  {
    return $this->belongsTo(ProgramCode::class);
  }

  /**
   * Get the bank that owns the Program
   */
  public function bank(): BelongsTo
  {
    return $this->belongsTo(Bank::class);
  }

  /**
   * Get all of the companies for the Program
   */
  public function companies(): HasManyThrough
  {
    return $this->hasManyThrough(Company::class, ProgramCompanyRole::class, 'program_id', 'id');
  }

  /**
   * Get all of the discountDetails for the Program
   */
  public function discountDetails(): HasMany
  {
    return $this->hasMany(ProgramDiscount::class);
  }

  /**
   * Get all of the dealerDiscountRates for the Program
   */
  public function dealerDiscountRates(): HasMany
  {
    return $this->hasMany(ProgramDealerDiscountRate::class);
  }

  /**
   * Get all of the fees for the Program
   */
  public function fees(): HasMany
  {
    return $this->hasMany(ProgramFee::class);
  }

  /**
   * Get all of the anchorDetails for the Program
   */
  public function anchorDetails(): HasMany
  {
    return $this->hasMany(ProgramAnchorDetails::class);
  }

  /**
   * Get all of the bankUserDetails for the Program
   */
  public function bankUserDetails(): HasMany
  {
    return $this->hasMany(ProgramBankUserDetails::class);
  }

  /**
   * Get all of the bankDetails for the Program
   */
  public function bankDetails(): HasMany
  {
    return $this->hasMany(ProgramBankDetails::class);
  }

  /**
   * Get all of the vendorDiscountDetails for the Program
   */
  public function vendorDiscountDetails(): HasMany
  {
    return $this->hasMany(ProgramVendorDiscount::class);
  }

  /**
   * Get all of the vendorFeeDetails for the Program
   */
  public function vendorFeeDetails(): HasMany
  {
    return $this->hasMany(ProgramVendorFee::class);
  }

  /**
   * Get all of the vendorConfigurations for the Program
   */
  public function vendorConfigurations(): HasMany
  {
    return $this->hasMany(ProgramVendorConfiguration::class);
  }

  /**
   * Get all of the vendorBankDetails for the Program
   */
  public function vendorBankDetails(): HasMany
  {
    return $this->hasMany(ProgramVendorBankDetail::class);
  }

  /**
   * Get all of the vendorContactDetails for the Program
   */
  public function vendorContactDetails(): HasMany
  {
    return $this->hasMany(ProgramVendorContactDetail::class);
  }

  /**
   * Get the utilized amount
   *
   * @param  string  $value
   * @return string
   */
  public function getUtilizedAmountAttribute($value)
  {
    if ($value < 0) {
      return 0;
    }

    return $value;
  }

  /**
   * Get the pipeline amount
   *
   * @param  string  $value
   * @return string
   */
  public function getPipelineAmountAttribute($value)
  {
    if ($value < 0) {
      return 0;
    }

    return $value;
  }

  /**
   * Get the program balance
   *
   * @param  string  $value
   * @return string
   */
  public function getBalanceAttribute()
  {
    $amount = 0;
    $invoices = Invoice::where('program_id', $this->id)
      ->where('financing_status', 'financed')
      ->where('due_date', '>', now())
      ->get();

    foreach ($invoices as $invoice) {
      $amount += $invoice->balance;
    }

    return round($amount, 2);
  }

  /**
   * Get the pending transactions total amount
   *
   * @param  string  $value
   * @return string
   */
  public function getPendingRepaymentTransactionsAmountAttribute()
  {
    $amount = CbsTransaction::whereHas('paymentRequest', function ($query) {
      $query->whereHas('invoice', function ($query) {
        $query
          ->where('program_id', $this->id)
          ->where('financing_status', 'financed')
          ->where('due_date', '>', now());
      });
    })
      ->where('status', 'Created')
      ->whereIn('transaction_type', [CbsTransaction::REPAYMENT, CbsTransaction::BANK_INVOICE_PAYMENT])
      ->sum('amount');

    return round($amount, 2);
  }

  /**
   * Get the paid transactions total amount
   *
   * @param  string  $value
   * @return string
   */
  public function getPaidRepaymentTransactionsAmountAttribute()
  {
    $amount = CbsTransaction::whereHas('paymentRequest', function ($query) {
      $query->whereHas('invoice', function ($query) {
        $query
          ->where('program_id', $this->id)
          ->where('financing_status', 'financed')
          ->where('due_date', '>', now());
      });
    })
      ->where('status', 'Successful')
      ->whereIn('transaction_type', [CbsTransaction::REPAYMENT, CbsTransaction::BANK_INVOICE_PAYMENT])
      ->sum('amount');

    return round($amount, 2);
  }

  /**
   * Get all of the invoices for the Program
   */
  public function invoices(): HasMany
  {
    return $this->hasMany(Invoice::class);
  }

  public function anchor(): HasOneThrough
  {
    $anchor_role = ProgramRole::where('name', 'anchor')->first();

    return $this->hasOneThrough(
      Company::class,
      ProgramCompanyRole::class,
      'program_id',
      'id',
      'id',
      'company_id'
    )->where('role_id', $anchor_role->id);
  }

  /**
   * Get all of the dealers for the Program
   *
   * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
   */
  public function dealers(): HasManyThrough
  {
    $anchor_role = ProgramRole::where('name', 'dealer')->first();

    return $this->hasOneThrough(
      Company::class,
      ProgramCompanyRole::class,
      'program_id',
      'id',
      'id',
      'company_id'
    )->where('role_id', $anchor_role->id);
  }

  /**
   * Get the proposedUpdate associated with the Program
   */
  public function proposedUpdate(): HasOne
  {
    return $this->hasOne(ProgramChange::class);
  }

  public function getFactoringProgramLimit(Company $company)
  {
    return $this->vendorConfigurations()
      ->where('company_id', $company->id)
      ->first()->sanctioned_limit;
  }

  public function getAnchorAttribute(): Company|NULL
  {
    $company = null;

    $anchor_role = ProgramRole::where('name', 'anchor')->first();

    $anchor = ProgramCompanyRole::where('role_id', $anchor_role->id)
      ->where('program_id', $this->id)
      ->first();

    if ($anchor) {
      $company = Company::find($anchor->company_id);
    }

    return $company;
  }

  public function getAnchor(): Company|NULL
  {
    $company = null;

    $anchor_role = ProgramRole::where('name', 'anchor')->first();

    $anchor_id = ProgramCompanyRole::where('role_id', $anchor_role->id)
      ->where('program_id', $this->id)
      ->first()->company_id;

    if ($anchor_id) {
      $company = Company::find($anchor_id);
    }

    return $company;
  }

  public function getVendors(): Collection
  {
    $vendor_role = ProgramRole::where('name', 'vendor')->first();

    $vendors_ids = ProgramCompanyRole::where('role_id', $vendor_role->id)
      ->where('program_id', $this->id)
      ->get()
      ->pluck('company_id');

    return Company::whereIn('id', $vendors_ids)->get();
  }

  public function getBuyers(): Collection
  {
    $vendor_role = ProgramRole::where('name', 'buyer')->first();

    $vendors_ids = ProgramCompanyRole::where('role_id', $vendor_role->id)
      ->where('program_id', $this->id)
      ->get()
      ->pluck('company_id');

    return Company::whereIn('id', $vendors_ids)->get();
  }

  public function getDealers(): Collection
  {
    $vendor_role = ProgramRole::where('name', 'dealer')->first();

    $vendors_ids = ProgramCompanyRole::where('role_id', $vendor_role->id)
      ->where('program_id', $this->id)
      ->get()
      ->pluck('company_id');

    return Company::whereIn('id', $vendors_ids)->get();
  }

  public function getActiveDealers(): Collection
  {
    $dealers = [];
    foreach ($this->getDealers() as $dealer) {
      if ($dealer->utilizedAmount($this) > 0) {
        array_push($dealers, $dealer);
      }
    }

    return collect($dealers);
  }

  public function getActiveVendors(): Collection
  {
    $dealers = [];
    foreach ($this->getVendors() as $dealer) {
      if ($dealer->utilizedAmount($this) > 0) {
        array_push($dealers, $dealer);
      }
    }

    return collect($dealers);
  }

  public function getActiveBuyers(): Collection
  {
    $dealers = [];
    foreach ($this->getBuyers() as $dealer) {
      if ($dealer->utilizedAmount($this) > 0) {
        array_push($dealers, $dealer);
      }
    }

    return collect($dealers);
  }

  public function getPassiveDealers(): Collection
  {
    $dealers = [];
    foreach ($this->getDealers() as $dealer) {
      if ($dealer->utilizedAmount($this) <= 0) {
        array_push($dealers, $dealer);
      }
    }

    return collect($dealers);
  }

  public function getPassiveVendors(): Collection
  {
    $dealers = [];
    foreach ($this->getVendors() as $dealer) {
      if ($dealer->utilizedAmount($this) <= 0) {
        array_push($dealers, $dealer);
      }
    }

    return collect($dealers);
  }

  public function getPassiveBuyers(): Collection
  {
    $dealers = [];
    foreach ($this->getBuyers() as $dealer) {
      if ($dealer->utilizedAmount($this) <= 0) {
        array_push($dealers, $dealer);
      }
    }

    return collect($dealers);
  }

  public function getUtilizedAttribute(): float
  {
    $amount = 0;

    $invoices = Invoice::where('program_id', $this->id)
      ->whereIn('financing_status', ['financed', 'pending', 'disbursed', 'submitted'])
      ->whereHas('paymentRequests', function ($query) {
        $query->whereIn('status', ['paid', 'approved']);
      })
      ->get();

    if ($this->programType) {
      if ($this->programType->name == self::DEALER_FINANCING) {
        foreach ($invoices as $key => $invoice) {
          // if ($invoice->financing_status == 'financed') {
          //   $amount += $invoice->disbursed_amount - $invoice->paid_amount;
          // } else {
          // }
          $amount += $invoice->drawdown_amount - $invoice->paid_amount;
        }
      } else {
        foreach ($invoices as $key => $invoice) {
          // if ($invoice->financing_status == 'financed') {
          //   $amount += $invoice->disbursed_amount - $invoice->paid_amount;
          // } else {
          // }
          $amount += $invoice->eligibility
            ? ($invoice->eligibility / 100) * $invoice->invoice_total_amount - $invoice->paid_amount
            : $invoice->invoice_total_amount - $invoice->paid_amount;
        }
      }
    }

    return round($amount, 2);
  }

  public function getUtilizedPercentageRatioAttribute(): float
  {
    if ($this->program_limit > 0) {
      return round(($this->utilized_amount / $this->program_limit) * 100, 2);
    }

    return 0;
  }

  public function getPipelineAttribute(): float
  {
    $amount = 0;

    $invoices = Invoice::where('program_id', $this->id)
      // ->whereDate('due_date', '>=', now())
      ->whereIn('financing_status', ['pending', 'submitted'])
      ->whereHas('paymentRequests', function ($query) {
        $query->whereIn('status', ['created']);
      })
      ->get();

    foreach ($invoices as $invoice) {
      // Get Program Eligibility
      $eligibility = ProgramVendorConfiguration::where('company_id', $invoice->company_id)
        ->where('program_id', $invoice->program_id)
        ->when($invoice->buyer_id && $invoice->buyer_id != null, function ($query) use ($invoice) {
          $query->where('buyer_id', $invoice->buyer_id);
        })
        ->select('eligibility')
        ->first();

      $amount += ($eligibility->eligibility / 100) * $invoice->invoice_total_amount;
    }

    return round($amount, 2);
  }

  /**
   * Get the can activate attribute
   *
   * @param  string  $value
   * @return string
   */
  public function getCanActivateAttribute()
  {
    if (auth()->check()) {
      return auth()
        ->user()
        ->hasPermissionTo('Activate/Deactivate Program & Mapping');
    }

    return false;
  }

  /**
   * Get the can edit attribute
   *
   * @param  string  $value
   * @return string
   */
  public function getCanEditAttribute()
  {
    // Check if has pending changes that require approval
    $changes = ProgramChange::where('program_id', $this->id)->exists();
    if ($changes) {
      return false;
    }

    if (auth()->check()) {
      return auth()
        ->user()
        ->hasPermissionTo('Add/Edit Program & Mapping');
    }

    return false;
  }

  /**
   * Get the can view attribute
   *
   * @param  string  $value
   * @return string
   */
  public function getCanViewAttribute()
  {
    if (auth()->check()) {
      return auth()
        ->user()
        ->hasPermissionTo('View Programs & Mapping');
    }

    return false;
  }

  public function getCanApproveAttribute()
  {
    if (
      ($this->status === 'pending' || $this->status === 'rejected') &&
      Carbon::parse($this->approval_date)->lessThanOrEqualTo(now()) &&
      auth()->check()
    ) {
      return auth()
        ->user()
        ->hasPermissionTo('Activate/Deactivate Program & Mapping') && $this->created_by != auth()->id()
        ? true
        : false;
    }

    return false;
  }

  /**
   * Get the can delete attribute
   *
   * @param  string  $value
   * @return string
   */
  public function getCanDeleteAttribute()
  {
    if (auth()->check()) {
      if (
        auth()
          ->user()
          ->hasPermissionTo('Add/Edit Program & Mapping') &&
        $this->deleted_by != auth()->id()
      ) {
        // Check if program has open invoices
        $invoices = Invoice::where('program_id', $this->id)->count();

        // Check if program has open payment requests
        $payment_requests = PaymentRequest::whereHas('invoice', function ($query) {
          $query->where('program_id', $this->id);
        })->count();

        if ($invoices > 0 || $payment_requests > 0) {
          return false;
        }

        return true;
      }

      return false;
    }

    return false;
  }
}
