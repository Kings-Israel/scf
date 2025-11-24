<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class ProgramVendorConfiguration extends Model
{
  use HasFactory;

  /**
   * The attributes that should be cast to native types.
   *
   * @var array
   */
  protected $casts = [
    'request_auto_finance' => 'bool',
    'auto_approve_finance' => 'bool',
    'is_approved' => 'bool',
  ];

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
  protected $appends = ['can_approve', 'utilized_percentage_ratio', 'can_delete'];

  /**
   * Get the program that owns the ProgramVendorBankDetail
   */
  public function program(): BelongsTo
  {
    return $this->belongsTo(Program::class);
  }

  /**
   * Get the company that owns the ProgramVendorBankDetail
   */
  public function company(): BelongsTo
  {
    return $this->belongsTo(Company::class);
  }

  /**
   * Get the buyer that owns the Invoice
   */
  public function buyer(): BelongsTo
  {
    return $this->belongsTo(Company::class, 'buyer_id', 'id');
  }

  /**
   * Get the user that owns the ProgramVendorConfiguration
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by', 'id');
  }

  public function anchorConfigurationChange(): MorphOne
  {
    return $this->morphOne(AnchorConfigurationChange::class, 'configurable');
  }

  /**
   * Get all of the invoices for the ProgramVendorConfiguration
   */
  public function invoices(): HasManyThrough
  {
    return $this->hasManyThrough(Invoice::class, Program::class, 'id', 'program_id', 'program_id', 'id');
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
   * Get the can approve
   *
   * @return string
   */
  public function getCanApproveAttribute()
  {
    if (auth()->check()) {
      if ($this->created_by == auth()->id()) {
        return false;
      }

      return true;
    }

    return false;
  }

  public function paidAmount(): float
  {
    $paid_amount = Invoice::where('program_id', $this->program_id)
      ->where('company_id', $this->company_id)
      ->whereIn('financing_status', ['disbursed', 'closed'])
      ->whereHas('payments')
      ->sum('calculated_paid_amount');

    return round($paid_amount, 2);
  }

  public function getUtilizedAttribute(): float
  {
    $amount = 0;
    if ($this->program_id && ($this->company_id || $this->buyer_id)) {
      if ($this->program->programType->name == Program::DEALER_FINANCING) {
        $invoices = Invoice::where('program_id', $this->program_id)
          ->where('company_id', $this->company_id)
          ->whereIn('financing_status', ['financed', 'disbursed', 'pending', 'submitted'])
          ->whereHas('paymentRequests', function ($query) {
            $query->whereIn('status', ['approved', 'paid']);
          })
          ->get();
        foreach ($invoices as $key => $invoice) {
          $amount += $invoice->drawdown_amount - $invoice->paid_amount;
          // if ($invoice->disbursed_amount < $invoice->drawdown_amount) {
          // } else {
          //   $amount += $invoice->disbursed_amount - $invoice->paid_amount;
          // }
        }
      } else {
        if ($this->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
          $invoices = Invoice::where('program_id', $this->program_id)
            ->where('company_id', $this->company_id)
            ->whereIn('financing_status', ['financed', 'disbursed', 'pending', 'submitted'])
            ->whereHas('paymentRequests', function ($query) {
              $query->whereIn('status', ['approved', 'paid']);
            })
            ->get();
        } else {
          $invoices = Invoice::where('program_id', $this->program_id)
            ->where('buyer_id', $this->buyer_id)
            ->whereIn('financing_status', ['financed', 'disbursed', 'pending', 'submitted'])
            ->whereHas('paymentRequests', function ($query) {
              $query->whereIn('status', ['approved', 'paid']);
            })
            ->get();
        }
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

  public function getPipelineAttribute(): float
  {
    $amount = 0;

    if ($this->program_id && ($this->company_id || $this->buyer_id)) {
      if ($this->program->programType->name == Program::DEALER_FINANCING) {
        $invoices = Invoice::where('program_id', $this->program_id)
          // ->whereDate('due_date', '>=', now()->format('Y-m-d'))
          ->whereIn('financing_status', ['pending', 'submitted'])
          ->where('company_id', $this->company_id)
          ->whereHas('paymentRequests', function ($query) {
            $query->whereIn('status', ['created']);
          })
          ->get();
      } else {
        if ($this->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
          $invoices = Invoice::where('program_id', $this->program_id)
            // ->whereDate('due_date', '>=', now()->format('Y-m-d'))
            ->where('company_id', $this->company_id)
            ->whereIn('financing_status', ['pending', 'submitted'])
            ->whereHas('paymentRequests', function ($query) {
              $query->whereIn('status', ['created']);
            })
            ->get();
        } else {
          $invoices = Invoice::where('program_id', $this->program_id)
            // ->whereDate('due_date', '>=', now()->format('Y-m-d'))
            ->where('buyer_id', $this->buyer_id)
            ->whereIn('financing_status', ['pending', 'submitted'])
            ->whereHas('paymentRequests', function ($query) {
              $query->whereIn('status', ['created']);
            })
            ->get();
        }
      }

      foreach ($invoices as $invoice) {
        $amount += $invoice->eligibility
          ? ($invoice->eligibility / 100) * $invoice->invoice_total_amount
          : $invoice->invoice_total_amount;
      }
    }

    return round($amount, 2);
  }

  public function getOverdueAmountAttribute(): float
  {
    return round(
      Invoice::where('program_id', $this->program_id)
        ->where('company_id', $this->company_id)
        ->where('financing_status', 'financed')
        ->whereDate('due_date', '<', now()->format('Y-m-d'))
        ->sum('calculated_total_amount'),
      2
    );
  }

  public function getUtilizedPercentageRatioAttribute(): float
  {
    if ($this->sanctioned_limit > 0) {
      return round((($this->utilized_amount + $this->pipeline_amount) / $this->sanctioned_limit) * 100, 2);
    }

    return 0;
  }

  public function getDaysPastDueAttribute()
  {
    $days = 0;

    $invoices = Invoice::where(['program_id' => $this->program_id, 'company_id' => $this->company_id])
      ->whereDate('due_date', '<', now()->format('Y-m-d'))
      ->where('financing_status', 'financed')
      ->get();

    foreach ($invoices as $invoice) {
      $days += $invoice->days_past_due;
    }

    return $days;
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
        if (round($this->pipeline_amount) > 0 || round($this->utilized_amount) > 0) {
          return false;
        }

        return true;
      }

      return false;
    }

    return false;
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
    if ($this->program->programType->name == 'Dealer Financing') {
      $invoices = Invoice::where('program_id', $this->program_id)
        ->where('company_id', $this->company_id)
        ->where('financing_status', 'financed')
        ->where('due_date', '>', now())
        ->get();
    } else {
      if ($this->program->programCode->name == 'Vendor Financing Receivable') {
        $invoices = Invoice::where('program_id', $this->program_id)
          ->where('company_id', $this->company_id)
          ->where('financing_status', 'financed')
          ->where('due_date', '>', now())
          ->get();
      } else {
        $invoices = Invoice::where('program_id', $this->program_id)
          ->where('buyer_id', $this->buyer_id)
          ->where('company_id', $this->company_id)
          ->where('financing_status', 'financed')
          ->where('due_date', '>', now())
          ->get();
      }
    }

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
    if ($this->program->programType->name == 'Dealer Financing') {
      $amount = CbsTransaction::whereHas('paymentRequest', function ($query) {
        $query->whereHas('invoice', function ($query) {
          $query
            ->where('program_id', $this->program_id)
            ->where('company_id', $this->company_id)
            ->where('financing_status', 'financed')
            ->where('due_date', '>', now());
        });
      })
        ->where('status', 'Created')
        ->whereIn('transaction_type', ['Repayment', 'Bank Invoice Payment'])
        ->sum('amount');
    } else {
      if ($this->program->programCode->name == 'Vendor Financing Receivable') {
        $amount = CbsTransaction::whereHas('paymentRequest', function ($query) {
          $query->whereHas('invoice', function ($query) {
            $query
              ->where('program_id', $this->program_id)
              ->where('company_id', $this->company_id)
              ->where('financing_status', 'financed')
              ->where('due_date', '>', now());
          });
        })
          ->where('status', 'Created')
          ->whereIn('transaction_type', ['Repayment', 'Bank Invoice Payment'])
          ->sum('amount');
      } else {
        $amount = CbsTransaction::whereHas('paymentRequest', function ($query) {
          $query->whereHas('invoice', function ($query) {
            $query
              ->where('program_id', $this->program_id)
              ->where('company_id', $this->company_id)
              ->where('buyer_id', $this->buyer_id)
              ->where('financing_status', 'financed')
              ->where('due_date', '>', now());
          });
        })
          ->where('status', 'Created')
          ->whereIn('transaction_type', ['Repayment', 'Bank Invoice Payment'])
          ->sum('amount');
      }
    }

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
    if ($this->program->programType->name == 'Dealer Financing') {
      $amount = CbsTransaction::whereHas('paymentRequest', function ($query) {
        $query->whereHas('invoice', function ($query) {
          $query
            ->where('program_id', $this->program_id)
            ->where('company_id', $this->company_id)
            ->where('financing_status', 'financed')
            ->where('due_date', '>', now());
        });
      })
        ->where('status', 'Successful')
        ->whereIn('transaction_type', ['Repayment', 'Bank Invoice Payment'])
        ->sum('amount');
    } else {
      if ($this->program->programCode->name == 'Vendor Financing Receivable') {
        $amount = CbsTransaction::whereHas('paymentRequest', function ($query) {
          $query->whereHas('invoice', function ($query) {
            $query
              ->where('program_id', $this->program_id)
              ->where('company_id', $this->company_id)
              ->where('financing_status', 'financed')
              ->where('due_date', '>', now());
          });
        })
          ->where('status', 'Successful')
          ->whereIn('transaction_type', ['Repayment', 'Bank Invoice Payment'])
          ->sum('amount');
      } else {
        $amount = CbsTransaction::whereHas('paymentRequest', function ($query) {
          $query->whereHas('invoice', function ($query) {
            $query
              ->where('program_id', $this->program_id)
              ->where('company_id', $this->company_id)
              ->where('buyer_id', $this->buyer_id)
              ->where('financing_status', 'financed')
              ->where('due_date', '>', now());
          });
        })
          ->where('status', 'Successful')
          ->whereIn('transaction_type', ['Repayment', 'Bank Invoice Payment'])
          ->sum('amount');
      }
    }

    return round($amount, 2);
  }

  public function getTotalRequestedAmountAttribute()
  {
    return round(
      Invoice::where('program_id', $this->program_id)
        ->where('company_id', $this->company_id)
        ->whereIn('financing_status', ['disbursed', 'closed'])
        ->sum('calculated_total_amount'),
      2
    );
  }
}
