<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class CbsTransaction extends Model
{
  use HasFactory;

  const PLATFORM_CHARGES = 'Platform Charges';
  const ACCRUAL_POSTED_INTEREST = 'Accrual/Posted Interest';
  // const ACCRUAL_POSTED_INTEREST = 'Discount Charge';
  const OD_DRAWDOWN = 'OD Drawdown';
  const FEES_CHARGES = 'Fees/Charges';
  const REPAYMENT = 'Repayment';
  const PAYMENT_DISBURSEMENT = 'Payment Disbursement';
  const BALANCE_DF_INVOICE_PAYMENT = 'Balance DF Invoice Payment';
  const BANK_INVOICE_PAYMENT = 'Bank Invoice Payment';
  const OD_ACCOUNT_DEBIT = 'OD Account Debit';
  const OVERDUE_ACCOUNT = 'Overdue Account';
  const FUNDS_TRANSFER = 'Funds Transfer';
  const ADVANCE_DISCOUNT_SETTLEMENT = 'Advance Discount Settlement';
  const UNREALIZED_DISCOUNT_SETTLEMENT = 'Unrealized Discount Settlement';

  /**
   * The attributes that aren't mass assignable.
   *
   * @var array
   */
  protected $guarded = [];

  /**
   * The accessors to append to the model's array form.
   *
   * @var array
   */
  protected $appends = ['currency'];

  /**
   * The "booting" method of the model.
   *
   * @return void
   */
  protected static function boot()
  {
    parent::boot();

    static::creating(function ($model) {
        DB::transaction(function () use ($model) {
            $latestValue = self::where('bank_id', $model->bank_id)
                                ->lockForUpdate() // Essential for race condition prevention
                                ->max('cbs_id');

            $model->cbs_id = ($latestValue ?? 0) + 1;
        });
    });
  }

  /**
   * Get the bank that owns the CbsTransaction
   */
  public function bank(): BelongsTo
  {
    return $this->belongsTo(Bank::class);
  }

  /**
   * Get the paymentRequest that owns the CbsTransaction
   */
  public function paymentRequest(): BelongsTo
  {
    return $this->belongsTo(PaymentRequest::class);
  }

  /**
   * Get the creditAccountRequest that owns the CbsTransaction
   */
  public function creditAccountRequest(): BelongsTo
  {
    return $this->belongsTo(CreditAccountRequest::class);
  }

  /**
   * Scope a query to only include vendorFinancing
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeVendorFinancing($query)
  {
    return $query->whereHas('paymentRequest', function ($query) {
      $query->whereHas('invoice', function ($query) {
        $query->whereHas('program', function ($query) {
          $query
            ->whereHas('programType', fn($query) => $query->where('name', Program::VENDOR_FINANCING))
            ->whereHas('programCode', fn($query) => $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE));
        });
      });
    });
  }

  /**
   * Scope a query to only include Factoring
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeFactoring($query)
  {
    return $query->whereHas('paymentRequest', function ($query) {
      $query->whereHas('invoice', function ($query) {
        $query->whereHas('program', function ($query) {
          $query->whereHas(
            'programCode',
            fn($query) => $query
              ->where('name', Program::FACTORING_WITH_RECOURSE)
              ->orWhere('name', Program::FACTORING_WITHOUT_RECOURSE)
          );
        });
      });
    });
  }

  /**
   * Scope a query to only include vendorFinancing
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeDealerFinancing($query)
  {
    return $query->whereHas('paymentRequest', function ($query) {
      $query->whereHas('invoice', function ($query) {
        $query->whereHas('program', function ($query) {
          $query->whereHas('programType', fn($query) => $query->where('name', Program::DEALER_FINANCING));
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
   * Get the currency
   *
   * @param  string  $value
   * @return string
   */
  public function getCurrencyAttribute()
  {
    $currency = 'KES';

    if ($this->paymentRequest()->exists() && $this->paymentRequest->invoice->program->bank->adminConfiguration) {
      if ($this->paymentRequest->invoice->program->bank->adminConfiguration->defaultCurrency) {
        $currency = Currency::find($this->paymentRequest->invoice->program->bank->adminConfiguration->defaultCurrency)
          ->code;
      } else {
        $currency = Currency::where('code', $this->paymentRequest->invoice->currency)->first()?->code;
        if (!$currency) {
          $currency = 'KES';
        }
      }
    }

    if ($this->creditAccountRequest()->exists() && $this->creditAccountRequest->program->bank->adminConfiguration) {
      if ($this->creditAccountRequest->program->bank->adminConfiguration->defaultCurrency) {
        $currency = Currency::find($this->creditAccountRequest->program->bank->adminConfiguration->defaultCurrency)
          ->code;
      }
    }

    return $currency;
  }
}
