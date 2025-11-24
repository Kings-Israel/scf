<?php

namespace App\Models;

use App\Helpers\Helpers;
use Carbon\Carbon;
use App\Jobs\SendMail;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class PaymentRequest extends Model
{
  use HasFactory;

  protected $guarded = [];

  // protected static function boot()
  // {
  //   parent::boot();

  //   static::creating(function ($model) {
  //     DB::transaction(function () use ($model) {
  //       $latestValue = self::where('bank_id', $model->invoice->program->bank_id)
  //         ->lockForUpdate() // Essential for race condition prevention
  //         ->max('cbs_id');

  //       $model->cbs_id = ($latestValue ?? 0) + 1;
  //     });
  //   });
  // }

  protected static function booted()
  {
    static::updated(function ($model) {
      if ($model->status == 'approved') {
        $latest_id = PaymentRequest::whereHas('invoice', function ($query) use ($model) {
          $query->whereHas('program', function ($query) use ($model) {
            $query->where('bank_id', $model->invoice->program->bank_id);
          });
        })
          ->whereIn('status', ['approved', 'paid'])
          ->where('pr_id', '!=', null)
          ->latest('pr_id')
          ->first();

        if (!$model->pr_id) {
          $model->update([
            'pr_id' => $latest_id ? $latest_id->pr_id + 1 : 1,
          ]);
        }
      }
    });

    static::created(function ($model) {
      if ($model->status == 'approved') {
        $latest_id = PaymentRequest::whereHas('invoice', function ($query) use ($model) {
          $query->whereHas('program', function ($query) use ($model) {
            $query->where('bank_id', $model->invoice->program->bank_id);
          });
        })
          ->whereIn('status', ['approved', 'paid'])
          ->where('pr_id', '!=', null)
          ->latest('pr_id')
          ->first();

        if (!$model->pr_id) {
          $model->update([
            'pr_id' => $latest_id ? $latest_id->pr_id + 1 : 1,
          ]);
        }
      }
    });
  }

  /**
   * The accessors to append to the model's array form.
   *
   * @var array
   */
  protected $appends = [
    'eligible_for_finance',
    'discount',
    'vendor_fees',
    'approval_stage',
    'user_has_approved',
    'user_can_approve',
    'currency',
    'discount_rate',
    'fees',
  ];

  /**
   * The attributes that should be cast to native types.
   *
   * @var array
   */
  protected $casts = [
    'auto_approve_finance' => 'bool',
    'request_auto_finance' => 'bool',
    'auto_request_finance' => 'bool',
    'is_approved' => 'bool',
  ];

  /**
   * Get the invoice that owns the PaymentRequest
   */
  public function invoice(): BelongsTo
  {
    return $this->belongsTo(Invoice::class);
  }

  /**
   * Get all of the paymentAccounts for the PaymentRequest
   */
  public function paymentAccounts(): HasMany
  {
    return $this->hasMany(PaymentRequestAccount::class);
  }

  /**
   * Get all of the cbsTransactions for the PaymentRequest
   */
  public function cbsTransactions(): HasMany
  {
    return $this->hasMany(CbsTransaction::class);
  }

  /**
   * Get all of the approvals for the PaymentRequest
   */
  public function approvals(): HasMany
  {
    return $this->hasMany(PaymentRequestApproval::class);
  }

  /**
   * Get the rejectedBy that owns the PaymentRequest
   *
   * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
   */
  public function rejectedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'rejected_by', 'id');
  }

  /**
   * Get the createdBy that owns the PaymentRequest
   *
   * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
   */
  public function createdBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by', 'id');
  }

  /**
   * Get the updatedBy that owns the PaymentRequest
   *
   * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
   */
  public function updatedBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'updated_by', 'id');
  }

  /**
   * Get all of the approvals for the PaymentRequest
   */
  public function companyApprovals(): HasMany
  {
    return $this->hasMany(FinanceRequestApproval::class);
  }

  /**
   * Scope a query to only include vendor financing receivable
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeVendorFinancingReceivable($query)
  {
    return $query->whereHas('invoice', function ($query) {
      $query->whereHas('program', function ($query) {
        $query->whereHas('programCode', function ($query) {
          $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE);
        });
      });
    });
  }

  /**
   * Scope a query to only include factoring with recourse
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeFactoringWithRecourse($query)
  {
    return $query->whereHas('invoice', function ($query) {
      $query->whereHas('program', function ($query) {
        $query->whereHas('programCode', function ($query) {
          $query->where('name', Program::FACTORING_WITH_RECOURSE);
        });
      });
    });
  }

  /**
   * Scope a query to only include factoring without recourse
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeFactoringWithoutRecourse($query)
  {
    return $query->whereHas('invoice', function ($query) {
      $query->whereHas('program', function ($query) {
        $query->whereHas('programCode', function ($query) {
          $query->where('name', Program::FACTORING_WITHOUT_RECOURSE);
        });
      });
    });
  }

  /**
   * Scope a query to only include dealer financing
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeDealerFinancing($query)
  {
    return $query->whereHas('invoice', function ($query) {
      $query->whereHas('program', function ($query) {
        $query->whereHas('programType', function ($query) {
          $query->where('name', Program::DEALER_FINANCING);
        });
      });
    });
  }

  /**
   * Get the amount
   *
   * @param  string  $value
   * @return string
   */
  public function getAmountAttribute($value)
  {
    if ($value < 0.05) {
      return 0.05;
    } else {
      return round($value, 2);
    }
  }

  /**
   * Get the Eligibile for Finance
   *
   * @param  string  $value
   * @return string
   */
  public function getEligibleForFinanceAttribute()
  {
    // Get Eligibility
    $eligibility = $this->invoice->program
      ->vendorConfigurations()
      ->where('company_id', $this->invoice->company->id)
      ->first();

    // Get ROI
    // $roi = $this->invoice->program->vendorDiscountDetails()->where('company_id', $this->invoice->company->id)->first();

    // Get total invoice deductions
    $deductions = 0;
    foreach ($this->invoice->invoiceFees as $fee) {
      $deductions += $fee->amount;
    }

    // Get total invoice taxe amount
    $taxes = 0;
    foreach ($this->invoice->invoiceTaxes as $tax) {
      $taxes += $tax->value;
    }

    if ($this->invoice->program->programType->name == 'Dealer Financing') {
      $total_amount = $this->invoice->total_amount;
    } else {
      // Get Invoice total amount
      $total_amount = 0;
      if ($this->invoice->total_amount) {
        $total_amount = $this->invoice->total_amount;
      } else {
        foreach ($this->invoice->invoiceItems as $item) {
          $total_amount += $item->quantity * $item->price_per_quantity;
        }
      }
    }

    // Get invoice discount amount
    $total_invoice_discount = 0;
    foreach ($this->invoice->invoiceDiscounts as $invoice_discount) {
      if (!$invoice_discount->invoiceItem) {
        if ($invoice_discount->type == 'percentage') {
          $total_invoice_discount += ($invoice_discount->value / 100) * $total_amount;
        } else {
          $total_invoice_discount += $invoice_discount->value;
        }
      } else {
        // Calculate invoice discount on item
        $item_total = $invoice_discount->invoiceItem->quantity * $invoice_discount->invoiceItem->price_per_quantity;
        if ($invoice_discount->type == 'percentage') {
          $total_invoice_discount += ($invoice_discount->value / 100) * $item_total;
        } else {
          $total_invoice_discount += $invoice_discount->value;
        }
      }
    }

    $total_invoice_amount = $total_amount + $taxes - $total_invoice_discount - $deductions;

    $legible_amount = ($eligibility->eligibility / 100) * $total_invoice_amount;

    // $processing_fee = 0.01 * $legible_amount;

    // $total_discount = (($eligibility->eligibility / 100) * $total_invoice_amount) * ($roi->total_roi / 100) * ((Carbon::parse($this->payment_request_date)->diffInDays(Carbon::parse($this->invoice->due_date)) + 1) / 365);

    return round($legible_amount, 2);
  }

  public function getDiscountAttribute()
  {
    $total_discount = PaymentRequestAccount::whereHas('paymentRequest', function ($q) {
      $q->where('payment_request_id', $this->id);
    })
      ->whereDate('created_at', '<', Carbon::parse($this->invoice->due_date))
      ->where('type', 'discount')
      ->sum('amount');
    // // Get Eligibility
    // $vendor_configuration = $this->invoice->program
    //   ->vendorConfigurations()
    //   ->where('company_id', $this->invoice->company->id)
    //   ->first();

    // $vendor_discount_details = $this->invoice->program
    //   ->vendorDiscountDetails()
    //   ->where('company_id', $this->invoice->company->id)
    //   ->first();

    // $total_amount =
    //   $this->invoice->total +
    //   $this->invoice->total_invoice_taxes -
    //   $this->invoice->total_invoice_fees -
    //   $this->invoice->total_invoice_discount;

    // if ($this->invoice->program->programType->name == Program::VENDOR_FINANCING) {
    //   $total_discount =
    //     ($vendor_configuration->eligibility / 100) *
    //     $total_amount *
    //     ($vendor_discount_details->total_roi / 100) *
    //     (Carbon::parse($this->payment_request_date)->diffInDays(Carbon::parse($this->invoice->due_date)) / 365);
    // } else {
    //   $total_discount =
    //     ($vendor_configuration->eligibility / 100) *
    //     $total_amount *
    //     ($vendor_discount_details->total_roi / 100) *
    //     ((Carbon::parse($this->payment_request_date)->diffInDays(Carbon::parse($this->invoice->due_date)) + 1) / 365);
    // }

    return round($total_discount, 2);
  }

  public function getVendorFeesAttribute()
  {
    $vendor_fee_details = $this->invoice->program
      ->vendorFeeDetails()
      ->where('company_id', $this->invoice->company_id)
      ->get();

    $total_fee_amount = 0;

    $legible_amount = $this->invoice->eligible_for_finance;

    foreach ($vendor_fee_details as $vendor_fee) {
      if ($vendor_fee->vendor_bearing_discount > 0 || !$vendor_fee->anchor_bearing_discount) {
        if ($vendor_fee->type == 'amount') {
          $total_fee_amount += $vendor_fee->value;
        }
        if ($vendor_fee->type == 'percentage') {
          $total_fee_amount += ($vendor_fee->value / 100) * $legible_amount;
        }
        if ($vendor_fee->type == 'per amount') {
          $total_fee_amount += ($legible_amount / $vendor_fee->per_amount) * $vendor_fee->value;
        }
      }
    }

    return round($total_fee_amount, 2);
  }

  /**
   * Get the approval stage
   *
   * @return string
   */
  public function getApprovalStageAttribute()
  {
    $status = '';

    if (auth()->check()) {
      if ($this->status === 'created' || $this->status === 'pending') {
        if (Carbon::parse($this->invoice->due_date)->lessThanOrEqualTo(now()->format('Y-m-d'))) {
          $status = 'past due';
        } else {
          // Check configurations
          $config = $this->invoice->program->bank->generalConfigurations
            ->where('product_type_id', $this->invoice->program->programType->id)
            ->where('name', 'finance request approval')
            ->first();

          if ($config && $config->value === 'no') {
            $status = 'pending approval';
          } else {
            $approval_count = $this->approvals()->count();
            switch ($approval_count) {
              case '0':
                $status = 'pending maker';
                break;
              case '1':
                $status = 'pending checker';
                break;
              default:
                $status = $this->status;
                break;
            }
          }
        }
      } else {
        $status = $this->status;
      }
    }

    return Str::headline($status);
  }

  /**
   * Get the user has approved
   *
   * @return boolean
   */
  public function getUserHasApprovedAttribute()
  {
    if (auth()->check()) {
      $has_approved = $this->approvals()
        ->where('user_id', auth()->id())
        ->exists();

      if ($has_approved) {
        return true;
      }

      return false;
    }

    return false;
  }

  /**
   * Get the user can approve attribute
   *
   * @param  string  $value
   * @return string
   */
  public function getUserCanApproveAttribute()
  {
    if (auth()->check()) {
      if (
        ($this->status == 'created' || $this->status == 'pending') &&
        Carbon::parse($this->invoice->due_date)->greaterThan(now()->format('Y-m-d'))
      ) {
        $approvals = $this->approvals->sortByDesc('id');
        $company_approvals = $this->companyApprovals->where('user_id', auth()->id());

        // Check permissions
        if ($this->invoice->program->programType->name === Program::VENDOR_FINANCING) {
          // If request has company approvals on created stage, it's still in the company level awaiting checker approval
          if ($company_approvals->count() > 0) {
            if ($this->invoice->program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE) {
              if (
                !$company_approvals->contains(auth()->id()) &&
                auth()
                  ->user()
                  ->hasPermissionTo('Vendor Finance Request Checker')
              ) {
                return true;
              }

              return false;
            } else {
              if (
                !$company_approvals->contains(auth()->id()) &&
                auth()
                  ->user()
                  ->hasPermissionTo('Seller Finance Request Checker')
              ) {
                return true;
              }

              return false;
            }
          }

          $vendor_financing_id = ProgramType::where('name', Program::VENDOR_FINANCING)->first()->id;
          $bank_configuration = BankProductsConfiguration::where('bank_id', $this->invoice->program->bank_id)
            ->where('product_type_id', $vendor_financing_id)
            ->first();
          if ($bank_configuration && $bank_configuration->finance_request_approval == 'no') {
            if (
              auth()
                ->user()
                ->hasPermissionTo('Approve Vendor Financing Requests Level 1') ||
              auth()
                ->user()
                ->hasPermissionTo('Approve Vendor Financing Requests Level 2')
            ) {
              return true;
            } else {
              return false;
            }
          } elseif (
            count($approvals) == 0 &&
            auth()
              ->user()
              ->hasPermissionTo('Approve Vendor Financing Requests Level 1')
          ) {
            return true;
          } elseif (
            count($approvals) == 1 &&
            auth()
              ->user()
              ->hasPermissionTo('Approve Vendor Financing Requests Level 2')
          ) {
            return true;
          }
        }
        if ($this->invoice->program->programType->name == Program::DEALER_FINANCING) {
          // If request has company approvals on created stage, it's still in the company level awaiting checker approval
          if ($company_approvals->count() > 0) {
            if (
              !$company_approvals->contains(auth()->id()) &&
              auth()
                ->user()
                ->hasPermissionTo('Dealer Finance Request Checker')
            ) {
              return true;
            }

            return false;
          }

          $dealer_financing_id = ProgramType::where('name', Program::DEALER_FINANCING)->first()->id;
          $bank_configuration = BankProductsConfiguration::where('bank_id', $this->invoice->program->bank_id)
            ->where('product_type_id', $dealer_financing_id)
            ->first();
          if ($bank_configuration && $bank_configuration->finance_request_approval == 'no') {
            if (
              auth()
                ->user()
                ->hasPermissionTo('Approve Dealer Financing Requests Level 1') ||
              auth()
                ->user()
                ->hasPermissionTo('Approve Dealer Financing Requests Level 2')
            ) {
              return true;
            } else {
              return false;
            }
          } elseif (
            count($approvals) == 0 &&
            auth()
              ->user()
              ->hasPermissionTo('Approve Dealer Financing Requests Level 1')
          ) {
            return true;
          } elseif (
            count($approvals) == 1 &&
            auth()
              ->user()
              ->hasPermissionTo('Approve Dealer Financing Requests Level 2')
          ) {
            return true;
          }
        }
      }

      return false;
    }

    return false;
  }

  public function resolveStatus($status): string
  {
    switch (Str::lower($status)) {
      case 'pending maker':
        $style = 'bg-label-warning';
        break;
      case 'pending checker':
        $style = 'bg-label-warning';
        break;
      case 'pending':
        $style = 'bg-label-primary';
        break;
      case 'created':
        $style = 'bg-label-secondary';
        break;
      case 'approved':
        $style = 'bg-label-success';
        break;
      case 'paid':
        $style = 'bg-label-success';
        break;
      case 'closed':
        $style = 'bg-label-info';
        break;
      case 'failed':
        $style = 'bg-label-danger';
        break;
      case 'rejected':
        $style = 'bg-label-danger';
        break;
      case 'past due':
        $style = 'bg-label-danger';
        break;
      default:
        $style = 'bg-label-primary';
        break;
    }

    return $style;
  }

  /**
   * Get the currency
   *
   * @param  string  $value
   * @return string
   */
  public function getCurrencyAttribute()
  {
    $currency = 'KES';
    if ($this->invoice->program->bank->adminConfiguration) {
      if ($this->invoice->program->bank->adminConfiguration->defaultCurrency) {
        $currency = Currency::find($this->invoice->program->bank->adminConfiguration->defaultCurrency)->code;
      } elseif ($this->invoice->program->bank->adminConfiguration->selectedCurrencyIds) {
        $currency = Currency::find(
          explode(',', str_replace("\"", '', $this->invoice->program->bank->adminConfiguration->selectedCurrencyIds))[0]
        )?->code;
      }
    }

    return $currency;
  }

  /**
   * Get the discount rate used in dealer financing
   *
   * @param  string  $value
   * @return float
   */
  public function getDiscountRateAttribute()
  {
    if ($this->invoice->program->programType->name == Program::DEALER_FINANCING) {
      // Get difference in days between anchor payment and repayment date
      $diff = Carbon::parse($this->invoice->payment_date)->diffInDays(Carbon::parse($this->invoice->due_date));

      $vendor_discount_details = ProgramVendorDiscount::where('company_id', $this->invoice->company_id)
        ->where('program_id', $this->invoice->program_id)
        ->where('from_day', '<=', $diff)
        ->where('to_day', '>=', $diff)
        ->select('total_roi')
        ->latest()
        ->first();

      if (!$vendor_discount_details) {
        $vendor_discount_details = ProgramVendorDiscount::where('company_id', $this->invoice->company_id)
          ->where('program_id', $this->invoice->program_id)
          ->select('total_roi')
          ->latest()
          ->first();
      }

      return $vendor_discount_details->total_roi;
    }

    return 0.0;
  }

  public function notifyUsers($type)
  {
    switch ($type) {
      case 'LoanClosing':
        // If vendor financing receivable, notify anchor
        if (
          $this->invoice->program->programType->name == Program::VENDOR_FINANCING &&
          $this->invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE
        ) {
          $anchor_users = $this->invoice->program->anchor->users;
          foreach ($anchor_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'LoanClosing', ['invoice_id' => $this->invoice_id]);
          }
        }
        // If factoring, notify buyer
        if (
          $this->invoice->program->programType->name == Program::VENDOR_FINANCING &&
          ($this->invoice->program->programCode->name == Program::FACTORING_WITH_RECOURSE ||
            $this->invoice->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE)
        ) {
          $buyer_users = $this->invoice->buyer->users;
          foreach ($buyer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'LoanClosing', ['invoice_id' => $this->invoice_id]);
          }
        }
        // If dealer financing, notify dealer users
        if ($this->invoice->program->programType->name == Program::DEALER_FINANCING) {
          $dealer_users = $this->invoice->company->users;
          foreach ($dealer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'LoanClosing', ['invoice_id' => $this->invoice_id]);
          }
        }
        // Notify bank RMs users
        $bank_users = $this->invoice->program->bankUserDetails;
        foreach ($bank_users as $user) {
          SendMail::dispatchAfterResponse($user->email, 'LoanClosing', ['invoice_id' => $this->invoice_id]);
        }
        break;
      case 'LoanDisbursal':
        // If vendor financing receivable, notify anchor
        if (
          $this->invoice->program->programType->name == Program::VENDOR_FINANCING &&
          $this->invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE
        ) {
          $anchor_users = $this->invoice->program->anchor->users;
          foreach ($anchor_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'LoanDisbursal', ['invoice_id' => $this->invoice_id]);
          }
          $vendor_users = $this->invoice->company->users;
          foreach ($vendor_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'LoanDisbursal', ['invoice_id' => $this->invoice_id]);
          }
        }
        // If factoring, notify buyer
        if (
          $this->invoice->program->programType->name == Program::VENDOR_FINANCING &&
          ($this->invoice->program->programCode->name == Program::FACTORING_WITH_RECOURSE ||
            $this->invoice->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE)
        ) {
          $buyer_users = $this->invoice->buyer->users;
          foreach ($buyer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'LoanDisbursal', ['invoice_id' => $this->invoice_id]);
          }
          $anchor_users = $this->invoice->program->anchor->users;
          foreach ($anchor_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'LoanDisbursal', ['invoice_id' => $this->invoice_id]);
          }
        }
        // If dealer financing, notify dealer users
        if ($this->invoice->program->programType->name == Program::DEALER_FINANCING) {
          $dealer_users = $this->invoice->company->users;
          foreach ($dealer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'LoanDisbursal', ['invoice_id' => $this->invoice_id]);
          }
          $anchor_users = $this->invoice->program->anchor->users;
          foreach ($anchor_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'LoanDisbursal', ['invoice_id' => $this->invoice_id]);
          }
        }
        // Notify bank RMs users
        $bank_users = $this->invoice->program->bankUserDetails;
        foreach ($bank_users as $user) {
          SendMail::dispatchAfterResponse($user->email, 'LoanDisbursal', ['invoice_id' => $this->invoice_id]);
        }
        break;
      case 'FullRepayment':
        // If vendor financing receivable, notify anchor
        if (
          $this->invoice->program->programType->name == Program::VENDOR_FINANCING &&
          $this->invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE
        ) {
          $anchor_users = $this->invoice->program->anchor->users;
          foreach ($anchor_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'FullRepayment', ['invoice_id' => $this->invoice_id]);
          }
        }
        // If factoring, notify buyer
        if (
          $this->invoice->program->programType->name == Program::VENDOR_FINANCING &&
          ($this->invoice->program->programCode->name == Program::FACTORING_WITH_RECOURSE ||
            $this->invoice->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE)
        ) {
          $buyer_users = $this->invoice->buyer->users;
          foreach ($buyer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'FullRepayment', ['invoice_id' => $this->invoice_id]);
          }
        }
        // If dealer financing, notify dealer users
        if ($this->invoice->program->programType->name == Program::DEALER_FINANCING) {
          $dealer_users = $this->invoice->company->users;
          foreach ($dealer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'FullRepayment', ['invoice_id' => $this->invoice_id]);
          }
        }
        // Notify bank RMs users
        $bank_users = $this->invoice->program->bankUserDetails;
        foreach ($bank_users as $user) {
          SendMail::dispatchAfterResponse($user->email, 'FullRepayment', ['invoice_id' => $this->invoice_id]);
        }
        break;
      case 'PartialRepayment':
        // If vendor financing receivable, notify anchor
        if (
          $this->invoice->program->programType->name == Program::VENDOR_FINANCING &&
          $this->invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE
        ) {
          $anchor_users = $this->invoice->program->anchor->users;
          foreach ($anchor_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'PartialRepayment', ['invoice_id' => $this->invoice_id]);
          }
        }
        // If factoring, notify buyer
        if (
          $this->invoice->program->programType->name == Program::VENDOR_FINANCING &&
          ($this->invoice->program->programCode->name == Program::FACTORING_WITH_RECOURSE ||
            $this->invoice->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE)
        ) {
          $buyer_users = $this->invoice->buyer->users;
          foreach ($buyer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'PartialRepayment', ['invoice_id' => $this->invoice_id]);
          }
        }
        // If dealer financing, notify dealer users
        if ($this->invoice->program->programType->name == Program::DEALER_FINANCING) {
          $dealer_users = $this->invoice->company->users;
          foreach ($dealer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'PartialRepayment', ['invoice_id' => $this->invoice_id]);
          }
        }
        // Notify bank RMs users
        $bank_users = $this->invoice->program->bankUserDetails;
        foreach ($bank_users as $user) {
          SendMail::dispatchAfterResponse($user->email, 'PartialRepayment', ['invoice_id' => $this->invoice_id]);
        }
        break;
      case 'OverdueFullRepayment':
        // If vendor financing receivable, notify anchor
        if (
          $this->invoice->program->programType->name == Program::VENDOR_FINANCING &&
          $this->invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE
        ) {
          $anchor_users = $this->invoice->program->anchor->users;
          foreach ($anchor_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'OverdueFullRepayment', [
              'invoice_id' => $this->invoice_id,
              'amount' => $this->invoice->overdue_amount,
            ]);
          }
        }
        // If factoring, notify buyer
        if (
          $this->invoice->program->programType->name == Program::VENDOR_FINANCING &&
          ($this->invoice->program->programCode->name == Program::FACTORING_WITH_RECOURSE ||
            $this->invoice->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE)
        ) {
          $buyer_users = $this->invoice->buyer->users;
          foreach ($buyer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'OverdueFullRepayment', [
              'invoice_id' => $this->invoice_id,
              'amount' => $this->invoice->overdue_amount,
            ]);
          }
        }
        // If dealer financing, notify dealer users
        if ($this->invoice->program->programType->name == Program::DEALER_FINANCING) {
          $dealer_users = $this->invoice->company->users;
          foreach ($dealer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'OverdueFullRepayment', [
              'invoice_id' => $this->invoice_id,
              'amount' => $this->invoice->overdue_amount,
            ]);
          }
        }
        break;
      case 'InvoicePaymentProcessed':
        // If vendor financing receivable, notify anchor
        if (
          $this->invoice->program->programType->name == Program::VENDOR_FINANCING &&
          $this->invoice->program->programCode->name == 'Vendor Financing Receivable'
        ) {
          $anchor_users = $this->invoice->program->anchor->users;
          foreach ($anchor_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'InvoicePaymentProcessed', [
              'invoice_id' => $this->invoice_id,
            ]);
          }
        }
        // If factoring, notify buyer
        if (
          $this->invoice->program->programType->name == Program::VENDOR_FINANCING &&
          ($this->invoice->program->programCode->name == 'Factoring With Recourse' ||
            $this->invoice->program->programCode->name == 'Factoring Without Recourse')
        ) {
          $buyer_users = $this->invoice->buyer->users;
          foreach ($buyer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'InvoicePaymentProcessed', [
              'invoice_id' => $this->invoice_id,
            ]);
          }
        }
        // If dealer financing, notify dealer users
        if ($this->invoice->program->programType->name == Program::DEALER_FINANCING) {
          $dealer_users = $this->invoice->company->users;
          foreach ($dealer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'InvoicePaymentProcessed', [
              'invoice_id' => $this->invoice_id,
            ]);
          }
        }
        break;
      case 'BalanceInvoicePayment':
        // If vendor financing receivable, notify anchor
        if (
          $this->invoice->program->programType->name == Program::VENDOR_FINANCING &&
          $this->invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE
        ) {
          $anchor_users = $this->invoice->program->anchor->users;
          foreach ($anchor_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'BalanceInvoicePayment', ['invoice_id' => $this->invoice_id]);
          }
        }
        // If factoring, notify buyer
        if (
          $this->invoice->program->programType->name == Program::VENDOR_FINANCING &&
          ($this->invoice->program->programCode->name == Program::FACTORING_WITH_RECOURSE ||
            $this->invoice->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE)
        ) {
          $buyer_users = $this->invoice->buyer->users;
          foreach ($buyer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'BalanceInvoicePayment', ['invoice_id' => $this->invoice_id]);
          }
        }
        // If dealer financing, notify dealer users
        if ($this->invoice->program->programType->name == Program::DEALER_FINANCING) {
          $dealer_users = $this->invoice->company->users;
          foreach ($dealer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'BalanceInvoicePayment', ['invoice_id' => $this->invoice_id]);
          }
        }
        break;
      case 'InvoicePaymentReceivedBySeller':
        // If vendor financing receivable, notify anchor
        if (
          $this->invoice->program->programType->name == Program::VENDOR_FINANCING &&
          $this->invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE
        ) {
          $anchor_users = $this->invoice->program->anchor->users;
          foreach ($anchor_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'InvoicePaymentReceivedBySeller', [
              'invoice_id' => $this->invoice_id,
            ]);
          }
        }
        // If factoring, notify buyer
        if (
          $this->invoice->program->programType->name == Program::VENDOR_FINANCING &&
          ($this->invoice->program->programCode->name == Program::FACTORING_WITH_RECOURSE ||
            $this->invoice->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE)
        ) {
          $buyer_users = $this->invoice->buyer->users;
          foreach ($buyer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'InvoicePaymentReceivedBySeller', [
              'invoice_id' => $this->invoice_id,
            ]);
          }
        }
        // If dealer financing, notify dealer users
        if ($this->invoice->program->programType->name == Program::DEALER_FINANCING) {
          $dealer_users = $this->invoice->company->users;
          foreach ($dealer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'InvoicePaymentReceivedBySeller', [
              'invoice_id' => $this->invoice_id,
            ]);
          }
        }
        break;
      case 'PaymentRequestRejection':
        // Send to maker approver
        $maker = $this->approvals()
          ->pluck('user_id')
          ->first();
        $user = User::find($maker);
        if (auth()->check() && $user) {
          SendMail::dispatchAfterResponse($user->email, 'PaymentRequestRejection', [
            'payment_request_id' => $this->id,
            'user_name' => auth()->user()->name,
          ]);
        }
        break;

      default:
        # code...
        break;
    }
  }

  public function getFeesAttribute(): Collection|NULL
  {
    $fees = [];

    if ($this->invoice->program->programType->name == Program::DEALER_FINANCING) {
      $vendor_configuration = ProgramVendorConfiguration::where('company_id', '=', $this->invoice->company_id)
        ->where('program_id', $this->invoice->program_id)
        ->first();

      $vendor_fees = ProgramVendorFee::where('company_id', $this->invoice->company_id)
        ->where('program_id', $this->invoice->program_id)
        ->get();
    } else {
      if ($this->invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        $vendor_configuration = ProgramVendorConfiguration::where('company_id', '=', $this->invoice->company_id)
          ->where('program_id', $this->invoice->program_id)
          ->first();

        $vendor_fees = ProgramVendorFee::where('company_id', $this->invoice->company_id)
          ->where('program_id', $this->invoice->program_id)
          ->get();
      } else {
        $vendor_configuration = ProgramVendorConfiguration::where('company_id', '=', $this->invoice->company_id)
          ->where('buyer_id', $this->invoice->buyer_id)
          ->where('program_id', $this->invoice->program_id)
          ->first();

        $vendor_fees = ProgramVendorFee::where('company_id', $this->invoice->company_id)
          ->where('buyer_id', $this->invoice->buyer_id)
          ->where('program_id', $this->invoice->program_id)
          ->get();
      }
    }

    $legible_amount = ($vendor_configuration->eligibility / 100) * $this->invoice->invoice_total_amount;

    if ($vendor_fees->count() > 0) {
      foreach ($vendor_fees as $key => $fee) {
        if ($fee->type == 'amount') {
          if ($this->invoice->program->programType->name == Program::DEALER_FINANCING) {
            $fees[$key] = ['name' => $fee->fee_name, 'value' => $fee->value];
          } else {
            if ($this->invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
              $fees[$key] = ['name' => $fee->fee_name, 'value' => ($fee->vendor_bearing_discount / 100) * $fee->value];
            } else {
              $fees[$key] = ['name' => $fee->fee_name, 'value' => ($fee->anchor_bearing_discount / 100) * $fee->value];
            }
          }
        }

        if ($fee->type == 'percentage') {
          if ($this->invoice->program->programType->name == Program::DEALER_FINANCING) {
            $fees[$key] = ['name' => $fee->fee_name, 'value' => round(($fee->value / 100) * $legible_amount, 2)];
          } else {
            if ($this->invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
              $fees[$key] = [
                'name' => $fee->fee_name,
                'value' => round(($fee->vendor_bearing_discount / 100) * (($fee->value / 100) * $legible_amount), 2),
              ];
            } else {
              $fees[$key] = [
                'name' => $fee->fee_name,
                'value' => round(($fee->anchor_bearing_discount / 100) * (($fee->value / 100) * $legible_amount), 2),
              ];
            }
          }
        }

        if ($fee->type == 'per amount') {
          if ($this->invoice->program->programType->name == 'Dealer Financing') {
            $fees[$key] = [
              'name' => $fee->fee_name,
              'value' => round(floor($legible_amount / $fee->per_amount) * $fee->value, 2),
            ];
          } else {
            if ($this->invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
              $fees[$key] = [
                'name' => $fee->fee_name,
                'value' => round(
                  ($fee->vendor_bearing_discount / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value),
                  2
                ),
              ];
            } else {
              $fees[$key] = [
                'name' => $fee->fee_name,
                'value' => round(
                  ($fee->anchor_bearing_discount / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value),
                  2
                ),
              ];
            }
          }
        }
      }

      return collect($fees);
    }

    return null;
  }

  public function updateRequestAmounts()
  {
    $vendor_financing = ProgramType::where('name', Program::VENDOR_FINANCING)->first();
    $vendor_financing_receivable = ProgramCode::where('name', Program::VENDOR_FINANCING_RECEIVABLE)->first();
    $dealer_financing = ProgramType::where('name', Program::DEALER_FINANCING)->first();

    if ($this->invoice->program->programType->name === Program::VENDOR_FINANCING) {
      if ($this->invoice->program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE) {
        // Vendor Financing Receivable
        $vendor_discount_details = ProgramVendorDiscount::select(
          'total_roi',
          'anchor_discount_bearing',
          'vendor_discount_bearing'
        )
          ->where('company_id', $this->invoice->company_id)
          ->where('program_id', $this->invoice->program_id)
          ->first();

        $vendor_fees = ProgramVendorFee::where('company_id', $this->invoice->company_id)
          ->where('program_id', $this->invoice->program_id)
          ->get();

        $fees_income_bank_account = BankProductsConfiguration::where('bank_id', $this->invoice->program->bank_id)
          ->where('section', 'Vendor Finance Receivable')
          ->where('product_code_id', $vendor_financing_receivable->id)
          ->where('product_type_id', $vendor_financing->id)
          ->where('name', 'Fee Income Account')
          ->first();
      } else {
        $vendor_discount_details = ProgramVendorDiscount::select(
          'total_roi',
          'anchor_discount_bearing',
          'vendor_discount_bearing'
        )
          ->where('company_id', $this->invoice->company_id)
          ->where('buyer_id', $this->invoice->buyer_id)
          ->where('program_id', $this->invoice->program_id)
          ->first();

        $vendor_fees = ProgramVendorFee::where('company_id', $this->invoice->company_id)
          ->where('buyer_id', $this->invoice->buyer_id)
          ->where('program_id', $this->invoice->program_id)
          ->get();

        if ($this->invoice->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE) {
          $fees_income_bank_account = BankProductsConfiguration::where('bank_id', $this->invoice->program->bank->id)
            ->where('section', 'Factoring Without Recourse')
            ->where('product_type_id', $vendor_financing->id)
            ->where('product_code_id', $this->invoice->program->program_code_id)
            ->where('name', 'Fee Income Account')
            ->first();
        } else {
          // Factoring with recourse
          $fees_income_bank_account = BankProductsConfiguration::where('bank_id', $this->invoice->program->bank->id)
            ->where('section', 'Factoring With Recourse')
            ->where('product_type_id', $vendor_financing->id)
            ->where('product_code_id', $this->invoice->program->program_code_id)
            ->where('name', 'Fee Income Account')
            ->first();
        }
      }
    } else {
      // Dealer Financing
      $vendor_discount_details = ProgramVendorDiscount::select(
        'total_roi',
        'anchor_discount_bearing',
        'vendor_discount_bearing'
      )
        ->where('company_id', $this->invoice->company_id)
        ->where('program_id', $this->invoice->program_id)
        ->first();

      $vendor_fees = ProgramVendorFee::where('company_id', $this->invoice->company_id)
        ->where('program_id', $this->invoice->program_id)
        ->get();

      $fees_income_bank_account = BankProductsConfiguration::where('bank_id', $this->invoice->program->bank->id)
        ->where('product_type_id', $dealer_financing->id)
        ->where('product_code_id', null)
        ->where('name', 'Fee Income Account')
        ->first();
    }

    // Get Tax on Discount Value
    $tax_on_discount = ProgramDiscount::select('tax_on_discount')
      ->where('program_id', $this->invoice->program_id)
      ->first()?->tax_on_discount;

    $eligibility = $this->invoice->eligibility;
    $total_amount = $this->invoice->calculated_total_amount;
    if ($total_amount == 0) {
      $total_amount = $this->invoice->invoice_total_amount;
    }

    $total_roi = $vendor_discount_details ? $vendor_discount_details->total_roi : 0;
    $legible_amount = ($eligibility / 100) * $total_amount;

    $vendor_bearing_fees = 0;
    $fees_amount = 0;

    PaymentRequestAccount::where('payment_request_id', $this->id)
      ->whereIn('type', ['program_fees'])
      ->delete();

    PaymentRequestAccount::where('payment_request_id', $this->id)
      ->whereIn('type', ['tax_on_fees'])
      ->delete();

    $payment_date = PaymentRequestApproval::where('payment_request_id', $this->id)
      ->latest()
      ->first()->created_at;

    // Fee charges
    $fees_amount = 0;
    $anchor_bearing_fees = 0;
    $vendor_bearing_fees = 0;
    $fees_tax_amount = 0;
    if ($vendor_fees->count() > 0) {
      foreach ($vendor_fees as $fee) {
        if ($fee->type === 'amount') {
          if ($fee->charge_type === 'daily') {
            $fees_amount += $fee->value * ($payment_date->diffInDays(Carbon::parse($this->invoice->due_date)) + 1);

            if ($fee->taxes) {
              $fees_tax_amount += round(
                ($fee->taxes / 100) *
                  $fee->value *
                  ($payment_date->diffInDays(Carbon::parse($this->invoice->due_date)) + 1),
                2
              );
            }

            if ($this->invoice->program->programType->name === Program::DEALER_FINANCING) {
              $anchor_bearing_fees += 0;
              $vendor_bearing_fees +=
                $fee->value * ($payment_date->diffInDays(Carbon::parse($this->invoice->due_date)) + 1);
            } else {
              $anchor_bearing_fees +=
                ($fee->anchor_bearing_discount / 100) *
                $fee->value *
                ($payment_date->diffInDays(Carbon::parse($this->invoice->due_date)) + 1);
              $vendor_bearing_fees +=
                ($fee->vendor_bearing_discount / 100) *
                $fee->value *
                ($payment_date->diffInDays(Carbon::parse($this->invoice->due_date)) + 1);
            }
          } else {
            $fees_amount += $fee->value;

            if ($fee->taxes) {
              $fees_tax_amount += round(($fee->taxes / 100) * $fee->value, 2);
            }

            if ($this->invoice->program->programType->name === Program::DEALER_FINANCING) {
              $anchor_bearing_fees += 0;
              $vendor_bearing_fees += $fee->value;
            } else {
              $anchor_bearing_fees += ($fee->anchor_bearing_discount / 100) * $fee->value;
              $vendor_bearing_fees += ($fee->vendor_bearing_discount / 100) * $fee->value;
            }
          }
        }

        if ($fee->type == 'percentage') {
          if ($fee->charge_type === 'daily') {
            $fees_amount +=
              ($fee->value / 100) *
              $legible_amount *
              ($payment_date->diffInDays(Carbon::parse($this->invoice->due_date)) + 1);

            if ($fee->taxes) {
              $fees_tax_amount += round(
                ($fee->taxes / 100) *
                  (($fee->value / 100) *
                    $legible_amount *
                    ($payment_date->diffInDays(Carbon::parse($this->invoice->due_date)) + 1)),
                2
              );
            }

            if ($this->invoice->program->programType->name === Program::DEALER_FINANCING) {
              $anchor_bearing_fees += 0;
              $vendor_bearing_fees += round(
                ($fee->value / 100) *
                  $legible_amount *
                  ($payment_date->diffInDays(Carbon::parse($this->invoice->due_date)) + 1),
                2
              );
            } else {
              $anchor_bearing_fees += round(
                ($fee->anchor_bearing_discount / 100) *
                  (($fee->value / 100) *
                    $legible_amount *
                    ($payment_date->diffInDays(Carbon::parse($this->invoice->due_date)) + 1)),
                2
              );
              $vendor_bearing_fees += round(
                ($fee->vendor_bearing_discount / 100) *
                  (($fee->value / 100) *
                    $legible_amount *
                    ($payment_date->diffInDays(Carbon::parse($this->invoice->due_date)) + 1)),
                2
              );
            }
          } else {
            $fees_amount += ($fee->value / 100) * $legible_amount;

            if ($fee->taxes) {
              $fees_tax_amount += round(($fee->taxes / 100) * (($fee->value / 100) * $legible_amount), 2);
            }

            if ($this->invoice->program->programType->name === Program::DEALER_FINANCING) {
              $anchor_bearing_fees += 0;
              $vendor_bearing_fees += round(($fee->value / 100) * $legible_amount, 2);
            } else {
              $anchor_bearing_fees += round(
                ($fee->anchor_bearing_discount / 100) * (($fee->value / 100) * $legible_amount),
                2
              );
              $vendor_bearing_fees += round(
                ($fee->vendor_bearing_discount / 100) * (($fee->value / 100) * $legible_amount),
                2
              );
            }
          }
        }

        if ($fee->type === 'per amount') {
          if ($fee->charge_type === 'daily') {
            $fees_amount +=
              floor($legible_amount / $fee->per_amount) *
              $fee->value *
              ($payment_date->diffInDays(Carbon::parse($this->invoice->due_date)) + 1);

            if ($fee->taxes) {
              $fees_tax_amount += round(
                ($fee->taxes / 100) *
                  (floor($legible_amount / $fee->per_amount) *
                    $fee->value *
                    ($payment_date->diffInDays(Carbon::parse($this->invoice->due_date)) + 1)),
                2
              );
            }

            if ($this->invoice->program->programType->name === Program::DEALER_FINANCING) {
              $anchor_bearing_fees += 0;
              $vendor_bearing_fees += round(
                floor($legible_amount / $fee->per_amount) *
                  $fee->value *
                  ($payment_date->diffInDays(Carbon::parse($this->invoice->due_date)) + 1),
                2
              );
            } else {
              $anchor_bearing_fees += round(
                ($fee->anchor_bearing_discount / 100) *
                  (floor($legible_amount / $fee->per_amount) *
                    $fee->value *
                    ($payment_date->diffInDays(Carbon::parse($this->invoice->due_date)) + 1)),
                2
              );
              $vendor_bearing_fees += round(
                ($fee->vendor_bearing_discount / 100) *
                  (floor($legible_amount / $fee->per_amount) *
                    $fee->value *
                    ($payment_date->diffInDays(Carbon::parse($this->invoice->due_date)) + 1)),
                2
              );
            }
          } else {
            $fees_amount += floor($legible_amount / $fee->per_amount) * $fee->value;

            if ($fee->taxes) {
              $fees_tax_amount += round(
                ($fee->taxes / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value),
                2
              );
            }

            if ($this->invoice->program->programType->name === Program::DEALER_FINANCING) {
              $anchor_bearing_fees += 0;
              $vendor_bearing_fees += round(floor($legible_amount / $fee->per_amount) * $fee->value, 2);
            } else {
              $anchor_bearing_fees += round(
                ($fee->anchor_bearing_discount / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value),
                2
              );
              $vendor_bearing_fees += round(
                ($fee->vendor_bearing_discount / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value),
                2
              );
            }
          }
        }
      }
    }

    $anchor_bearing_discount_value = 0;

    $request_approval_date = PaymentRequestApproval::where('payment_request_id', $this->id)
      ->latest()
      ->first();

    $original_discount =
      $legible_amount *
      ($total_roi / 100) *
      (($request_approval_date->created_at->diffInDays(Carbon::parse($this->invoice->due_date)) + 1) / 365);

    // Tax on discount
    $discount_tax_amount = 0;
    if ($tax_on_discount && $tax_on_discount > 0) {
      $discount_tax_amount = ($tax_on_discount / 100) * $original_discount;
    }

    $discount = 0;
    if ($total_roi > 0) {
      if ($this->invoice->program->programType->name === Program::DEALER_FINANCING) {
        $discount = $original_discount;
      } else {
        if ($vendor_discount_details->anchor_discount_bearing > 0) {
          $discount =
            ($vendor_discount_details->anchor_discount_bearing / $total_roi) *
            $legible_amount *
            ($total_roi / 100) *
            (($request_approval_date->created_at->diffInDays(Carbon::parse($this->invoice->due_date)) + 1) / 365);
        } else {
          $discount = $original_discount;
        }
      }
    }

    $anchor_bearing_discount_value = round($original_discount - $discount, 2);

    if ($this->invoice->program->programType->name == Program::VENDOR_FINANCING) {
      $amount = $legible_amount - $fees_tax_amount - $discount - $discount_tax_amount - $vendor_bearing_fees;
    } else {
      if ($this->invoice->drawdown_amount) {
        $amount = $this->invoice->drawdown_amount - $fees_amount - $discount - $fees_tax_amount - $discount_tax_amount;
      } else {
        $amount = $total_amount - $fees_amount - $discount - $fees_tax_amount - $discount_tax_amount;
      }
    }

    $vendor_amount = $amount;

    // Calculate amount to disburse to vendor based on discount type and fee type
    if ($this->invoice->discount_charge_type === Invoice::REAR_ENDED) {
      $vendor_amount = $amount + $discount + $discount_tax_amount;
    }

    if ($this->invoice->fee_charge_type === Invoice::REAR_ENDED) {
      if ($this->invoice->program->programType->name == Program::VENDOR_FINANCING) {
        $vendor_amount = $vendor_amount + $fees_tax_amount + $vendor_bearing_fees;
      } else {
        $vendor_amount = $vendor_amount + $fees_tax_amount + $fees_amount;
      }
    }

    $this->update([
      'amount' => $vendor_amount,
      'payment_request_date' => now()->format('Y-m-d'),
      'anchor_discount_bearing' => $discount,
      'vendor_discount_bearing' => $original_discount - $anchor_bearing_discount_value,
    ]);

    $vendor_account = PaymentRequestAccount::where('payment_request_id', $this->id)
      ->where('type', 'vendor_account')
      ->first();

    $vendor_account->update([
      'amount' => $vendor_amount,
    ]);

    if ($this->invoice->program->programType->name == Program::VENDOR_FINANCING) {
      $discount_account = PaymentRequestAccount::where('payment_request_id', $this->id)
        ->where('type', 'discount')
        ->where('description', Invoice::VENDOR_DISCOUNT_BEARING)
        ->first();
    } else {
      $discount_account = PaymentRequestAccount::where('payment_request_id', $this->id)
        ->where('type', 'discount')
        ->where('description', Invoice::DEALER_DISCOUNT_BEARING)
        ->first();
    }

    if ($discount_account) {
      $discount_account->update([
        'amount' => $discount,
      ]);
    }

    $anchor_bearing_discount_account = PaymentRequestAccount::where('payment_request_id', $this->id)
      ->where('type', 'discount')
      ->whereIn('description', [Invoice::ANCHOR_DISCOUNT_BEARING, Invoice::BUYER_DISCOUNT_BEARING])
      ->first();

    if ($anchor_bearing_discount_account) {
      $anchor_bearing_discount_account->update([
        'amount' => $anchor_bearing_discount_value,
      ]);
    }

    $discount_tax_account = PaymentRequestAccount::where('payment_request_id', $this->id)
      ->where('type', 'tax_on_discount')
      ->first();

    if ($discount_tax_account) {
      $discount_tax_account->update([
        'amount' => $discount_tax_amount,
      ]);
    } else {
      if ($discount_tax_amount > 0) {
        $tax_income_bank_account = null;
        $dealer_financing = ProgramType::where('name', Program::DEALER_FINANCING)->first();

        if ($this->invoice->program->programType->name == Program::DEALER_FINANCING) {
          $tax_income_bank_account = BankProductsConfiguration::where('bank_id', $this->invoice->program->bank_id)
            ->where('product_type_id', $dealer_financing->id)
            ->where('product_code_id', null)
            ->where('name', 'Tax Account Number')
            ->first();

          $this->paymentAccounts()->create([
            'account' => $tax_income_bank_account->value,
            'account_name' => $tax_income_bank_account->name,
            'amount' => round($discount_tax_amount, 2),
            'type' => 'tax_on_discount',
            'description' => 'Tax on Discount',
          ]);
        } else {
          $tax_income_account = BankTaxRate::where('bank_id', $this->invoice->program->bank_id)
            ->where('value', $tax_on_discount)
            ->where('status', 'active')
            ->first();

          $this->paymentAccounts()->create([
            'account' => $tax_income_account->account_no,
            'account_name' => 'Tax Income Bank Account',
            'amount' => round($discount_tax_amount, 2),
            'type' => 'tax_on_discount',
            'description' => 'tax on discount',
          ]);
        }
      }
    }

    if ($vendor_fees->count() > 0) {
      foreach ($vendor_fees as $fee) {
        $fee_account = $fee->account_number
          ? $fee->account_number
          : ($fees_income_bank_account
            ? $fees_income_bank_account->value
            : 'Fee_Inc_Acc');
        $fee_account_name = $fee->account_name
          ? $fee->account_name
          : ($fees_income_bank_account
            ? $fees_income_bank_account->name
            : 'Fee Income Account');

        if ($fee->type === 'amount') {
          // Dealer Financing
          if ($this->invoice->program->programType->name === Program::DEALER_FINANCING) {
            $anchor_bearing_fees += 0;
            $vendor_bearing_fees =
              $fee->charge_type === 'fixed'
                ? $fee->value
                : $fee->value * (Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->invoice->due_date)) + 1);
            if ($vendor_bearing_fees > 0) {
              $this->paymentAccounts()->create([
                'account' => $fee_account,
                'account_name' => $fee_account_name,
                'amount' => round($vendor_bearing_fees, 2),
                'type' => 'program_fees',
                'description' => Invoice::VENDOR_FEE_BEARING,
                'title' => $fee->fee_name,
              ]);
            }
          } else {
            // Vendor Financing
            $anchor_bearing_fees =
              $fee->charge_type === 'fixed'
                ? ($fee->anchor_bearing_discount / 100) * $fee->value
                : ($fee->anchor_bearing_discount / 100) *
                  $fee->value *
                  (Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->invoice->due_date)) + 1);
            if ($anchor_bearing_fees > 0) {
              $this->paymentAccounts()->create([
                'account' => $fee_account,
                'account_name' => $fee_account_name,
                'amount' => round($anchor_bearing_fees, 2),
                'type' => 'program_fees',
                'description' =>
                  $this->invoice->program->programType->name == Program::VENDOR_FINANCING &&
                  $this->invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE
                    ? Invoice::ANCHOR_FEE_BEARING
                    : Invoice::BUYER_FEE_BEARING,
                'title' => $fee->fee_name,
              ]);
            }
            $vendor_bearing_fees =
              $fee->charge_type === 'fixed'
                ? ($fee->vendor_bearing_discount / 100) * $fee->value
                : ($fee->vendor_bearing_discount / 100) *
                  $fee->value *
                  (Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->invoice->due_date)) + 1);
            if ($vendor_bearing_fees > 0) {
              $this->paymentAccounts()->create([
                'account' => $fee_account,
                'account_name' => $fee_account_name,
                'amount' => round($vendor_bearing_fees, 2),
                'type' => 'program_fees',
                'description' => Invoice::VENDOR_FEE_BEARING,
                'title' => $fee->fee_name,
              ]);
            }
          }
        }

        if ($fee->type == 'percentage') {
          // Dealer Financing
          if ($this->invoice->program->programType->name == Program::DEALER_FINANCING) {
            $anchor_bearing_fees += 0;
            $vendor_bearing_fees =
              $fee->charge_type === 'fixed'
                ? round(($fee->value / 100) * $legible_amount, 2)
                : round(
                  ($fee->value / 100) *
                    $legible_amount *
                    (Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->invoice->due_date)) + 1),
                  2
                );
            if ($vendor_bearing_fees > 0) {
              $this->paymentAccounts()->create([
                'account' => $fee_account,
                'account_name' => $fee_account_name,
                'amount' => round($vendor_bearing_fees, 2),
                'type' => 'program_fees',
                'description' => Invoice::VENDOR_FEE_BEARING,
                'title' => $fee->fee_name,
              ]);
            }
          } else {
            // Vendor Financing
            $anchor_bearing_fees =
              $fee->charge_type === 'fixed'
                ? round(($fee->anchor_bearing_discount / 100) * (($fee->value / 100) * $legible_amount), 2)
                : round(
                  ($fee->anchor_bearing_discount / 100) *
                    (($fee->value / 100) *
                      $legible_amount *
                      (Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->invoice->due_date)) + 1)),
                  2
                );
            if ($anchor_bearing_fees > 0) {
              $this->paymentAccounts()->create([
                'account' => $fee_account,
                'account_name' => $fee_account_name,
                'amount' => round($anchor_bearing_fees, 2),
                'type' => 'program_fees',
                'description' =>
                  $this->invoice->program->programType->name == Program::VENDOR_FINANCING &&
                  $this->invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE
                    ? Invoice::ANCHOR_FEE_BEARING
                    : Invoice::BUYER_FEE_BEARING,
                'title' => $fee->fee_name,
              ]);
            }
            $vendor_bearing_fees =
              $fee->charge_type === 'fixed'
                ? round(($fee->vendor_bearing_discount / 100) * (($fee->value / 100) * $legible_amount), 2)
                : round(
                  ($fee->vendor_bearing_discount / 100) *
                    (($fee->value / 100) *
                      $legible_amount *
                      (Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->invoice->due_date)) + 1)),
                  2
                );
            if ($vendor_bearing_fees > 0) {
              $this->paymentAccounts()->create([
                'account' => $fee_account,
                'account_name' => $fee_account_name,
                'amount' => round($vendor_bearing_fees, 2),
                'type' => 'program_fees',
                'description' => Invoice::VENDOR_FEE_BEARING,
                'title' => $fee->fee_name,
              ]);
            }
          }
        }

        if ($fee->type == 'per amount') {
          // Dealer Financing
          if ($this->invoice->program->programType->name == Program::DEALER_FINANCING) {
            $vendor_bearing_fees =
              $fee->charge_type === 'fixed'
                ? round(floor($legible_amount / $fee->per_amount) * $fee->value, 2)
                : round(
                  floor($legible_amount / $fee->per_amount) *
                    $fee->value *
                    (Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->invoice->due_date)) + 1),
                  2
                );
            if ($vendor_bearing_fees > 0) {
              $this->paymentAccounts()->create([
                'account' => $fee_account,
                'account_name' => $fee_account_name,
                'amount' => round($vendor_bearing_fees, 2),
                'type' => 'program_fees',
                'description' => Invoice::VENDOR_FEE_BEARING,
                'title' => $fee->fee_name,
              ]);
            }
          } else {
            // Vendor Financing
            $anchor_bearing_fees =
              $fee->charge_type === 'fixed'
                ? round(
                  ($fee->anchor_bearing_discount / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value),
                  2
                )
                : round(
                  ($fee->anchor_bearing_discount / 100) *
                    (floor($legible_amount / $fee->per_amount) *
                      $fee->value *
                      (Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->invoice->due_date)) + 1)),
                  2
                );

            if ($anchor_bearing_fees > 0) {
              $this->paymentAccounts()->create([
                'account' => $fee_account,
                'account_name' => $fee_account_name,
                'amount' => round($anchor_bearing_fees, 2),
                'type' => 'program_fees',
                'description' =>
                  $this->program->programType->name == Program::VENDOR_FINANCING &&
                  $this->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE
                    ? Invoice::ANCHOR_FEE_BEARING
                    : Invoice::BUYER_FEE_BEARING,
                'title' => $fee->fee_name,
              ]);
            }

            $vendor_bearing_fees =
              $fee->charge_type === 'fixed'
                ? round(
                  ($fee->vendor_bearing_discount / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value),
                  2
                )
                : round(
                  ($fee->vendor_bearing_discount / 100) *
                    (floor($legible_amount / $fee->per_amount) *
                      $fee->value *
                      (Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->invoice->due_date)) + 1)),
                  2
                );

            if ($vendor_bearing_fees > 0) {
              $this->paymentAccounts()->create([
                'account' => $fee_account,
                'account_name' => $fee_account_name,
                'amount' => round($vendor_bearing_fees, 2),
                'type' => 'program_fees',
                'description' => Invoice::VENDOR_FEE_BEARING,
                'title' => $fee->fee_name,
              ]);
            }
          }
        }
      }
    }

    // Credit Fee Taxes to Fees taxes Account
    if ($fees_tax_amount > 0) {
      if ($vendor_fees->count() > 0) {
        foreach ($vendor_fees as $fee) {
          if ($fee->type == 'amount') {
            if ($fee->taxes) {
              $fees_tax_amount =
                $fee->charge_type === 'fixed'
                  ? round(($fee->taxes / 100) * $fee->value, 2)
                  : round(
                    ($fee->taxes / 100) *
                      $fee->value *
                      (Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->invoice->due_date)) + 1),
                    2
                  );
            }
          }

          if ($fee->type == 'percentage') {
            if ($fee->taxes) {
              $fees_tax_amount =
                $fee->charge_type === 'fixed'
                  ? round(($fee->taxes / 100) * (($fee->value / 100) * $legible_amount), 2)
                  : round(
                    ($fee->taxes / 100) *
                      (($fee->value / 100) * $legible_amount) *
                      (Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->invoice->due_date)) + 1),
                    2
                  );
            }
          }

          if ($fee->type == 'per amount') {
            if ($fee->taxes) {
              $fees_tax_amount =
                $fee->charge_type === 'fixed'
                  ? round(($fee->taxes / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value), 2)
                  : round(
                    ($fee->taxes / 100) *
                      (floor($legible_amount / $fee->per_amount) *
                        $fee->value *
                        (Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->invoice->due_date)) + 1)),
                    2
                  );
            }
          }

          $tax_income_account = BankTaxRate::where('bank_id', $this->invoice->program->bank_id)
            ->where('value', $fee->taxes)
            ->where('status', 'active')
            ->first();

          if ($tax_income_account) {
            $this->paymentAccounts()->create([
              'account' => $tax_income_account->account_no,
              'account_name' => 'Tax Income Bank Account',
              'amount' => round($fees_tax_amount, 2),
              'type' => 'tax_on_fees',
              'description' => 'Tax on Fees for ' . $fee->fee_name,
            ]);
          } else {
            $this->paymentAccounts()->create([
              'account' => $fees_income_bank_account ? $fees_income_bank_account->value : 'Fees Income Bank Account',
              'account_name' => $fees_income_bank_account
                ? $fees_income_bank_account->name
                : 'Fees Income Bank Account',
              'amount' => round($fees_tax_amount, 2),
              'type' => 'tax_on_fees',
              'description' => 'Tax on Fees for ' . $fee->fee_name,
            ]);
          }
        }
      }
    }
  }

  public function createCbsTransactions()
  {
    // Receiver
    // Get payment request account
    $payment_request_accounts = $this->paymentAccounts;
    $program_vendor_configuration = null;

    if ($this->invoice->program->programType->name === Program::VENDOR_FINANCING) {
      if ($this->invoice->program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE) {
        $program_vendor_configuration = ProgramVendorConfiguration::where('company_id', $this->invoice->company_id)
          ->where('program_id', $this->invoice->program_id)
          ->first();

        foreach ($payment_request_accounts as $request_account) {
          switch ($request_account->type) {
            case 'vendor_account':
              $program_vendor_bank_details = ProgramVendorBankDetail::where('company_id', $this->invoice->company_id)
                ->where('program_id', $this->invoice->program_id)
                ->where('account_number', $request_account->account)
                ->select('bank_name')
                ->first();
              CbsTransaction::create([
                'bank_id' => $this->invoice?->program?->bank?->id,
                'payment_request_id' => $this->id,
                'debit_from_account' => $program_vendor_configuration->payment_account_number,
                'debit_from_account_name' => $this->invoice?->program?->name,
                'debit_from_account_description' => $program_vendor_configuration->payment_account_number . ' (Bank)',
                'credit_to_account' => $request_account->account,
                'credit_to_account_name' => $request_account->account_name,
                'credit_to_account_description' =>
                  $this->invoice->company->name .
                  '(' .
                  $program_vendor_bank_details?->bank_name .
                  ':' .
                  $request_account->account .
                  ')',
                'amount' => $request_account->amount,
                'transaction_created_date' => now()->format('Y-m-d'),
                'pay_date' => now()->format('Y-m-d'),
                'status' => 'Created',
                'transaction_type' => CbsTransaction::PAYMENT_DISBURSEMENT,
                'product' => $this->invoice->program->programType->name,
              ]);
              break;
            case 'discount':
              if ($this->invoice->discount_charge_type == Invoice::FRONT_ENDED) {
                // Front Ended Discount
                if ($request_account->description == Invoice::VENDOR_DISCOUNT_BEARING) {
                  CbsTransaction::create([
                    'bank_id' => $this->invoice?->program?->bank?->id,
                    'payment_request_id' => $this->id,
                    'debit_from_account' => $program_vendor_configuration->payment_account_number,
                    'debit_from_account_name' => $this->invoice?->program?->name,
                    'debit_from_account_description' =>
                      $program_vendor_configuration->payment_account_number . ' (Bank)',
                    'credit_to_account' => $request_account->account,
                    'credit_to_account_name' => $request_account->account_name,
                    'credit_to_account_description' =>
                      CbsTransaction::ACCRUAL_POSTED_INTEREST . ' (Bank: ' . $request_account->account . ')',
                    'amount' => $request_account->amount,
                    'transaction_created_date' => now()->format('Y-m-d'),
                    'pay_date' => now()->format('Y-m-d'),
                    'status' => 'Created',
                    'transaction_type' => CbsTransaction::ACCRUAL_POSTED_INTEREST,
                    'product' => $this->invoice->program->programType->name,
                  ]);
                } elseif (
                  $request_account->description == Invoice::ANCHOR_DISCOUNT_BEARING ||
                  $request_account->description == Invoice::BUYER_DISCOUNT_BEARING
                ) {
                  // Create new payment request to credit from anchor/buyer CASA
                  $anchor_discount_payment_request = self::create([
                    'reference_number' => $request_account->paymentRequest->reference_number,
                    'invoice_id' => $this->invoice_id,
                    'amount' => $request_account->amount,
                    'payment_request_date' => now()->format('Y-m-d'),
                    'status' => 'approved',
                    'approval_status' => 'approved',
                  ]);

                  $request_account->update([
                    'payment_request_id' => $anchor_discount_payment_request->id,
                  ]);

                  // Get the bank account of the anchor/buyer
                  if ($this->invoice->program->programType->name == Program::DEALER_FINANCING) {
                    $anchor_bank_details = ProgramBankDetails::where('program_id', $this->invoice->program_id)->first();
                    $anchor_name = $this->invoice->program->anchor->name;
                  } else {
                    if ($this->invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
                      $anchor_bank_details = ProgramBankDetails::where(
                        'program_id',
                        $this->invoice->program_id
                      )->first();
                      $anchor_name = $this->invoice->program->anchor->name;
                    } else {
                      $anchor_bank_details = ProgramVendorBankDetail::where('program_id', $this->invoice->program_id)
                        ->where('buyer_id', $this->invoice->buyer_id)
                        ->first();
                      $anchor_name = $anchor_bank_details->buyer->name;
                    }
                  }

                  CbsTransaction::create([
                    'bank_id' => $this->invoice?->program?->bank?->id,
                    'payment_request_id' => $anchor_discount_payment_request->id,
                    'debit_from_account' => $anchor_bank_details->account_number,
                    'debit_from_account_name' => $anchor_bank_details->name_as_per_bank,
                    'debit_from_account_description' =>
                      $anchor_name .
                      '(' .
                      $anchor_bank_details->bank_name .
                      ': ' .
                      $anchor_bank_details->account_number .
                      ')',
                    'credit_to_account' => $request_account->account,
                    'credit_to_account_name' => $request_account->account_name,
                    'credit_to_account_description' =>
                      CbsTransaction::ACCRUAL_POSTED_INTEREST . ' (Bank: ' . $request_account->account . ')',
                    'amount' => $request_account->amount,
                    'transaction_created_date' => now()->format('Y-m-d'),
                    'pay_date' => now()->format('Y-m-d'),
                    'status' => 'Created',
                    'transaction_type' => CbsTransaction::ACCRUAL_POSTED_INTEREST,
                    'product' => $this->invoice->program->programType->name,
                  ]);
                }
              } else {
                // Rear Ended Discount
                // Credit Discount Receivable to Unrealized Discount Account
                $discount_receivable_bank_account = BankProductsConfiguration::where(
                  'bank_id',
                  $this->invoice->program->bank_id
                )
                  ->where('section', 'Vendor Finance Receivable')
                  ->where('product_code_id', $this->invoice->program->program_code_id)
                  ->where('product_type_id', $this->invoice->program->program_type_id)
                  ->where('name', 'Discount Receivable Account')
                  ->first();

                $unrealized_discount_bank_account = BankProductsConfiguration::where(
                  'bank_id',
                  $this->invoice->program->bank_id
                )
                  ->where('section', 'Vendor Finance Receivable')
                  ->where('product_code_id', $this->invoice->program->program_code_id)
                  ->where('product_type_id', $this->invoice->program->program_type_id)
                  ->where('name', 'Unrealised Discount Account')
                  ->first();

                $reference_number =
                  'VFR0' .
                  $this->invoice->program->bank_id .
                  '' .
                  now()->format('y') .
                  '000' .
                  Helpers::generateSequentialReferenceNumber(
                    $this->invoice->program->bank_id,
                    Program::VENDOR_FINANCING,
                    [Program::VENDOR_FINANCING_RECEIVABLE]
                  );

                $discount_transaction = self::create([
                  'reference_number' => $reference_number,
                  'invoice_id' => $this->invoice_id,
                  'amount' => $request_account->amount,
                  'payment_request_date' => now()->format('Y-m-d'),
                  'status' => 'approved',
                  'approval_status' => 'approved',
                ]);

                $discount_transaction->paymentAccounts()->create([
                  'account' => $unrealized_discount_bank_account->value,
                  'account_name' => $unrealized_discount_bank_account->name,
                  'amount' => $request_account->amount,
                  'type' => 'unrealized discount',
                  'description' => 'discount to be earned',
                ]);

                CbsTransaction::create([
                  'bank_id' => $this->invoice?->program?->bank?->id,
                  'payment_request_id' => $discount_transaction->id,
                  'debit_from_account' => $discount_receivable_bank_account->value,
                  'debit_from_account_name' => $discount_receivable_bank_account->name,
                  'debit_from_account_description' => $discount_receivable_bank_account->value . ' (Bank)',
                  'credit_to_account' => $unrealized_discount_bank_account->value,
                  'credit_to_account_name' => $unrealized_discount_bank_account->name,
                  'credit_to_account_description' =>
                    CbsTransaction::ACCRUAL_POSTED_INTEREST .
                    ' (Bank: ' .
                    $unrealized_discount_bank_account->value .
                    ')',
                  'amount' => $request_account->amount,
                  'transaction_created_date' => now()->format('Y-m-d'),
                  'pay_date' => now()->format('Y-m-d'),
                  'status' => 'Created',
                  'transaction_type' => CbsTransaction::ACCRUAL_POSTED_INTEREST,
                  'product' => $this->invoice->program->programType->name,
                ]);
              }
              break;
            case 'program_fees':
              if ($this->invoice->fee_charge_type == Invoice::FRONT_ENDED) {
                if ($request_account->description == Invoice::VENDOR_FEE_BEARING) {
                  CbsTransaction::create([
                    'bank_id' => $this->invoice?->program?->bank?->id,
                    'payment_request_id' => $this->id,
                    'debit_from_account' => $program_vendor_configuration->payment_account_number,
                    'debit_from_account_name' => $this->invoice?->program?->name,
                    'debit_from_account_description' =>
                      $program_vendor_configuration->payment_account_number . ' (Bank)',
                    'credit_to_account' => $request_account->account,
                    'credit_to_account_name' => $request_account->account_name,
                    'credit_to_account_description' => 'Charges (Bank: ' . $request_account->account . ')',
                    'amount' => $request_account->amount,
                    'transaction_created_date' => now()->format('Y-m-d'),
                    'pay_date' => now()->format('Y-m-d'),
                    'status' => 'Created',
                    'transaction_type' => CbsTransaction::FEES_CHARGES,
                    'product' => $this->invoice->program->programType->name,
                  ]);
                } elseif (
                  $request_account->description == Invoice::ANCHOR_FEE_BEARING ||
                  $request_account->description == Invoice::BUYER_FEE_BEARING
                ) {
                  $reference_number =
                    'VFR0' .
                    $this->invoice->program->bank_id .
                    '' .
                    now()->format('y') .
                    '000' .
                    Helpers::generateSequentialReferenceNumber(
                      $this->invoice->program->bank_id,
                      Program::VENDOR_FINANCING,
                      [Program::VENDOR_FINANCING_RECEIVABLE]
                    );

                  // Create payment request to credit anchor/buyer CASA account
                  $anchor_fees_payment_request = self::create([
                    'reference_number' => $reference_number,
                    'invoice_id' => $this->invoice_id,
                    'amount' => $request_account->amount,
                    'payment_request_date' => now()->format('Y-m-d'),
                    'status' => 'approved',
                    'approval_status' => 'approved',
                  ]);

                  $request_account->update([
                    'payment_request_id' => $anchor_fees_payment_request->id,
                  ]);

                  // Get the bank account of the anchor/buyer
                  if ($this->invoice->program->programType->name == Program::DEALER_FINANCING) {
                    $anchor_bank_details = ProgramBankDetails::where('program_id', $this->invoice->program_id)->first();
                    $anchor_name = $this->invoice->program->anchor->name;
                  } else {
                    if ($this->invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
                      $anchor_bank_details = ProgramBankDetails::where(
                        'program_id',
                        $this->invoice->program_id
                      )->first();
                      $anchor_name = $this->invoice->program->anchor->name;
                    } else {
                      $anchor_bank_details = ProgramVendorBankDetail::where('program_id', $this->invoice->program_id)
                        ->where('buyer_id', $this->invoice->buyer_id)
                        ->first();
                      $anchor_name = $anchor_bank_details->buyer->name;
                    }
                  }

                  CbsTransaction::create([
                    'bank_id' => $this->invoice?->program?->bank?->id,
                    'payment_request_id' => $anchor_fees_payment_request->id,
                    'debit_from_account' => $anchor_bank_details->account_number,
                    'debit_from_account_name' => $anchor_bank_details->name_as_per_bank,
                    'debit_from_account_description' =>
                      $anchor_name .
                      ' (' .
                      $anchor_bank_details->bank_name .
                      ': ' .
                      $anchor_bank_details->account_number .
                      ')',
                    'credit_to_account' => $request_account->account,
                    'credit_to_account_name' => $request_account->account_name,
                    'credit_to_account_description' => 'Charges (Bank: ' . $request_account->account . ')',
                    'amount' => $request_account->amount,
                    'transaction_created_date' => now()->format('Y-m-d'),
                    'pay_date' => now()->format('Y-m-d'),
                    'status' => 'Created',
                    'transaction_type' => CbsTransaction::FEES_CHARGES,
                    'product' => $this->invoice->program->programType->name,
                  ]);
                }
              } else {
                // Credit to Fee Income Account from Discount Receivable Account
                $discount_receivable_bank_account = BankProductsConfiguration::where(
                  'bank_id',
                  $this->invoice->program->bank_id
                )
                  ->where('section', 'Vendor Finance Receivable')
                  ->where('product_code_id', $this->invoice->program->program_code_id)
                  ->where('product_type_id', $this->invoice->program->program_type_id)
                  ->where('name', 'Discount Receivable Account')
                  ->first();

                $fee_income_bank_account = BankProductsConfiguration::where('bank_id', $this->invoice->program->bank_id)
                  ->where('section', 'Vendor Finance Receivable')
                  ->where('product_code_id', $this->invoice->program->program_code_id)
                  ->where('product_type_id', $this->invoice->program->program_type_id)
                  ->where('name', 'Fee Income Account')
                  ->first();

                $words = explode(' ', $this->invoice->company->name);
                $acronym = '';

                foreach ($words as $w) {
                  $acronym .= mb_substr($w, 0, 1);
                }

                $reference_number =
                  'VFR0' .
                  $this->invoice->program->bank_id .
                  '' .
                  now()->format('y') .
                  '000' .
                  Helpers::generateSequentialReferenceNumber(
                    $this->invoice->program->bank_id,
                    Program::VENDOR_FINANCING,
                    [Program::VENDOR_FINANCING_RECEIVABLE]
                  );

                $discount_transaction = self::create([
                  'reference_number' => $reference_number,
                  'invoice_id' => $this->invoice_id,
                  'amount' => $request_account->amount,
                  'payment_request_date' => now()->format('Y-m-d'),
                  'status' => 'approved',
                  'approval_status' => 'approved',
                ]);

                CbsTransaction::create([
                  'bank_id' => $this->invoice?->program?->bank?->id,
                  'payment_request_id' => $discount_transaction->id,
                  'debit_from_account' => $discount_receivable_bank_account->value,
                  'debit_from_account_name' => $discount_receivable_bank_account->name,
                  'debit_from_account_description' => $discount_receivable_bank_account->value . ' (Bank)',
                  'credit_to_account' => $fee_income_bank_account->value,
                  'credit_to_account_name' => $fee_income_bank_account->name,
                  'credit_to_account_description' => 'Charges (Bank: ' . $fee_income_bank_account->value . ')',
                  'amount' => $request_account->amount,
                  'transaction_created_date' => now()->format('Y-m-d'),
                  'pay_date' => now()->format('Y-m-d'),
                  'status' => 'Created',
                  'transaction_type' => CbsTransaction::FEES_CHARGES,
                  'product' => $this->invoice->program->programType->name,
                ]);
              }
              break;
            case 'program_fees_taxes':
              if ($this->invoice->fee_charge_type == Invoice::FRONT_ENDED) {
                CbsTransaction::create([
                  'bank_id' => $this->invoice?->program?->bank?->id,
                  'payment_request_id' => $this->id,
                  'debit_from_account' => $program_vendor_configuration->payment_account_number,
                  'debit_from_account_name' => $this->invoice?->program?->name,
                  'debit_from_account_description' => $program_vendor_configuration->payment_account_number . ' (Bank)',
                  'credit_to_account' => $request_account->account,
                  'credit_to_account_name' => $request_account->account_name,
                  'credit_to_account_description' => 'Charges (Bank: ' . $request_account->account . ')',
                  'amount' => $request_account->amount,
                  'transaction_created_date' => now()->format('Y-m-d'),
                  'pay_date' => now()->format('Y-m-d'),
                  'status' => 'Created',
                  'transaction_type' => CbsTransaction::FEES_CHARGES,
                  'product' => $this->invoice->program->programType->name,
                ]);
              } else {
                $discount_receivable_bank_account = BankProductsConfiguration::where(
                  'bank_id',
                  $this->invoice->program->bank_id
                )
                  ->where('section', 'Vendor Finance Receivable')
                  ->where('product_code_id', $this->invoice->program->program_code_id)
                  ->where('product_type_id', $this->invoice->program->program_type_id)
                  ->where('name', 'Discount Receivable Account')
                  ->first();

                $fee_income_bank_account = BankProductsConfiguration::where('bank_id', $this->invoice->program->bank_id)
                  ->where('section', 'Vendor Finance Receivable')
                  ->where('product_code_id', $this->invoice->program->program_code_id)
                  ->where('product_type_id', $this->invoice->program->program_type_id)
                  ->where('name', 'Fee Income Account')
                  ->first();

                $reference_number =
                  'VFR0' .
                  $this->invoice->program->bank_id .
                  '' .
                  now()->format('y') .
                  '000' .
                  Helpers::generateSequentialReferenceNumber(
                    $this->invoice->program->bank_id,
                    Program::VENDOR_FINANCING,
                    [Program::VENDOR_FINANCING_RECEIVABLE]
                  );

                $fee_taxes_transaction = self::create([
                  'reference_number' => $reference_number,
                  'invoice_id' => $this->invoice_id,
                  'amount' => $request_account->amount,
                  'payment_request_date' => now()->format('Y-m-d'),
                  'status' => 'approved',
                  'approval_status' => 'approved',
                ]);

                $fee_taxes_transaction->paymentAccounts()->create([
                  'account' => $fee_income_bank_account->value,
                  'account_name' => $fee_income_bank_account->name,
                  'amount' => $request_account->amount,
                  'type' => 'tax on fees',
                  'description' => 'Tax Charged on Fees - Rear Ended',
                ]);

                CbsTransaction::create([
                  'bank_id' => $this->invoice?->program?->bank?->id,
                  'payment_request_id' => $fee_taxes_transaction->id,
                  'debit_from_account' => $discount_receivable_bank_account->value,
                  'debit_from_account_name' => $discount_receivable_bank_account->name,
                  'debit_from_account_description' => $discount_receivable_bank_account->value . ' (Bank)',
                  'credit_to_account' => $fee_income_bank_account->value,
                  'credit_to_account_name' => $fee_income_bank_account->name,
                  'credit_to_account_description' => 'Charges (Bank: ' . $fee_income_bank_account->value . ')',
                  'amount' => $request_account->amount,
                  'transaction_created_date' => now()->format('Y-m-d'),
                  'pay_date' => now()->format('Y-m-d'),
                  'status' => 'Created',
                  'transaction_type' => CbsTransaction::FEES_CHARGES,
                  'product' => $this->invoice->program->programType->name,
                ]);
              }
              break;
            case 'tax_on_discount':
              if ($this->invoice->discount_charge_type == Invoice::FRONT_ENDED) {
                CbsTransaction::create([
                  'bank_id' => $this->invoice?->program?->bank?->id,
                  'payment_request_id' => $this->id,
                  'debit_from_account' => $program_vendor_configuration->payment_account_number,
                  'debit_from_account_name' => $this->invoice->program->name,
                  'debit_from_account_description' => $program_vendor_configuration->payment_account_number . ' (Bank)',
                  'credit_to_account' => $request_account->account,
                  'credit_to_account_name' => $request_account->account_name,
                  'credit_to_account_description' => 'Charges (Bank: ' . $request_account->account . ')',
                  'amount' => $request_account->amount,
                  'transaction_created_date' => now()->format('Y-m-d'),
                  'pay_date' => now()->format('Y-m-d'),
                  'status' => 'Created',
                  'transaction_type' => CbsTransaction::FEES_CHARGES,
                  'product' => $this->invoice->program->programType->name,
                ]);
              } else {
                $discount_receivable_bank_account = BankProductsConfiguration::where(
                  'bank_id',
                  $this->invoice->program->bank_id
                )
                  ->where('section', 'Vendor Finance Receivable')
                  ->where('product_code_id', $this->invoice->program->program_code_id)
                  ->where('product_type_id', $this->invoice->program->program_type_id)
                  ->where('name', 'Discount Receivable Account')
                  ->first();

                $fee_income_bank_account = BankProductsConfiguration::where('bank_id', $this->invoice->program->bank_id)
                  ->where('section', 'Vendor Finance Receivable')
                  ->where('product_code_id', $this->invoice->program->program_code_id)
                  ->where('product_type_id', $this->invoice->program->program_type_id)
                  ->where('name', 'Fee Income Account')
                  ->first();

                $reference_number =
                  'VFR0' .
                  $this->invoice->program->bank_id .
                  '' .
                  now()->format('y') .
                  '000' .
                  Helpers::generateSequentialReferenceNumber(
                    $this->invoice->program->bank_id,
                    Program::VENDOR_FINANCING,
                    [Program::VENDOR_FINANCING_RECEIVABLE]
                  );

                $discount_tax_transaction = self::create([
                  'reference_number' => $reference_number,
                  'invoice_id' => $this->invoice_id,
                  'amount' => $request_account->amount,
                  'payment_request_date' => now()->format('Y-m-d'),
                  'status' => 'approved',
                  'approval_status' => 'approved',
                ]);

                $discount_tax_transaction->paymentAccounts()->create([
                  'account' => $fee_income_bank_account->value,
                  'account_name' => $fee_income_bank_account->name,
                  'amount' => $request_account->amount,
                  'type' => 'tax on discount',
                  'description' => 'Tax Charged on Discount - Rear Ended',
                ]);

                CbsTransaction::create([
                  'bank_id' => $this->invoice?->program?->bank?->id,
                  'payment_request_id' => $discount_tax_transaction->id,
                  'debit_from_account' => $discount_receivable_bank_account->value,
                  'debit_from_account_name' => $discount_receivable_bank_account->name,
                  'debit_from_account_description' => $discount_receivable_bank_account->value . ' (Bank)',
                  'credit_to_account' => $fee_income_bank_account->value,
                  'credit_to_account_name' => $fee_income_bank_account->name,
                  'credit_to_account_description' => 'Charges (Bank: ' . $fee_income_bank_account->value . ')',
                  'amount' => $request_account->amount,
                  'transaction_created_date' => now()->format('Y-m-d'),
                  'pay_date' => now()->format('Y-m-d'),
                  'status' => 'Created',
                  'transaction_type' => CbsTransaction::FEES_CHARGES,
                  'product' => $this->invoice->program->programType->name,
                ]);
              }
              break;
            case 'tax_on_fees':
              if ($this->invoice->fee_charge_type == Invoice::FRONT_ENDED) {
                CbsTransaction::create([
                  'bank_id' => $this->invoice?->program?->bank?->id,
                  'payment_request_id' => $this->id,
                  'debit_from_account' => $program_vendor_configuration->payment_account_number,
                  'debit_from_account_name' => $this->invoice->program->name,
                  'debit_from_account_description' => $program_vendor_configuration->payment_account_number . ' (Bank)',
                  'credit_to_account' => $request_account->account,
                  'credit_to_account_name' => $request_account->account_name,
                  'credit_to_account_description' => 'Charges (Bank: ' . $request_account->account . ')',
                  'amount' => $request_account->amount,
                  'transaction_created_date' => now()->format('Y-m-d'),
                  'pay_date' => now()->format('Y-m-d'),
                  'status' => 'Created',
                  'transaction_type' => CbsTransaction::FEES_CHARGES,
                  'product' => $this->invoice->program->programType->name,
                ]);
              } else {
                $discount_receivable_bank_account = BankProductsConfiguration::where(
                  'bank_id',
                  $this->invoice->program->bank_id
                )
                  ->where('section', 'Vendor Finance Receivable')
                  ->where('product_code_id', $this->invoice->program->program_code_id)
                  ->where('product_type_id', $this->invoice->program->program_type_id)
                  ->where('name', 'Discount Receivable Account')
                  ->first();

                $fee_income_bank_account = BankProductsConfiguration::where('bank_id', $this->invoice->program->bank_id)
                  ->where('section', 'Vendor Finance Receivable')
                  ->where('product_code_id', $this->invoice->program->program_code_id)
                  ->where('product_type_id', $this->invoice->program->program_type_id)
                  ->where('name', 'Fee Income Account')
                  ->first();

                $reference_number =
                  'VFR0' .
                  $this->invoice->program->bank_id .
                  '' .
                  now()->format('y') .
                  '000' .
                  Helpers::generateSequentialReferenceNumber(
                    $this->invoice->program->bank_id,
                    Program::VENDOR_FINANCING,
                    [Program::VENDOR_FINANCING_RECEIVABLE]
                  );

                $fee_taxes_transaction = self::create([
                  'reference_number' => $reference_number,
                  'invoice_id' => $this->invoice_id,
                  'amount' => $request_account->amount,
                  'payment_request_date' => now()->format('Y-m-d'),
                  'status' => 'approved',
                  'approval_status' => 'approved',
                ]);

                $fee_taxes_transaction->paymentAccounts()->create([
                  'account' => $fee_income_bank_account->value,
                  'account_name' => $fee_income_bank_account->name,
                  'amount' => $request_account->amount,
                  'type' => 'tax on fees',
                  'description' => 'Tax Charged on Fees - Rear Ended',
                ]);

                CbsTransaction::create([
                  'bank_id' => $this->invoice?->program?->bank?->id,
                  'payment_request_id' => $fee_taxes_transaction->id,
                  'debit_from_account' => $discount_receivable_bank_account->value,
                  'debit_from_account_name' => $discount_receivable_bank_account->name,
                  'debit_from_account_description' => $discount_receivable_bank_account->value . ' (Bank)',
                  'credit_to_account' => $fee_income_bank_account->value,
                  'credit_to_account_name' => $fee_income_bank_account->name,
                  'credit_to_account_description' => 'Charges (Bank: ' . $fee_income_bank_account->value . ')',
                  'amount' => $request_account->amount,
                  'transaction_created_date' => now()->format('Y-m-d'),
                  'pay_date' => now()->format('Y-m-d'),
                  'status' => 'Created',
                  'transaction_type' => CbsTransaction::FEES_CHARGES,
                  'product' => $this->invoice->program->programType->name,
                ]);
              }
              break;
          }
        }
      } else {
        // Factoring
        $program_vendor_configuration = ProgramVendorConfiguration::where('buyer_id', $this->invoice->buyer_id)
          ->where('company_id', $this->invoice->company_id)
          ->where('program_id', $this->invoice->program_id)
          ->first();

        foreach ($payment_request_accounts as $request_account) {
          switch ($request_account->type) {
            case 'vendor_account':
              $vendor_bank_account = ProgramBankDetails::where('program_id', $this->invoice->program_id)
                ->where('account_number', $request_account->account)
                ->select('bank_name')
                ->first();

              CbsTransaction::create([
                'bank_id' => $this->invoice?->program?->bank?->id,
                'payment_request_id' => $this->id,
                'debit_from_account' => $program_vendor_configuration->payment_account_number,
                'debit_from_account_name' => $this->invoice?->program?->name,
                'debit_from_account_description' => $program_vendor_configuration->payment_account_number . ' (Bank)',
                'credit_to_account' => $request_account->account,
                'credit_to_account_name' => $request_account->account_name,
                'credit_to_account_description' =>
                  $this->invoice?->company?->name .
                  ' (' .
                  $vendor_bank_account->bank_name .
                  ': ' .
                  $request_account->account .
                  ')',
                'amount' => $request_account->amount,
                'transaction_created_date' => now()->format('Y-m-d'),
                'pay_date' => now()->format('Y-m-d'),
                'status' => 'Created',
                'transaction_type' => CbsTransaction::PAYMENT_DISBURSEMENT,
                'product' => $this->invoice->program->programType->name,
              ]);
              break;
            case 'discount':
              if ($this->invoice->discount_charge_type == Invoice::FRONT_ENDED) {
                if ($request_account->description == Invoice::VENDOR_DISCOUNT_BEARING) {
                  CbsTransaction::create([
                    'bank_id' => $this->invoice?->program?->bank?->id,
                    'payment_request_id' => $this->id,
                    'debit_from_account' => $program_vendor_configuration->payment_account_number,
                    'debit_from_account_name' => $this->invoice->program->name,
                    'debit_from_account_description' =>
                      $program_vendor_configuration->payment_account_number . ' (Bank)',
                    'credit_to_account' => $request_account->account,
                    'credit_to_account_name' => $request_account->account_name,
                    'credit_to_account_description' =>
                      CbsTransaction::ACCRUAL_POSTED_INTEREST . ' (Bank: ' . $request_account->account_name . ')',
                    'amount' => $request_account->amount,
                    'transaction_created_date' => now()->format('Y-m-d'),
                    'pay_date' => now()->format('Y-m-d'),
                    'status' => 'Created',
                    'transaction_type' => CbsTransaction::ACCRUAL_POSTED_INTEREST,
                    'product' => $this->invoice->program->programType->name,
                  ]);
                } elseif (
                  $request_account->description == Invoice::ANCHOR_DISCOUNT_BEARING ||
                  $request_account->description == Invoice::BUYER_DISCOUNT_BEARING
                ) {
                  // Create new payment request to credit from anchor/buyer CASA
                  $anchor_discount_payment_request = self::create([
                    'reference_number' => $request_account->paymentRequest->reference_number,
                    'invoice_id' => $this->invoice_id,
                    'amount' => $request_account->amount,
                    'payment_request_date' => now()->format('Y-m-d'),
                    'status' => 'approved',
                    'approval_status' => 'approved',
                  ]);

                  $request_account->update([
                    'payment_request_id' => $anchor_discount_payment_request->id,
                  ]);

                  // Get the bank account of the anchor/buyer
                  if ($this->invoice->program->programType->name == Program::DEALER_FINANCING) {
                    $anchor_bank_details = ProgramBankDetails::where('program_id', $this->invoice->program_id)->first();
                    $anchor_name = $this->invoice->program->anchor->name;
                  } else {
                    if ($this->invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
                      $anchor_bank_details = ProgramBankDetails::where(
                        'program_id',
                        $this->invoice->program_id
                      )->first();
                      $anchor_name = $this->invoice->program->anchor->name;
                    } else {
                      $anchor_bank_details = ProgramVendorBankDetail::where('program_id', $this->invoice->program_id)
                        ->where('buyer_id', $this->invoice->buyer_id)
                        ->first();
                      $anchor_name = $anchor_bank_details->buyer->name;
                    }
                  }

                  CbsTransaction::create([
                    'bank_id' => $this->invoice?->program?->bank?->id,
                    'payment_request_id' => $anchor_discount_payment_request->id,
                    'debit_from_account' => $anchor_bank_details->account_number,
                    'debit_from_account_name' => $anchor_bank_details->name_as_per_bank,
                    'debit_from_account_description' =>
                      $anchor_name .
                      ' (' .
                      $anchor_bank_details?->bank_name .
                      ':' .
                      $anchor_bank_details?->account_number .
                      ')',
                    'credit_to_account' => $request_account->account,
                    'credit_to_account_name' => $request_account->account_name,
                    'credit_to_account_description' =>
                      CbsTransaction::ACCRUAL_POSTED_INTEREST . ' (Bank: ' . $request_account->account . ')',
                    'amount' => $request_account->amount,
                    'transaction_created_date' => now()->format('Y-m-d'),
                    'pay_date' => now()->format('Y-m-d'),
                    'status' => 'Created',
                    'transaction_type' => CbsTransaction::ACCRUAL_POSTED_INTEREST,
                    'product' => $this->invoice->program->programType->name,
                  ]);
                }
              } else {
                $words = explode(' ', $this->invoice->company->name);
                $acronym = '';

                foreach ($words as $w) {
                  $acronym .= mb_substr($w, 0, 1);
                }

                // Get Bank Configured Receivable Accounts
                if ($this->invoice->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE) {
                  // Factoring without recourse
                  $discount_receivable_bank_account = BankProductsConfiguration::where(
                    'bank_id',
                    $this->invoice->program->bank_id
                  )
                    ->where('section', 'Factoring Without Recourse')
                    ->where('product_code_id', $this->invoice->program->program_code_id)
                    ->where('product_type_id', $this->invoice->program->program_type_id)
                    ->where('name', 'Discount Receivable Account')
                    ->first();

                  $unrealized_discount_bank_account = BankProductsConfiguration::where(
                    'bank_id',
                    $this->invoice->program->bank_id
                  )
                    ->where('section', 'Factoring Without Recourse')
                    ->where('product_code_id', $this->invoice->program->program_code_id)
                    ->where('product_type_id', $this->invoice->program->program_type_id)
                    ->where('name', 'Unrealised Discount Account')
                    ->first();

                  $reference_number =
                    'FWR0' .
                    $this->invoice->program->bank_id .
                    '' .
                    now()->format('y') .
                    '000' .
                    Helpers::generateSequentialReferenceNumber(
                      $this->invoice->program->bank_id,
                      Program::VENDOR_FINANCING,
                      [Program::FACTORING_WITH_RECOURSE, Program::FACTORING_WITHOUT_RECOURSE]
                    );
                } else {
                  // Factoring with recourse
                  $discount_receivable_bank_account = BankProductsConfiguration::where(
                    'bank_id',
                    $this->invoice->program->bank_id
                  )
                    ->where('section', 'Factoring With Recourse')
                    ->where('product_code_id', $this->invoice->program->program_code_id)
                    ->where('product_type_id', $this->invoice->program->program_type_id)
                    ->where('name', 'Discount Receivable Account')
                    ->first();

                  $unrealized_discount_bank_account = BankProductsConfiguration::where(
                    'bank_id',
                    $this->invoice->program->bank_id
                  )
                    ->where('section', 'Factoring With Recourse')
                    ->where('product_code_id', $this->invoice->program->program_code_id)
                    ->where('product_type_id', $this->invoice->program->program_type_id)
                    ->where('name', 'Unrealised Discount Account')
                    ->first();

                  $reference_number =
                    'FR0' .
                    $this->invoice->program->bank_id .
                    '' .
                    now()->format('y') .
                    '000' .
                    Helpers::generateSequentialReferenceNumber(
                      $this->invoice->program->bank_id,
                      Program::VENDOR_FINANCING,
                      [Program::VENDOR_FINANCING_RECEIVABLE]
                    );
                }

                $discount_transaction = self::create([
                  'reference_number' => $reference_number,
                  'invoice_id' => $this->invoice_id,
                  'amount' => $request_account->amount,
                  'payment_request_date' => now()->format('Y-m-d'),
                  'status' => 'approved',
                  'approval_status' => 'approved',
                ]);

                CbsTransaction::create([
                  'bank_id' => $this->invoice?->program?->bank?->id,
                  'payment_request_id' => $discount_transaction->id,
                  'debit_from_account' => $discount_receivable_bank_account->value,
                  'debit_from_account_name' => $discount_receivable_bank_account->name,
                  'debit_from_account_description' => $discount_receivable_bank_account->value . ' (Bank)',
                  'credit_to_account' => $unrealized_discount_bank_account->value,
                  'credit_to_account_name' => $unrealized_discount_bank_account->name,
                  'credit_to_account_description' =>
                    CbsTransaction::ACCRUAL_POSTED_INTEREST .
                    ' (Bank: ' .
                    $unrealized_discount_bank_account->value .
                    ')',
                  'amount' => $request_account->amount,
                  'transaction_created_date' => now()->format('Y-m-d'),
                  'pay_date' => now()->format('Y-m-d'),
                  'status' => 'Created',
                  'transaction_type' => CbsTransaction::ACCRUAL_POSTED_INTEREST,
                  'product' => $this->invoice->program->programType->name,
                ]);
              }
              break;
            case 'program_fees':
              if ($this->invoice->fee_charge_type == Invoice::FRONT_ENDED) {
                if ($request_account->description == Invoice::VENDOR_FEE_BEARING) {
                  CbsTransaction::create([
                    'bank_id' => $this->invoice?->program?->bank?->id,
                    'payment_request_id' => $this->id,
                    'debit_from_account' => $program_vendor_configuration->payment_account_number,
                    'debit_from_account_name' => $this->invoice->program->name,
                    'debit_from_account_description' =>
                      $program_vendor_configuration->payment_account_number . ' (Bank)',
                    'credit_to_account' => $request_account->account,
                    'credit_to_account_name' => $request_account->account_name,
                    'credit_to_account_description' => 'Charges (Bank: ' . $request_account->account . ')',
                    'amount' => $request_account->amount,
                    'transaction_created_date' => now()->format('Y-m-d'),
                    'pay_date' => now()->format('Y-m-d'),
                    'status' => 'Created',
                    'transaction_type' => CbsTransaction::FEES_CHARGES,
                    'product' => $this->invoice->program->programType->name,
                  ]);
                } elseif (
                  $request_account->description == Invoice::ANCHOR_FEE_BEARING ||
                  $request_account->description == Invoice::BUYER_FEE_BEARING
                ) {
                  if ($this->invoice->program->programCode->name === Program::FACTORING_WITH_RECOURSE) {
                    $reference_number =
                      'FR0' .
                      $this->invoice->program->bank_id .
                      '' .
                      now()->format('y') .
                      '000' .
                      Helpers::generateSequentialReferenceNumber(
                        $this->invoice->program->bank_id,
                        Program::VENDOR_FINANCING,
                        [Program::FACTORING_WITH_RECOURSE, Program::FACTORING_WITHOUT_RECOURSE]
                      );
                  } else {
                    $reference_number =
                      'FWR0' .
                      $this->invoice->program->bank_id .
                      '' .
                      now()->format('y') .
                      '000' .
                      Helpers::generateSequentialReferenceNumber(
                        $this->invoice->program->bank_id,
                        Program::VENDOR_FINANCING,
                        [Program::FACTORING_WITH_RECOURSE, Program::FACTORING_WITHOUT_RECOURSE]
                      );
                  }

                  // Create payment request to credit anchor/buyer CASA account
                  $anchor_fees_payment_request = self::create([
                    'reference_number' => $reference_number,
                    'invoice_id' => $this->invoice_id,
                    'amount' => $request_account->amount,
                    'payment_request_date' => now()->format('Y-m-d'),
                    'status' => 'approved',
                    'approval_status' => 'approved',
                  ]);

                  $request_account->update([
                    'payment_request_id' => $anchor_fees_payment_request->id,
                  ]);

                  // Get the bank account of the anchor/buyer
                  if ($this->invoice->program->programType->name == Program::DEALER_FINANCING) {
                    $anchor_bank_details = ProgramBankDetails::where('program_id', $this->invoice->program_id)->first();
                    $anchor_name = $this->invoice->program->anchor->name;
                  } else {
                    if ($this->invoice->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
                      $anchor_bank_details = ProgramBankDetails::where(
                        'program_id',
                        $this->invoice->program_id
                      )->first();
                      $anchor_name = $this->invoice->program->anchor->name;
                    } else {
                      $anchor_bank_details = ProgramVendorBankDetail::where('program_id', $this->invoice->program_id)
                        ->where('buyer_id', $this->invoice->buyer_id)
                        ->first();
                      $anchor_name = $anchor_bank_details->buyer->name;
                    }
                  }

                  CbsTransaction::create([
                    'bank_id' => $this->invoice?->program?->bank?->id,
                    'payment_request_id' => $anchor_fees_payment_request->id,
                    'debit_from_account' => $anchor_bank_details->account_number,
                    'debit_from_account_name' => $anchor_bank_details->name_as_per_bank,
                    'debit_from_account_description' =>
                      $anchor_name .
                      '(' .
                      $anchor_bank_details?->bank_name .
                      ': ' .
                      $anchor_bank_details->account_number .
                      ')',
                    'credit_to_account' => $request_account->account,
                    'credit_to_account_name' => $request_account->account_name,
                    'credit_to_account_description' => 'Charges (Bank: ' . $request_account->account . ')',
                    'amount' => $request_account->amount,
                    'transaction_created_date' => now()->format('Y-m-d'),
                    'pay_date' => now()->format('Y-m-d'),
                    'status' => 'Created',
                    'transaction_type' => CbsTransaction::FEES_CHARGES,
                    'product' => $this->invoice->program->programType->name,
                  ]);
                }
              } else {
                // Factoring: Fee Is Rear Ended
                $words = explode(' ', $this->invoice->company->name);
                $acronym = '';

                foreach ($words as $w) {
                  $acronym .= mb_substr($w, 0, 1);
                }

                // Get Bank Configured Receivable Accounts
                if ($this->invoice->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE) {
                  // Factoring without recourse
                  $discount_receivable_bank_account = BankProductsConfiguration::where(
                    'bank_id',
                    $this->invoice->program->bank_id
                  )
                    ->where('section', 'Factoring Without Recourse')
                    ->where('product_code_id', $this->invoice->program->program_code_id)
                    ->where('product_type_id', $this->invoice->program->program_type_id)
                    ->where('name', 'Discount Receivable Account')
                    ->first();

                  $fee_income_bank_account = BankProductsConfiguration::where(
                    'bank_id',
                    $this->invoice->program->bank_id
                  )
                    ->where('section', 'Factoring Without Recourse')
                    ->where('product_code_id', $this->invoice->program->program_code_id)
                    ->where('product_type_id', $this->invoice->program->program_type_id)
                    ->where('name', 'Fee Income Account')
                    ->first();

                  $reference_number =
                    'FWR0' .
                    $this->invoice->program->bank_id .
                    '' .
                    now()->format('y') .
                    '000' .
                    Helpers::generateSequentialReferenceNumber(
                      $this->invoice->program->bank_id,
                      Program::VENDOR_FINANCING,
                      [Program::FACTORING_WITH_RECOURSE, Program::FACTORING_WITHOUT_RECOURSE]
                    );
                } else {
                  // Factoring with recourse
                  $discount_receivable_bank_account = BankProductsConfiguration::where(
                    'bank_id',
                    $this->invoice->program->bank_id
                  )
                    ->where('section', 'Factoring With Recourse')
                    ->where('product_code_id', $this->invoice->program->program_code_id)
                    ->where('product_type_id', $this->invoice->program->program_type_id)
                    ->where('name', 'Discount Receivable Account')
                    ->first();

                  $fee_income_bank_account = BankProductsConfiguration::where(
                    'bank_id',
                    $this->invoice->program->bank_id
                  )
                    ->where('section', 'Factoring With Recourse')
                    ->where('product_code_id', $this->invoice->program->program_code_id)
                    ->where('product_type_id', $this->invoice->program->program_type_id)
                    ->where('name', 'Fee Income Account')
                    ->first();

                  $reference_number =
                    'FR0' .
                    $this->invoice->program->bank_id .
                    '' .
                    now()->format('y') .
                    '000' .
                    Helpers::generateSequentialReferenceNumber(
                      $this->invoice->program->bank_id,
                      Program::VENDOR_FINANCING,
                      [Program::FACTORING_WITH_RECOURSE, Program::FACTORING_WITHOUT_RECOURSE]
                    );
                }

                $discount_transaction = self::create([
                  'reference_number' => $reference_number,
                  'invoice_id' => $this->invoice_id,
                  'amount' => $request_account->amount,
                  'payment_request_date' => now()->format('Y-m-d'),
                  'status' => 'approved',
                  'approval_status' => 'approved',
                ]);

                CbsTransaction::create([
                  'bank_id' => $this->invoice?->program?->bank?->id,
                  'payment_request_id' => $discount_transaction->id,
                  'debit_from_account' => $discount_receivable_bank_account->value,
                  'debit_from_account_name' => $discount_receivable_bank_account->name,
                  'debit_from_account_description' => $discount_receivable_bank_account->value . ' (Bank)',
                  'credit_to_account' => $fee_income_bank_account->value,
                  'credit_to_account_name' => $fee_income_bank_account->name,
                  'credit_to_account_description' => 'Charges (Bank: ' . $fee_income_bank_account->value . ')',
                  'amount' => $request_account->amount,
                  'transaction_created_date' => now()->format('Y-m-d'),
                  'pay_date' => now()->format('Y-m-d'),
                  'status' => 'Created',
                  'transaction_type' => CbsTransaction::FEES_CHARGES,
                  'product' => $this->invoice->program->programType->name,
                ]);
              }
              break;
            case 'program_fees_taxes':
              if ($this->invoice->fee_charge_type == Invoice::FRONT_ENDED) {
                CbsTransaction::create([
                  'bank_id' => $this->invoice?->program?->bank?->id,
                  'payment_request_id' => $this->id,
                  'debit_from_account' => $program_vendor_configuration->payment_account_number,
                  'debit_from_account_name' => $this->invoice->program->name,
                  'debit_from_account_description' => $program_vendor_configuration->payment_account_number . ' (Bank)',
                  'credit_to_account' => $request_account->account,
                  'credit_to_account_name' => $request_account->account_name,
                  'credit_to_account_description' => 'Charges (Bank: ' . $request_account->account . ')',
                  'amount' => $request_account->amount,
                  'transaction_created_date' => now()->format('Y-m-d'),
                  'pay_date' => now()->format('Y-m-d'),
                  'status' => 'Created',
                  'transaction_type' => CbsTransaction::FEES_CHARGES,
                  'product' => $this->invoice->program->programType->name,
                ]);
              } else {
                // Debit Discount Receivable and Credit Fee Income
                $words = explode(' ', $this->invoice->company->name);
                $acronym = '';

                foreach ($words as $w) {
                  $acronym .= mb_substr($w, 0, 1);
                }

                // Get Bank Configured Receivable Accounts
                if ($this->invoice->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE) {
                  // Factoring without recourse
                  $discount_receivable_bank_account = BankProductsConfiguration::where(
                    'bank_id',
                    $this->invoice->program->bank_id
                  )
                    ->where('section', 'Factoring Without Recourse')
                    ->where('product_code_id', $this->invoice->program->program_code_id)
                    ->where('product_type_id', $this->invoice->program->program_type_id)
                    ->where('name', 'Discount Receivable Account')
                    ->first();

                  $fee_income_bank_account = BankProductsConfiguration::where(
                    'bank_id',
                    $this->invoice->program->bank_id
                  )
                    ->where('section', 'Factoring Without Recourse')
                    ->where('product_code_id', $this->invoice->program->program_code_id)
                    ->where('product_type_id', $this->invoice->program->program_type_id)
                    ->where('name', 'Fee Income Account')
                    ->first();

                  $reference_number =
                    'FWR0' .
                    $this->invoice->program->bank_id .
                    '' .
                    now()->format('y') .
                    '000' .
                    Helpers::generateSequentialReferenceNumber(
                      $this->invoice->program->bank_id,
                      Program::VENDOR_FINANCING,
                      [Program::FACTORING_WITH_RECOURSE, Program::FACTORING_WITHOUT_RECOURSE]
                    );
                } else {
                  // Factoring with recourse
                  $discount_receivable_bank_account = BankProductsConfiguration::where(
                    'bank_id',
                    $this->invoice->program->bank_id
                  )
                    ->where('section', 'Factoring With Recourse')
                    ->where('product_code_id', $this->invoice->program->program_code_id)
                    ->where('product_type_id', $this->invoice->program->program_type_id)
                    ->where('name', 'Discount Receivable Account')
                    ->first();

                  $fee_income_bank_account = BankProductsConfiguration::where(
                    'bank_id',
                    $this->invoice->program->bank_id
                  )
                    ->where('section', 'Factoring With Recourse')
                    ->where('product_code_id', $this->invoice->program->program_code_id)
                    ->where('product_type_id', $this->invoice->program->program_type_id)
                    ->where('name', 'Fee Income Account')
                    ->first();

                  $reference_number =
                    'FR0' .
                    $this->invoice->program->bank_id .
                    '' .
                    now()->format('y') .
                    '000' .
                    Helpers::generateSequentialReferenceNumber(
                      $this->invoice->program->bank_id,
                      Program::VENDOR_FINANCING,
                      [Program::FACTORING_WITH_RECOURSE, Program::FACTORING_WITHOUT_RECOURSE]
                    );
                }

                $discount_transaction = self::create([
                  'reference_number' => $reference_number,
                  'invoice_id' => $this->invoice_id,
                  'amount' => $request_account->amount,
                  'payment_request_date' => now()->format('Y-m-d'),
                  'status' => 'approved',
                  'approval_status' => 'approved',
                ]);

                CbsTransaction::create([
                  'bank_id' => $this->invoice?->program?->bank?->id,
                  'payment_request_id' => $discount_transaction->id,
                  'debit_from_account' => $discount_receivable_bank_account->value,
                  'debit_from_account_name' => $discount_receivable_bank_account->name,
                  'debit_from_account_description' => $discount_receivable_bank_account->value . ' (Bank)',
                  'credit_to_account' => $fee_income_bank_account->value,
                  'credit_to_account_name' => $fee_income_bank_account->name,
                  'credit_to_account_description' => 'Charges (Bank' . $fee_income_bank_account->value . ')',
                  'amount' => $request_account->amount,
                  'transaction_created_date' => now()->format('Y-m-d'),
                  'pay_date' => now()->format('Y-m-d'),
                  'status' => 'Created',
                  'transaction_type' => CbsTransaction::FEES_CHARGES,
                  'product' => $this->invoice->program->programType->name,
                ]);
              }
              break;
            case 'tax_on_discount':
              if ($this->invoice->discount_charge_type == Invoice::FRONT_ENDED) {
                CbsTransaction::create([
                  'bank_id' => $this->invoice?->program?->bank?->id,
                  'payment_request_id' => $this->id,
                  'debit_from_account' => $program_vendor_configuration->payment_account_number,
                  'debit_from_account_name' => $this->invoice->program->name,
                  'debit_from_account_description' => $program_vendor_configuration->payment_account_number . ' (Bank)',
                  'credit_to_account' => $request_account->account,
                  'credit_to_account_name' => $request_account->account_name,
                  'credit_to_account_description' => 'Charges (Bank: ' . $request_account->account . ')',
                  'amount' => $request_account->amount,
                  'transaction_created_date' => now()->format('Y-m-d'),
                  'pay_date' => now()->format('Y-m-d'),
                  'status' => 'Created',
                  'transaction_type' => CbsTransaction::FEES_CHARGES,
                  'product' => $this->invoice->program->programType->name,
                ]);
              } else {
                // Debit Discount Receivable and Credit Fee Income
                $words = explode(' ', $this->invoice->company->name);
                $acronym = '';

                foreach ($words as $w) {
                  $acronym .= mb_substr($w, 0, 1);
                }

                // Debit Discount Receivable and Credit Fee Income
                // Get Bank Configured Receivable Accounts
                if ($this->invoice->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE) {
                  // Factoring without recourse
                  $discount_receivable_bank_account = BankProductsConfiguration::where(
                    'bank_id',
                    $this->invoice->program->bank_id
                  )
                    ->where('section', 'Factoring Without Recourse')
                    ->where('product_code_id', $this->invoice->program->program_code_id)
                    ->where('product_type_id', $this->invoice->program->program_type_id)
                    ->where('name', 'Discount Receivable Account')
                    ->first();

                  $fee_income_bank_account = BankProductsConfiguration::where(
                    'bank_id',
                    $this->invoice->program->bank_id
                  )
                    ->where('section', 'Factoring Without Recourse')
                    ->where('product_code_id', $this->invoice->program->program_code_id)
                    ->where('product_type_id', $this->invoice->program->program_type_id)
                    ->where('name', 'Fee Income Account')
                    ->first();

                  $reference_number =
                    'FWR0' .
                    $this->invoice->program->bank_id .
                    '' .
                    now()->format('y') .
                    '000' .
                    Helpers::generateSequentialReferenceNumber(
                      $this->invoice->program->bank_id,
                      Program::VENDOR_FINANCING,
                      [Program::FACTORING_WITH_RECOURSE, Program::FACTORING_WITHOUT_RECOURSE]
                    );
                } else {
                  // Factoring with recourse
                  $discount_receivable_bank_account = BankProductsConfiguration::where(
                    'bank_id',
                    $this->invoice->program->bank_id
                  )
                    ->where('section', 'Factoring With Recourse')
                    ->where('product_code_id', $this->invoice->program->program_code_id)
                    ->where('product_type_id', $this->invoice->program->program_type_id)
                    ->where('name', 'Discount Receivable Account')
                    ->first();

                  $fee_income_bank_account = BankProductsConfiguration::where(
                    'bank_id',
                    $this->invoice->program->bank_id
                  )
                    ->where('section', 'Factoring With Recourse')
                    ->where('product_code_id', $this->invoice->program->program_code_id)
                    ->where('product_type_id', $this->invoice->program->program_type_id)
                    ->where('name', 'Fee Income Account')
                    ->first();

                  $reference_number =
                    'FWR0' .
                    $this->invoice->program->bank_id .
                    '' .
                    now()->format('y') .
                    '000' .
                    Helpers::generateSequentialReferenceNumber(
                      $this->invoice->program->bank_id,
                      Program::VENDOR_FINANCING,
                      [Program::FACTORING_WITH_RECOURSE, Program::FACTORING_WITHOUT_RECOURSE]
                    );
                }

                $discount_transaction = self::create([
                  'reference_number' => $reference_number,
                  'invoice_id' => $this->invoice_id,
                  'amount' => $request_account->amount,
                  'payment_request_date' => now()->format('Y-m-d'),
                  'status' => 'approved',
                  'approval_status' => 'approved',
                ]);

                CbsTransaction::create([
                  'bank_id' => $this->invoice?->program?->bank?->id,
                  'payment_request_id' => $discount_transaction->id,
                  'debit_from_account' => $discount_receivable_bank_account->value,
                  'debit_from_account_name' => $discount_receivable_bank_account->name,
                  'debit_from_account_description' => $discount_receivable_bank_account->value . ' (Bank)',
                  'credit_to_account' => $fee_income_bank_account->value,
                  'credit_to_account_name' => $fee_income_bank_account->name,
                  'credit_to_account_description' => 'Charges (Bank: ' . $fee_income_bank_account->value . ')',
                  'amount' => $request_account->amount,
                  'transaction_created_date' => now()->format('Y-m-d'),
                  'pay_date' => now()->format('Y-m-d'),
                  'status' => 'Created',
                  'transaction_type' => CbsTransaction::ACCRUAL_POSTED_INTEREST,
                  'product' => $this->invoice->program->programType->name,
                ]);
              }
              break;
            case 'tax_on_fees':
              if ($this->invoice->fee_charge_type == Invoice::FRONT_ENDED) {
                CbsTransaction::create([
                  'bank_id' => $this->invoice?->program?->bank?->id,
                  'payment_request_id' => $this->id,
                  'debit_from_account' => $program_vendor_configuration->payment_account_number,
                  'debit_from_account_name' => $this->invoice->program->name,
                  'debit_from_account_description' => $program_vendor_configuration->payment_account_number . ' (Bank)',
                  'credit_to_account' => $request_account->account,
                  'credit_to_account_name' => $request_account->account_name,
                  'credit_to_account_description' => 'Charges (Bank: ' . $request_account->account . ')',
                  'amount' => $request_account->amount,
                  'transaction_created_date' => now()->format('Y-m-d'),
                  'pay_date' => now()->format('Y-m-d'),
                  'status' => 'Created',
                  'transaction_type' => CbsTransaction::FEES_CHARGES,
                  'product' => $this->invoice->program->programType->name,
                ]);
              } else {
                // Debit Discount Receivable and Credit Fee Income
                $words = explode(' ', $this->invoice->company->name);
                $acronym = '';

                foreach ($words as $w) {
                  $acronym .= mb_substr($w, 0, 1);
                }
                // Debit Discount Receivable and Credit Fee Income Account
                // Get Bank Configured Receivable Accounts
                if ($this->invoice->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE) {
                  // Factoring without recourse
                  $discount_receivable_bank_account = BankProductsConfiguration::where(
                    'bank_id',
                    $this->invoice->program->bank_id
                  )
                    ->where('section', 'Factoring Without Recourse')
                    ->where('product_code_id', $this->invoice->program->program_code_id)
                    ->where('product_type_id', $this->invoice->program->program_type_id)
                    ->where('name', 'Discount Receivable Account')
                    ->first();

                  $fee_income_bank_account = BankProductsConfiguration::where(
                    'bank_id',
                    $this->invoice->program->bank_id
                  )
                    ->where('section', 'Factoring Without Recourse')
                    ->where('product_code_id', $this->invoice->program->program_code_id)
                    ->where('product_type_id', $this->invoice->program->program_type_id)
                    ->where('name', 'Fee Income Account')
                    ->first();

                  $reference_number =
                    'FWR0' .
                    $this->invoice->program->bank_id .
                    '' .
                    now()->format('y') .
                    '000' .
                    Helpers::generateSequentialReferenceNumber(
                      $this->invoice->program->bank_id,
                      Program::VENDOR_FINANCING,
                      [Program::FACTORING_WITH_RECOURSE, Program::FACTORING_WITHOUT_RECOURSE]
                    );
                } else {
                  // Factoring with recourse
                  $discount_receivable_bank_account = BankProductsConfiguration::where(
                    'bank_id',
                    $this->invoice->program->bank_id
                  )
                    ->where('section', 'Factoring With Recourse')
                    ->where('product_code_id', $this->invoice->program->program_code_id)
                    ->where('product_type_id', $this->invoice->program->program_type_id)
                    ->where('name', 'Discount Receivable Account')
                    ->first();

                  $fee_income_bank_account = BankProductsConfiguration::where(
                    'bank_id',
                    $this->invoice->program->bank_id
                  )
                    ->where('section', 'Factoring With Recourse')
                    ->where('product_code_id', $this->invoice->program->program_code_id)
                    ->where('product_type_id', $this->invoice->program->program_type_id)
                    ->where('name', 'Fee Income Account')
                    ->first();

                  $reference_number =
                    'FR0' .
                    $this->invoice->program->bank_id .
                    '' .
                    now()->format('y') .
                    '000' .
                    Helpers::generateSequentialReferenceNumber(
                      $this->invoice->program->bank_id,
                      Program::VENDOR_FINANCING,
                      [Program::FACTORING_WITH_RECOURSE, Program::FACTORING_WITHOUT_RECOURSE]
                    );
                }

                $discount_transaction = self::create([
                  'reference_number' => $reference_number,
                  'invoice_id' => $this->invoice_id,
                  'amount' => $request_account->amount,
                  'payment_request_date' => now()->format('Y-m-d'),
                  'status' => 'approved',
                  'approval_status' => 'approved',
                ]);

                CbsTransaction::create([
                  'bank_id' => $this->invoice?->program?->bank?->id,
                  'payment_request_id' => $discount_transaction->id,
                  'debit_from_account' => $discount_receivable_bank_account->value,
                  'debit_from_account_name' => $discount_receivable_bank_account->name,
                  'debit_from_account_description' => $discount_receivable_bank_account->value . ' (Bank)',
                  'credit_to_account' => $fee_income_bank_account->value,
                  'credit_to_account_name' => $fee_income_bank_account->name,
                  'credit_to_account_description' => 'Charges (Bank: ' . $fee_income_bank_account->value . ')',
                  'amount' => $request_account->amount,
                  'transaction_created_date' => now()->format('Y-m-d'),
                  'pay_date' => now()->format('Y-m-d'),
                  'status' => 'Created',
                  'transaction_type' => CbsTransaction::FEES_CHARGES,
                  'product' => $this->invoice->program->programType->name,
                ]);
              }
              break;
          }
        }
      }
    } else {
      // Dealer Financing
      $program_vendor_configuration = ProgramVendorConfiguration::where('company_id', $this->invoice->company_id)
        ->where('program_id', $this->invoice->program_id)
        ->first();
      foreach ($payment_request_accounts as $request_account) {
        switch ($request_account->type) {
          case 'vendor_account':
            $program_vendor_bank_details = ProgramVendorBankDetail::where(
              'account_number',
              $request_account->account
            )->first();

            CbsTransaction::create([
              'bank_id' => $this->invoice?->program?->bank?->id,
              'payment_request_id' => $this->id,
              'debit_from_account' => $program_vendor_configuration->payment_account_number,
              'debit_from_account_name' => $this->invoice->program->name,
              'debit_from_account_description' => $program_vendor_configuration->payment_account_number . ' (Bank)',
              'credit_to_account' => $request_account->account,
              'credit_to_account_name' => $request_account->account_name,
              'credit_to_account_description' =>
                $this->invoice->company->name .
                ' (' .
                $program_vendor_bank_details?->bank_name .
                ': ' .
                $request_account->account .
                ')',
              'amount' => $request_account->amount,
              'transaction_created_date' => now()->format('Y-m-d'),
              'pay_date' => now()->format('Y-m-d'),
              'status' => 'Created',
              'transaction_type' => CbsTransaction::PAYMENT_DISBURSEMENT,
              'product' => $this->invoice->program->programType->name,
            ]);
            break;
          case 'discount':
            if ($this->invoice->discount_charge_type == Invoice::FRONT_ENDED) {
              CbsTransaction::create([
                'bank_id' => $this->invoice?->program?->bank?->id,
                'payment_request_id' => $this->id,
                'debit_from_account' => $program_vendor_configuration->payment_account_number,
                'debit_from_account_name' => $this->invoice->program->name,
                'debit_from_account_description' => $program_vendor_configuration->payment_account_number . ' (Bank)',
                'credit_to_account' => $request_account->account,
                'credit_to_account_name' => $request_account->account_name,
                'credit_to_account_description' =>
                  CbsTransaction::ACCRUAL_POSTED_INTEREST . ' (Bank: ' . $request_account->account . ')',
                'amount' => $request_account->amount,
                'transaction_created_date' => now()->format('Y-m-d'),
                'pay_date' => now()->format('Y-m-d'),
                'status' => 'Created',
                'transaction_type' => CbsTransaction::ACCRUAL_POSTED_INTEREST,
                'product' => $this->invoice->program->programType->name,
              ]);
            } else {
              // Debit Discount Receivable and Credit Unrealized Discount
              // Rear Ended
              // Credit Discount Receivable to Unrealized Discount Account
              $discount_receivable_bank_account = BankProductsConfiguration::where(
                'bank_id',
                $this->invoice->program->bank_id
              )
                ->where('product_code_id', null)
                ->where('product_type_id', $this->invoice->program->program_type_id)
                ->where('name', 'Discount Receivable from Overdraft')
                ->first();

              // $unrealized_discount_bank_account = BankProductsConfiguration::where(
              //   'bank_id',
              //   $this->invoice->program->bank_id
              // )
              //   ->where('product_code_id', null)
              //   ->where('product_type_id', $this->invoice->program->program_type_id)
              //   ->where('name', 'Unrealised Discount Account')
              //   ->first();

              // $words = explode(' ', $this->invoice->company->name);
              // $acronym = '';

              // foreach ($words as $w) {
              //   $acronym .= mb_substr($w, 0, 1);
              // }

              // $reference_number = 'DF' . $this->invoice->program->bank_id . '' . $acronym . '000' . $this->invoice_id;

              // $discount_transaction = self::create([
              //   'reference_number' => $reference_number,
              //   'invoice_id' => $this->invoice_id,
              //   'amount' => $request_account->amount,
              //   'payment_request_date' => now()->format('Y-m-d'),
              // ]);

              // $discount_transaction->paymentAccounts()->create([
              //   'account' => $unrealized_discount_bank_account->value,
              //   'account_name' => $unrealized_discount_bank_account->name,
              //   'amount' => $request_account->amount,
              //   'type' => 'unrealized discount',
              //   'description' => 'discount to be earned',
              // ]);

              CbsTransaction::create([
                'bank_id' => $this->invoice?->program?->bank?->id,
                'payment_request_id' => $this->id,
                'debit_from_account' => $discount_receivable_bank_account->value,
                'debit_from_account_name' => $discount_receivable_bank_account->name,
                'debit_from_account_description' => $discount_receivable_bank_account->value . ' (Bank)',
                'credit_to_account' => $request_account->account,
                'credit_to_account_name' => $request_account->account_name,
                'credit_to_account_description' =>
                  CbsTransaction::ACCRUAL_POSTED_INTEREST . ' (Bank: ' . $request_account->account . ')',
                'amount' => $request_account->amount,
                'transaction_created_date' => now()->format('Y-m-d'),
                'pay_date' => now()->format('Y-m-d'),
                'status' => 'Created',
                'transaction_type' => CbsTransaction::ACCRUAL_POSTED_INTEREST,
                'product' => $this->invoice->program->programType->name,
              ]);
            }
            break;
          case 'program_fees':
            if ($this->invoice->fee_charge_type == Invoice::FRONT_ENDED) {
              CbsTransaction::create([
                'bank_id' => $this->invoice?->program?->bank?->id,
                'payment_request_id' => $this->id,
                'debit_from_account' => $program_vendor_configuration->payment_account_number,
                'debit_from_account_name' => $this->invoice->program->name,
                'debit_from_account_description' => $program_vendor_configuration->payment_account_number . ' (Bank)',
                'credit_to_account' => $request_account->account,
                'credit_to_account_name' => $request_account->account_name,
                'credit_to_account_description' => 'Charges (Bank: ' . $request_account->account . ')',
                'amount' => $request_account->amount,
                'transaction_created_date' => now()->format('Y-m-d'),
                'pay_date' => now()->format('Y-m-d'),
                'status' => 'Created',
                'transaction_type' => CbsTransaction::FEES_CHARGES,
                'product' => $this->invoice->program->programType->name,
              ]);
            } else {
              $words = explode(' ', $this->invoice->company->name);
              $acronym = '';

              foreach ($words as $w) {
                $acronym .= mb_substr($w, 0, 1);
              }

              // Get Bank Configured Receivable Accounts
              $discount_receivable_bank_account = BankProductsConfiguration::where(
                'bank_id',
                $this->invoice->program->bank_id
              )
                ->where('product_code_id', null)
                ->where('product_type_id', $this->invoice->program->program_type_id)
                ->where('name', 'Discount Receivable from Overdraft')
                ->first();

              $fee_income_bank_account = BankProductsConfiguration::where('bank_id', $this->invoice->program->bank_id)
                ->where('product_code_id', null)
                ->where('product_type_id', $this->invoice->program->program_type_id)
                ->where('name', 'Fee Income Account')
                ->first();

              $reference_number =
                'DF0' .
                $this->invoice->program->bank_id .
                '' .
                now()->format('y') .
                '000' .
                Helpers::generateSequentialReferenceNumber($this->invoice->program->bank_id, Program::DEALER_FINANCING);

              $discount_transaction = self::create([
                'reference_number' => $reference_number,
                'invoice_id' => $this->invoice_id,
                'amount' => $request_account->amount,
                'payment_request_date' => now()->format('Y-m-d'),
                'status' => 'approved',
                'approval_status' => 'approved',
              ]);

              CbsTransaction::create([
                'bank_id' => $this->invoice?->program?->bank?->id,
                'payment_request_id' => $discount_transaction->id,
                'debit_from_account' => $discount_receivable_bank_account->value,
                'debit_from_account_name' => $discount_receivable_bank_account->name,
                'debit_from_account_description' => $discount_receivable_bank_account->value . ' (Bank)',
                'credit_to_account' => $fee_income_bank_account->value,
                'credit_to_account_name' => $fee_income_bank_account->name,
                'credit_to_account_description' => 'Charges (Bank: ' . $fee_income_bank_account->value . ')',
                'amount' => $request_account->amount,
                'transaction_created_date' => now()->format('Y-m-d'),
                'pay_date' => now()->format('Y-m-d'),
                'status' => 'Created',
                'transaction_type' => CbsTransaction::FEES_CHARGES,
                'product' => $this->invoice->program->programType->name,
              ]);
            }
            break;
          case 'program_fees_taxes':
            if ($this->invoice->fee_charge_type == Invoice::FRONT_ENDED) {
              CbsTransaction::create([
                'bank_id' => $this->invoice?->program?->bank?->id,
                'payment_request_id' => $this->id,
                'debit_from_account' => $program_vendor_configuration->payment_account_number,
                'debit_from_account_name' => $this->invoice->program->name,
                'debit_from_account_description' => $program_vendor_configuration->payment_account_number . ' (Bank)',
                'credit_to_account' => $request_account->account,
                'credit_to_account_name' => $request_account->account_name,
                'credit_to_account_description' => 'Charges (Bank: ' . $request_account->account . ')',
                'amount' => $request_account->amount,
                'transaction_created_date' => now()->format('Y-m-d'),
                'pay_date' => now()->format('Y-m-d'),
                'status' => 'Created',
                'transaction_type' => CbsTransaction::FEES_CHARGES,
                'product' => $this->invoice->program->programType->name,
              ]);
            } else {
              $words = explode(' ', $this->invoice->company->name);
              $acronym = '';

              foreach ($words as $w) {
                $acronym .= mb_substr($w, 0, 1);
              }

              // Get Bank Configured Receivable Accounts
              $discount_receivable_bank_account = BankProductsConfiguration::where(
                'bank_id',
                $this->invoice->program->bank_id
              )
                ->where('product_code_id', null)
                ->where('product_type_id', $this->invoice->program->program_type_id)
                ->where('name', 'Discount Receivable from Overdraft')
                ->first();

              $fee_income_bank_account = BankProductsConfiguration::where('bank_id', $this->invoice->program->bank_id)
                ->where('product_code_id', null)
                ->where('product_type_id', $this->invoice->program->program_type_id)
                ->where('name', 'Fee Income Account')
                ->first();

              $reference_number =
                'DF0' .
                $this->invoice->program->bank_id .
                '' .
                now()->format('y') .
                '000' .
                Helpers::generateSequentialReferenceNumber($this->invoice->program->bank_id, Program::DEALER_FINANCING);

              $discount_transaction = self::create([
                'reference_number' => $reference_number,
                'invoice_id' => $this->invoice_id,
                'amount' => $request_account->amount,
                'payment_request_date' => now()->format('Y-m-d'),
                'status' => 'approved',
                'approval_status' => 'approved',
              ]);

              CbsTransaction::create([
                'bank_id' => $this->invoice?->program?->bank?->id,
                'payment_request_id' => $discount_transaction->id,
                'debit_from_account' => $discount_receivable_bank_account->value,
                'debit_from_account_name' => $discount_receivable_bank_account->name,
                'debit_from_account_description' => $discount_receivable_bank_account->value . ' (Bank)',
                'credit_to_account' => $fee_income_bank_account->value,
                'credit_to_account_name' => $fee_income_bank_account->name,
                'credit_to_account_description' => 'Charges: (Bank: ' . $fee_income_bank_account->value . ')',
                'amount' => $request_account->amount,
                'transaction_created_date' => now()->format('Y-m-d'),
                'pay_date' => now()->format('Y-m-d'),
                'status' => 'Created',
                'transaction_type' => CbsTransaction::FEES_CHARGES,
                'product' => $this->invoice->program->programType->name,
              ]);
            }
            break;
          case 'tax_on_discount':
            if ($this->invoice->discount_charge_type == Invoice::FRONT_ENDED) {
              CbsTransaction::create([
                'bank_id' => $this->invoice?->program?->bank?->id,
                'payment_request_id' => $this->id,
                'debit_from_account' => $program_vendor_configuration->payment_account_number,
                'debit_from_account_name' => $this->invoice->program->name,
                'debit_from_account_description' => $program_vendor_configuration->payment_account_number . ' (Bank)',
                'credit_to_account' => $request_account->account,
                'credit_to_account_name' => $request_account->account_name,
                'credit_to_account_description' => 'Charges (Bank: ' . $request_account->account . ')',
                'amount' => $request_account->amount,
                'transaction_created_date' => now()->format('Y-m-d'),
                'pay_date' => now()->format('Y-m-d'),
                'status' => 'Created',
                'transaction_type' => CbsTransaction::FEES_CHARGES,
                'product' => $this->invoice->program->programType->name,
              ]);
            } else {
              $words = explode(' ', $this->invoice->company->name);
              $acronym = '';

              foreach ($words as $w) {
                $acronym .= mb_substr($w, 0, 1);
              }

              // Get Bank Configured Receivable Accounts
              $discount_receivable_bank_account = BankProductsConfiguration::where(
                'bank_id',
                $this->invoice->program->bank_id
              )
                ->where('product_code_id', null)
                ->where('product_type_id', $this->invoice->program->program_type_id)
                ->where('name', 'Discount Receivable from Overdraft')
                ->first();

              // $fee_income_bank_account = BankProductsConfiguration::where('bank_id', $this->invoice->program->bank_id)
              //   ->where('product_code_id', null)
              //   ->where('product_type_id', $this->invoice->program->program_type_id)
              //   ->where('name', 'Fee Income Account')
              //   ->first();

              // $reference_number = 'DF' . $this->invoice->program->bank_id . '' . $acronym . '000' . $this->invoice_id;

              // $discount_transaction = self::create([
              //   'reference_number' => $reference_number,
              //   'invoice_id' => $this->invoice_id,
              //   'amount' => $request_account->amount,
              //   'payment_request_date' => now()->format('Y-m-d'),
              // ]);

              CbsTransaction::create([
                'bank_id' => $this->invoice?->program?->bank?->id,
                'payment_request_id' => $this->id,
                'debit_from_account' => $discount_receivable_bank_account->value,
                'debit_from_account_name' => $discount_receivable_bank_account->name,
                'debit_from_account_description' => $discount_receivable_bank_account->value . ' (Bank)',
                'credit_to_account' => $request_account->account,
                'credit_to_account_name' => $request_account->account_name,
                'credit_to_account_description' => 'Charges (Bank: ' . $request_account->account . ')',
                'amount' => $request_account->amount,
                'transaction_created_date' => now()->format('Y-m-d'),
                'pay_date' => now()->format('Y-m-d'),
                'status' => 'Created',
                'transaction_type' => CbsTransaction::FEES_CHARGES,
                'product' => $this->invoice->program->programType->name,
              ]);
            }
            break;
          case 'tax_on_fees':
            if ($this->invoice->fee_charge_type == Invoice::FRONT_ENDED) {
              CbsTransaction::create([
                'bank_id' => $this->invoice?->program?->bank?->id,
                'payment_request_id' => $this->id,
                'debit_from_account' => $program_vendor_configuration->payment_account_number,
                'debit_from_account_name' => $this->invoice->program->name,
                'debit_from_account_description' => $program_vendor_configuration->payment_account_number . ' (Bank)',
                'credit_to_account' => $request_account->account,
                'credit_to_account_name' => $request_account->account_name,
                'credit_to_account_description' => 'Charges (Bank: ' . $request_account->account . ')',
                'amount' => $request_account->amount,
                'transaction_created_date' => now()->format('Y-m-d'),
                'pay_date' => now()->format('Y-m-d'),
                'status' => 'Created',
                'transaction_type' => CbsTransaction::FEES_CHARGES,
                'product' => $this->invoice->program->programType->name,
              ]);
            } else {
              $words = explode(' ', $this->invoice->company->name);
              $acronym = '';

              foreach ($words as $w) {
                $acronym .= mb_substr($w, 0, 1);
              }

              // Get Bank Configured Receivable Accounts
              $discount_receivable_bank_account = BankProductsConfiguration::where(
                'bank_id',
                $this->invoice->program->bank_id
              )
                ->where('product_code_id', null)
                ->where('product_type_id', $this->invoice->program->program_type_id)
                ->where('name', 'Discount Receivable from Overdraft')
                ->first();

              // $fee_income_bank_account = BankProductsConfiguration::where('bank_id', $this->invoice->program->bank_id)
              //   ->where('product_code_id', null)
              //   ->where('product_type_id', $this->invoice->program->program_type_id)
              //   ->where('name', 'Fee Income Account')
              //   ->first();

              // $reference_number = 'DF' . $this->invoice->program->bank_id . '' . $acronym . '000' . $this->invoice_id;

              // $discount_transaction = self::create([
              //   'reference_number' => $reference_number,
              //   'invoice_id' => $this->invoice_id,
              //   'amount' => $request_account->amount,
              //   'payment_request_date' => now()->format('Y-m-d'),
              // ]);

              CbsTransaction::create([
                'bank_id' => $this->invoice?->program?->bank?->id,
                'payment_request_id' => $this->id,
                'debit_from_account' => $discount_receivable_bank_account->value,
                'debit_from_account_name' => $discount_receivable_bank_account->name,
                'debit_from_account_description' => $discount_receivable_bank_account->value . ' (Bank)',
                'credit_to_account' => $request_account->account,
                'credit_to_account_name' => $request_account->account_name,
                'credit_to_account_description' => 'Charges (Bank: ' . $request_account->account . ')',
                'amount' => $request_account->amount,
                'transaction_created_date' => now()->format('Y-m-d'),
                'pay_date' => now()->format('Y-m-d'),
                'status' => 'Created',
                'transaction_type' => CbsTransaction::FEES_CHARGES,
                'product' => $this->invoice->program->programType->name,
              ]);
            }
            break;
        }
      }
    }

    $this->invoice->update([
      'financing_status' => 'financed',
    ]);

    // Updated Limits
    if ($this->invoice->program->programType->name == Program::VENDOR_FINANCING) {
      $program_vendor_configuration->decrement(
        'pipeline_amount',
        ($this->invoice->eligibility / 100) * $this->invoice->invoice_total_amount
      );
      $program_vendor_configuration->increment(
        'utilized_amount',
        ($this->invoice->eligibility / 100) * $this->invoice->invoice_total_amount
      );

      // Update Program and Company Pipeline and Utilized Amounts
      $this->invoice->company->decrement(
        'pipeline_amount',
        ($this->invoice->eligibility / 100) * $this->invoice->invoice_total_amount
      );
      $this->invoice->company->increment(
        'utilized_amount',
        ($this->invoice->eligibility / 100) * $this->invoice->invoice_total_amount
      );

      $this->invoice->program->decrement(
        'pipeline_amount',
        ($this->invoice->eligibility / 100) * $this->invoice->invoice_total_amount
      );
      $this->invoice->program->increment(
        'utilized_amount',
        ($this->invoice->eligibility / 100) * $this->invoice->invoice_total_amount
      );
    } else {
      // Dealer Financing
      $program_vendor_configuration->decrement('pipeline_amount', $this->invoice->drawdown_amount);
      $program_vendor_configuration->increment('utilized_amount', $this->invoice->drawdown_amount);

      // Update Program and Company Pipeline and Utilized Amounts
      $this->invoice->company->decrement('pipeline_amount', $this->invoice->drawdown_amount);
      $this->invoice->company->increment('utilized_amount', $this->invoice->drawdown_amount);

      $this->invoice->program->decrement('pipeline_amount', $this->invoice->drawdown_amount);
      $this->invoice->program->increment('utilized_amount', $this->invoice->drawdown_amount);
    }
  }
}
