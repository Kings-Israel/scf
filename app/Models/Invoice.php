<?php

namespace App\Models;

use App\Helpers\Helpers;
use Carbon\Carbon;
use App\Jobs\SendMail;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\DB;

class Invoice extends Model implements HasMedia
{
  use HasFactory, InteractsWithMedia;

  const FRONT_ENDED = 'Front Ended';
  const REAR_ENDED = 'Rear Ended';

  const WITH_RECOURSE = 'With Recourse';
  const WITHOUT_RECOURSE = 'Without Recourse';

  const ANCHOR_DISCOUNT_BEARING = 'Anchor Discount Bearing';
  const VENDOR_DISCOUNT_BEARING = 'Vendor Discount Bearing';
  const BUYER_DISCOUNT_BEARING = 'Buyer Discount Bearing';
  const DEALER_DISCOUNT_BEARING = 'Dealer Discount Bearing';

  const ANCHOR_FEE_BEARING = 'Anchor Fee Bearing';
  const VENDOR_FEE_BEARING = 'Vendor Fee Bearing';
  const BUYER_FEE_BEARING = 'Buyer Fee Bearing';
  const DEALER_FEE_BEARING = 'Dealer Fee Bearing';

  /**
   * The attributes that aren't mass assignable.
   *
   * @var array
   */
  protected $guarded = [];

  protected $appends = [
    'paid_amount',
    'balance',
    'invoice_total_amount',
    'total',
    'total_invoice_discount',
    'total_invoice_taxes',
    'total_invoice_fees',
    'eligible_for_finance',
    'actual_remittance_amount',
    'days_past_due',
    'overdue_amount',
    'can_edit',
    'can_delete',
    'can_delete_attachment',
    'approval_stage',
    'user_can_approve',
    'user_has_approved',
    'can_edit',
    'can_edit_fees',
    'discount',
  ];

  /**
   * The attributes that should be cast to native types.
   *
   * @var array
   */
  protected $casts = [
    'eligible_for_financing' => 'bool',
  ];

  public function getAttachmentAttribute($value)
  {
    if ($value) {
      if ($this->program->programType->name == Program::VENDOR_FINANCING) {
        if ($this->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
          return config('app.url') . '/storage/invoices/' . $value;
        } else {
          return config('app.url') . '/storage/invoices/' . $value;
        }
      } else {
        return config('app.url') . '/storage/invoices/' . $value;
      }
    }

    return null;
  }

  /**
   * Get the status
   *
   * @param  string  $value
   * @return string
   */
  public function getStatusAttribute($value)
  {
    if ($value == 'denied') {
      return 'Rejected';
    }

    return $value;
  }

  /**
   * Get the program that owns the Invoice
   */
  public function program(): BelongsTo
  {
    return $this->belongsTo(Program::class);
  }

  /**
   * Get the company that owns the Invoice
   */
  public function company(): BelongsTo
  {
    return $this->belongsTo(Company::class);
  }

  /**
   * Get all of the invoiceItems for the Invoice
   */
  public function invoiceItems(): HasMany
  {
    return $this->hasMany(InvoiceItem::class);
  }

  /**
   * Get all of the invoiceFees for the Invoice
   */
  public function invoiceFees(): HasMany
  {
    return $this->hasMany(InvoiceFee::class);
  }

  /**
   * Get all of the invoiceTaxes for the Invoice
   */
  public function invoiceTaxes(): HasMany
  {
    return $this->hasMany(InvoiceTax::class);
  }

  /**
   * Get all of the invoiceDiscounts for the Invoice
   */
  public function invoiceDiscounts(): HasMany
  {
    return $this->hasMany(InvoiceDiscount::class);
  }

  /**
   * Get the buyer that owns the Invoice
   */
  public function buyer(): BelongsTo
  {
    return $this->belongsTo(Company::class, 'buyer_id', 'id');
  }

  /**
   * Get all of the paymentRequests for the Invoice
   */
  public function paymentRequests(): HasMany
  {
    return $this->hasMany(PaymentRequest::class);
  }

  /**
   * Get all of the payments for the Invoice
   */
  public function payments(): HasMany
  {
    return $this->hasMany(Payment::class);
  }

  /**
   * Get the purchaseOrder that owns the Invoice
   */
  public function purchaseOrder(): BelongsTo
  {
    return $this->belongsTo(PurchaseOrder::class);
  }

  /**
   * Get all of the approvals for the PurchaseOrder
   */
  public function approvals(): HasMany
  {
    return $this->hasMany(InvoiceApproval::class);
  }

  /**
   * Get the user that owns the Invoice
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by', 'id');
  }

  /**
   * Get all of the cbsTransactions for the Invoice
   *
   * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
   */
  public function cbsTransactions(): HasManyThrough
  {
    return $this->hasManyThrough(CbsTransaction::class, PaymentRequest::class);
  }

  public function invoiceMedia()
  {
    return $this->media();
  }

  /**
   * Scope a query to only include vendorFinancing
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeVendorFinancing($query)
  {
    return $query->whereHas('program', function ($query) {
      $query
        ->whereHas('programType', fn($query) => $query->where('name', Program::VENDOR_FINANCING))
        ->whereHas('programCode', fn($query) => $query->where('name', Program::VENDOR_FINANCING_RECEIVABLE));
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
    return $query->whereHas('program', function ($query) {
      $query->whereHas(
        'programCode',
        fn($query) => $query->whereIn('name', [Program::FACTORING_WITH_RECOURSE, Program::FACTORING_WITHOUT_RECOURSE])
      );
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
    return $query->whereHas('program', function ($query) {
      $query->whereHas('programType', fn($query) => $query->where('name', Program::DEALER_FINANCING));
    });
  }

  /**
   * Scope a query to only include factoringDealer
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeFactoringDealer($query)
  {
    return $query->whereHas('program', function ($query) {
      $query
        ->whereHas('programType', fn($query) => $query->where('name', Program::DEALER_FINANCING))
        ->orWhereHas(
          'programCode',
          fn($query) => $query->whereIn('name', [Program::FACTORING_WITH_RECOURSE, Program::FACTORING_WITHOUT_RECOURSE])
        );
    });
  }

  public function getTotalAttribute(): float
  {
    $amount = 0;

    if ($this->total_amount > 0) {
      $amount = $this->total_amount;
    } else {
      foreach ($this->invoiceItems as $invoice_item) {
        $amount += $invoice_item->quantity * $invoice_item->price_per_quantity;
      }
    }

    return round($amount, 2);
  }

  public function getTotalInvoiceDiscountAttribute(): float
  {
    $total_invoice_discount = 0;

    foreach ($this->invoiceDiscounts as $invoice_discount) {
      if (!$invoice_discount->invoiceItem) {
        if ($invoice_discount->type == 'percentage') {
          $total_invoice_discount += ($invoice_discount->value / 100) * $this->total;
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

    return round($total_invoice_discount, 2);
  }

  public function getTotalInvoiceTaxesAttribute(): float
  {
    $taxes = 0;
    foreach ($this->invoiceTaxes as $tax) {
      $taxes += $tax->value;
    }

    return round($taxes, 2);
  }

  public function getTotalInvoiceFeesAttribute(): float
  {
    $deductions = 0;

    foreach ($this->invoiceFees as $fee) {
      $deductions += $fee->amount;
    }

    return round($deductions, 2);
  }

  public function getInvoiceTotalAmountAttribute(): float
  {
    if ($this->invoiceItems->count() > 0) {
      return round(
        $this->total + $this->total_invoice_taxes - $this->total_invoice_fees - $this->total_invoice_discount,
        2
      );
    }

    return round(
      $this->total + $this->total_invoice_taxes - $this->total_invoice_fees - $this->total_invoice_discount,
      2
    );
  }

  /**
   * Get the tax percentage
   *
   * @param  double  $tax_amount
   * @return double
   */
  public function getTaxPercentage($tax_amount)
  {
    // return $value;
    return round(($tax_amount / $this->total + $this->total_invoice_taxes - $this->total_invoice_discount) * 100, 2);
  }

  public function getPaidAmountAttribute(): float
  {
    $amount = 0;

    // foreach ($this->payments as $payment) {
    //   $amount += $payment->amount;
    // }
    $amount = Payment::where('invoice_id', $this->id)->sum('amount');

    return round($amount, 2);
  }

  /**
   * Get the discount type
   *
   * @param  string  $value
   * @return string
   */
  public function getDiscountTypeAttribute()
  {
    if (
      $this->financing_status == 'financed' ||
      $this->financing_status == 'disbured' ||
      $this->financing_status == 'closed'
    ) {
      if ($this->program->programType->name == Program::DEALER_FINANCING) {
        if ($this->disbursed_amount < $this->drawdown_amount - $this->charged_fees) {
          // Front ended
          return self::FRONT_ENDED;
        } else {
          // Rear ended
          return self::REAR_ENDED;
        }
      } else {
        if ($this->disbursed_amount < $this->invoice_total_amount - $this->charged_fees) {
          // Front ended
          return self::FRONT_ENDED;
        } else {
          // Rear ended
          return self::REAR_ENDED;
        }
      }
    }

    $program_discount_details = ProgramDiscount::where('program_id', $this->program_id)
      ->select('discount_type')
      ->first();

    return $program_discount_details ? $program_discount_details->discount_type : self::FRONT_ENDED;
  }

  /**
   * Get the vendor bearing discount amount
   *
   * @param  string  $value
   * @return string
   */
  public function getVendorDiscountBearingAmountAttribute()
  {
    $amount = 0;
    if ($this->program->programType->name === Program::VENDOR_FINANCING) {
      if ($this->program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE) {
        $amount = PaymentRequestAccount::whereHas('paymentRequest', function ($query) {
          $query->where('invoice_id', $this->id);
        })
          ->where('description', self::VENDOR_DISCOUNT_BEARING)
          ->sum('amount');
      } else {
        $amount = PaymentRequestAccount::whereHas('paymentRequest', function ($query) {
          $query->where('invoice_id', $this->id);
        })
          ->where('description', self::ANCHOR_DISCOUNT_BEARING)
          ->sum('amount');
      }
    } else {
      $amount = PaymentRequestAccount::whereHas('paymentRequest', function ($query) {
        $query->where('invoice_id', $this->id);
      })
        ->where('description', self::VENDOR_DISCOUNT_BEARING)
        ->sum('amount');
    }

    return round($amount, 2);
  }

  /**
   * Get the vendor bearing discount amount
   *
   * @param  string  $value
   * @return string
   */
  public function getAnchorDiscountBearingAmountAttribute()
  {
    $amount = 0;
    if ($this->program->programType->name === Program::VENDOR_FINANCING) {
      if ($this->program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE) {
        $amount = PaymentRequestAccount::whereHas('paymentRequest', function ($query) {
          $query->where('invoice_id', $this->id);
        })
          ->where('description', self::ANCHOR_DISCOUNT_BEARING)
          ->sum('amount');
      } else {
        $amount = PaymentRequestAccount::whereHas('paymentRequest', function ($query) {
          $query->where('invoice_id', $this->id);
        })
          ->where('description', self::VENDOR_DISCOUNT_BEARING)
          ->sum('amount');
      }
    } else {
      $amount = PaymentRequestAccount::whereHas('paymentRequest', function ($query) {
        $query->where('invoice_id', $this->id);
      })
        ->where('description', self::ANCHOR_DISCOUNT_BEARING)
        ->sum('amount');
    }

    return round($amount, 2);
  }

  /**
   * Get the vendor bearing discount amount
   *
   * @param  string  $value
   * @return string
   */
  public function getVendorFeeBearingAmountAttribute()
  {
    $amount = 0;
    if ($this->program->programType->name === Program::VENDOR_FINANCING) {
      if ($this->program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE) {
        $amount = PaymentRequestAccount::whereHas('paymentRequest', function ($query) {
          $query->where('invoice_id', $this->id);
        })
          ->where('description', self::VENDOR_FEE_BEARING)
          ->sum('amount');
      } else {
        $amount = PaymentRequestAccount::whereHas('paymentRequest', function ($query) {
          $query->where('invoice_id', $this->id);
        })
          ->where('description', self::BUYER_FEE_BEARING)
          ->sum('amount');
      }
    } else {
      $amount = PaymentRequestAccount::whereHas('paymentRequest', function ($query) {
        $query->where('invoice_id', $this->id);
      })
        ->where('description', self::VENDOR_FEE_BEARING)
        ->sum('amount');
    }

    return round($amount, 2);
  }

  /**
   * Get the vendor bearing discount amount
   *
   * @param  string  $value
   * @return string
   */
  public function getAnchorFeeBearingAmountAttribute()
  {
    $amount = 0;
    if ($this->program->programType->name === Program::VENDOR_FINANCING) {
      if ($this->program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE) {
        $amount = PaymentRequestAccount::whereHas('paymentRequest', function ($query) {
          $query->where('invoice_id', $this->id);
        })
          ->where('description', self::ANCHOR_FEE_BEARING)
          ->sum('amount');
      } else {
        $amount = PaymentRequestAccount::whereHas('paymentRequest', function ($query) {
          $query->where('invoice_id', $this->id);
        })
          ->where('description', self::VENDOR_FEE_BEARING)
          ->sum('amount');
      }
    } else {
      $amount = PaymentRequestAccount::whereHas('paymentRequest', function ($query) {
        $query->where('invoice_id', $this->id);
      })
        ->where('description', self::ANCHOR_FEE_BEARING)
        ->sum('amount');
    }

    return round($amount, 2);
  }

  public function getBalanceAttribute(): float
  {
    $amount = 0;

    if ($this->financing_status === 'disbursed') {
      if ($this->program->programType->name === Program::DEALER_FINANCING) {
        if ($this->discount_charge_type == Invoice::FRONT_ENDED) {
          // Front ended
          $amount = ($this->eligibility / 100) * $this->drawdown_amount;
        } else {
          // Rear ended
          $tax_on_discount_amount = PaymentRequestAccount::whereHas('paymentRequest', function ($q) {
            $q->where('invoice_id', $this->id);
          })
            ->whereDate('created_at', '<', Carbon::parse($this->due_date))
            ->whereIn('type', ['tax_on_discount'])
            ->sum('amount');

          $amount = $this->disbursed_amount + $this->discount + $tax_on_discount_amount;
        }

        if ($this->fee_charge_type === Invoice::REAR_ENDED) {
          $amount = $amount + $this->program_fees;
        }
      } else {
        if ($this->discount_charge_type === Invoice::FRONT_ENDED) {
          // Front ended
          $amount = ($this->eligibility / 100) * $this->invoice_total_amount;
        } else {
          // Rear ended
          $tax_on_discount_amount = PaymentRequestAccount::whereHas('paymentRequest', function ($q) {
            $q->where('invoice_id', $this->id);
          })
            ->whereDate('created_at', '<', Carbon::parse($this->due_date))
            ->whereIn('type', ['tax_on_discount'])
            ->sum('amount');

          $amount = $this->disbursed_amount + $this->discount + $tax_on_discount_amount;
        }

        if ($this->fee_charge_type === Invoice::REAR_ENDED) {
          $amount = $amount + $this->program_fees;
        }
      }

      $amount = $amount + $this->overdue_amount - $this->paid_amount;
    }

    return round($amount, 2);
  }

  /**
   * Get the pending transactions
   *
   * @return string
   */
  public function getPendingCbsTransactionsAttribute()
  {
    return round(
      CbsTransaction::whereHas('paymentRequest', function ($query) {
        $query->where('invoice_id', $this->id);
      })
        ->where('status', 'Created')
        ->sum('amount'),
      2
    );
  }

  public function getEligibleForFinanceAttribute()
  {
    if ($this->eligibility) {
      $amount = ($this->eligibility / 100) * $this->invoice_total_amount;
    } else {
      if ($this->program->programType->name == Program::DEALER_FINANCING) {
        $vendor_configuration = ProgramVendorConfiguration::where('company_id', '=', $this->company_id)
          ->where('program_id', $this->program_id)
          ->first();
      } else {
        if ($this->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
          $vendor_configuration = ProgramVendorConfiguration::where('company_id', '=', $this->company_id)
            ->where('program_id', $this->program_id)
            ->first();
        } else {
          $vendor_configuration = ProgramVendorConfiguration::where('company_id', '=', $this->company_id)
            ->where('buyer_id', $this->buyer_id)
            ->where('program_id', $this->program_id)
            ->first();
        }
      }

      $amount = ($vendor_configuration?->eligibility / 100) * $this->invoice_total_amount;
    }

    return round($amount, 2);
  }

  /**
   * Get the Eligibile for Finance
   *
   * @param  string  $value
   * @return string
   */
  public function getActualRemittanceAmountAttribute()
  {
    $amount = 0;

    if ($this->program->programType->name == Program::DEALER_FINANCING) {
      $vendor_configuration = ProgramVendorConfiguration::where('company_id', '=', $this->company_id)
        ->where('program_id', $this->program_id)
        ->first();

      $vendor_discount_details = ProgramVendorDiscount::where('company_id', $this->company_id)
        ->where('program_id', $this->program_id)
        ->first();

      $vendor_fees = ProgramVendorFee::where('company_id', $this->company_id)
        ->where('program_id', $this->program_id)
        ->get();
    } else {
      if ($this->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        $vendor_configuration = ProgramVendorConfiguration::where('company_id', '=', $this->company_id)
          ->where('program_id', $this->program_id)
          ->first();

        $vendor_discount_details = ProgramVendorDiscount::where('company_id', $this->company_id)
          ->where('program_id', $this->program_id)
          ->first();

        $vendor_fees = ProgramVendorFee::where('company_id', $this->company_id)
          ->where('program_id', $this->program_id)
          ->get();
      } else {
        $vendor_configuration = ProgramVendorConfiguration::where('company_id', '=', $this->company_id)
          ->where('buyer_id', $this->buyer_id)
          ->where('program_id', $this->program_id)
          ->first();

        $vendor_discount_details = ProgramVendorDiscount::where('company_id', $this->company_id)
          ->where('buyer_id', $this->buyer_id)
          ->where('program_id', $this->program_id)
          ->first();

        $vendor_fees = ProgramVendorFee::where('company_id', $this->company_id)
          ->where('buyer_id', $this->buyer_id)
          ->where('program_id', $this->program_id)
          ->get();
      }
    }

    // Get Tax on Discount Value
    $tax_on_discount = ProgramDiscount::where('program_id', $this->program_id)->first()?->tax_on_discount;
    $eligibility = $vendor_configuration?->eligibility;
    $legible_amount = ($eligibility / 100) * $this->invoice_total_amount;

    $total_roi = $vendor_discount_details?->total_roi;

    $payment_date = now()->format('Y-m-d');

    // Fee charges
    $fees_amount = 0;
    $anchor_bearing_fees = 0;
    $vendor_bearing_fees = 0;
    $fees_tax_amount = 0;
    if ($vendor_fees->count() > 0) {
      foreach ($vendor_fees as $fee) {
        if ($fee->type == 'amount') {
          if ($fee->charge_type === 'daily') {
            $fees_amount += $fee->value;

            $anchor_bearing_fees +=
              ($fee->anchor_bearing_discount / 100) *
              $fee->value *
              Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date));
            $vendor_bearing_fees +=
              ($fee->vendor_bearing_discount / 100) *
              $fee->value *
              Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date));

            if ($fee->taxes) {
              $fees_tax_amount += round(
                ($fee->taxes / 100) *
                  $fee->value *
                  Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date)),
                2
              );
            }
          } else {
            $fees_amount += $fee->value;

            $anchor_bearing_fees += ($fee->anchor_bearing_discount / 100) * $fee->value;
            $vendor_bearing_fees += ($fee->vendor_bearing_discount / 100) * $fee->value;

            if ($fee->taxes) {
              $fees_tax_amount += round(($fee->taxes / 100) * $fee->value, 2);
            }
          }
        }

        if ($fee->type == 'percentage') {
          if ($fee->charge_type === 'daily') {
            $fees_amount +=
              ($fee->value / 100) *
              $legible_amount *
              Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date));

            $anchor_bearing_fees += round(
              ($fee->anchor_bearing_discount / 100) *
                (($fee->value / 100) *
                  $legible_amount *
                  Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date))),
              2
            );
            $vendor_bearing_fees += round(
              ($fee->vendor_bearing_discount / 100) *
                (($fee->value / 100) *
                  $legible_amount *
                  Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date))),
              2
            );

            if ($fee->taxes) {
              $fees_tax_amount += round(
                ($fee->taxes / 100) *
                  (($fee->value / 100) *
                    $legible_amount *
                    Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date))),
                2
              );
            }
          } else {
            $fees_amount += ($fee->value / 100) * $legible_amount;

            $anchor_bearing_fees += round(
              ($fee->anchor_bearing_discount / 100) * (($fee->value / 100) * $legible_amount),
              2
            );
            $vendor_bearing_fees += round(
              ($fee->vendor_bearing_discount / 100) * (($fee->value / 100) * $legible_amount),
              2
            );

            if ($fee->taxes) {
              $fees_tax_amount += round(($fee->taxes / 100) * (($fee->value / 100) * $legible_amount), 2);
            }
          }
        }

        if ($fee->type == 'per amount') {
          if ($fee->charge_type === 'daily') {
            $fees_amount +=
              floor($legible_amount / $fee->per_amount) *
              $fee->value *
              Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date));

            $anchor_bearing_fees += round(
              ($fee->anchor_bearing_discount / 100) *
                (floor($legible_amount / $fee->per_amount) *
                  $fee->value *
                  Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date))),
              2
            );
            $vendor_bearing_fees += round(
              ($fee->vendor_bearing_discount / 100) *
                (floor($legible_amount / $fee->per_amount) *
                  $fee->value *
                  Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date))),
              2
            );

            if ($fee->taxes) {
              $fees_tax_amount += round(
                ($fee->taxes / 100) *
                  (floor($legible_amount / $fee->per_amount) *
                    $fee->value *
                    Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date))),
                2
              );
            }
          } else {
            $fees_amount += floor($legible_amount / $fee->per_amount) * $fee->value;

            $anchor_bearing_fees += round(
              ($fee->anchor_bearing_discount / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value),
              2
            );
            $vendor_bearing_fees += round(
              ($fee->vendor_bearing_discount / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value),
              2
            );

            if ($fee->taxes) {
              $fees_tax_amount += round(
                ($fee->taxes / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value),
                2
              );
            }
          }
        }
      }
    }

    $total_amount = $this->invoice_total_amount;
    $discount = 0;

    $original_discount =
      ($eligibility / 100) *
      $total_amount *
      ($total_roi / 100) *
      (Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date)) / 365);

    // Tax on discount
    $discount_tax_amount = 0;
    if ($tax_on_discount && $tax_on_discount > 0) {
      $discount_tax_amount = ($tax_on_discount / 100) * $original_discount;
    }

    if ($vendor_configuration && $vendor_discount_details) {
      if ($total_roi > 0) {
        if ($this->program->programType->name == Program::DEALER_FINANCING) {
          $discount =
            ($vendor_discount_details?->vendor_discount_bearing / $total_roi) *
            ($eligibility / 100) *
            $total_amount *
            ($total_roi / 100) *
            (Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date)) / 365);
        } else {
          $discount =
            ($vendor_discount_details?->vendor_discount_bearing / $total_roi) *
            ($eligibility / 100) *
            $total_amount *
            ($total_roi / 100) *
            (Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date)) / 365);
        }
      }

      if ($this->program->discountDetails->first()?->discount_type == Invoice::FRONT_ENDED) {
        if ($this->program->programType->name == Program::DEALER_FINANCING) {
          $amount = $legible_amount - $fees_tax_amount - $discount - $discount_tax_amount + $anchor_bearing_fees;
        } else {
          $amount = $legible_amount - $fees_tax_amount - $discount - $discount_tax_amount - $vendor_bearing_fees;
        }
      } else {
        if ($this->program->programType->name == Program::DEALER_FINANCING) {
          $amount = $legible_amount - $fees_tax_amount + $anchor_bearing_fees;
        } else {
          $amount = $legible_amount - $fees_tax_amount - $vendor_bearing_fees;
        }
      }

      if ($this->program->discountDetails->first()?->fee_type == Invoice::REAR_ENDED) {
        if ($this->program->programType->name == Program::DEALER_FINANCING) {
          $amount = $amount + $fees_tax_amount - $anchor_bearing_fees;
        } else {
          $amount = $amount + $fees_tax_amount + $vendor_bearing_fees;
        }
      }
    }

    return round($amount, 2);
  }

  public function calculateActualRemittanceAmount($date)
  {
    if ($this->program->programType->name == Program::DEALER_FINANCING) {
      $vendor_configuration = ProgramVendorConfiguration::where('company_id', '=', $this->company_id)
        ->where('program_id', $this->program_id)
        ->first();

      $vendor_discount_details = ProgramVendorDiscount::where('company_id', $this->company_id)
        ->where('program_id', $this->program_id)
        ->first();

      $vendor_fees = ProgramVendorFee::where('company_id', $this->company_id)
        ->where('program_id', $this->program_id)
        ->get();
    } else {
      if ($this->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        $vendor_configuration = ProgramVendorConfiguration::where('company_id', '=', $this->company_id)
          ->where('program_id', $this->program_id)
          ->first();

        $vendor_discount_details = ProgramVendorDiscount::where('company_id', $this->company_id)
          ->where('program_id', $this->program_id)
          ->first();

        $vendor_fees = ProgramVendorFee::where('company_id', $this->company_id)
          ->where('program_id', $this->program_id)
          ->get();
      } else {
        $vendor_configuration = ProgramVendorConfiguration::where('company_id', '=', $this->company_id)
          ->where('buyer_id', $this->buyer_id)
          ->where('program_id', $this->program_id)
          ->first();

        $vendor_discount_details = ProgramVendorDiscount::where('company_id', $this->company_id)
          ->where('buyer_id', $this->buyer_id)
          ->where('program_id', $this->program_id)
          ->first();

        $vendor_fees = ProgramVendorFee::where('company_id', $this->company_id)
          ->where('buyer_id', $this->buyer_id)
          ->where('program_id', $this->program_id)
          ->get();
      }
    }

    // Get Tax on Discount Value
    $tax_on_discount = ProgramDiscount::where('program_id', $this->program_id)->first()?->tax_on_discount;

    $legible_amount = ($vendor_configuration->eligibility / 100) * $this->invoice_total_amount;

    $total_roi = $vendor_discount_details->total_roi;

    $payment_date = $date;

    // Fee charges
    $fees_amount = 0;
    $anchor_bearing_fees = 0;
    $vendor_bearing_fees = 0;
    $fees_tax_amount = 0;
    if ($vendor_fees->count() > 0) {
      foreach ($vendor_fees as $fee) {
        if ($fee->type == 'amount') {
          if ($fee->charge_type === 'daily') {
            $fees_amount += $fee->value * Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date));

            if ($fee->taxes) {
              $fees_tax_amount += round(
                ($fee->taxes / 100) *
                  $fee->value *
                  Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date)),
                2
              );
            }

            if ($this->program->programType->name == Program::DEALER_FINANCING) {
              $anchor_bearing_fees += 0;
              $vendor_bearing_fees +=
                $fee->value * Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date));
            } else {
              $anchor_bearing_fees +=
                ($fee->anchor_bearing_discount / 100) *
                $fee->value *
                Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date));
              $vendor_bearing_fees +=
                ($fee->vendor_bearing_discount / 100) *
                $fee->value *
                Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date));
            }
          } else {
            $fees_amount += $fee->value;

            if ($fee->taxes) {
              $fees_tax_amount += round(($fee->taxes / 100) * $fee->value, 2);
            }

            if ($this->program->programType->name == Program::DEALER_FINANCING) {
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
              Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date));

            if ($fee->taxes) {
              $fees_tax_amount += round(
                ($fee->taxes / 100) *
                  (($fee->value / 100) *
                    $legible_amount *
                    Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date))),
                2
              );
            }

            if ($this->program->programType->name == Program::DEALER_FINANCING) {
              $anchor_bearing_fees += 0;
              $vendor_bearing_fees += round(
                ($fee->value / 100) *
                  $legible_amount *
                  Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date)),
                2
              );
            } else {
              $anchor_bearing_fees += round(
                ($fee->anchor_bearing_discount / 100) *
                  (($fee->value / 100) *
                    $legible_amount *
                    Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date))),
                2
              );
              $vendor_bearing_fees += round(
                ($fee->vendor_bearing_discount / 100) *
                  (($fee->value / 100) *
                    $legible_amount *
                    Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date))),
                2
              );
            }
          } else {
            $fees_amount += ($fee->value / 100) * $legible_amount;

            if ($fee->taxes) {
              $fees_tax_amount += round(($fee->taxes / 100) * (($fee->value / 100) * $legible_amount), 2);
            }

            if ($this->program->programType->name == Program::DEALER_FINANCING) {
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

        if ($fee->type == 'per amount') {
          if ($fee->charge_type === 'daily') {
            $fees_amount +=
              floor($legible_amount / $fee->per_amount) *
              $fee->value *
              Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date));

            if ($fee->taxes) {
              $fees_tax_amount += round(
                ($fee->taxes / 100) *
                  (floor($legible_amount / $fee->per_amount) *
                    $fee->value *
                    Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date))),
                2
              );
            }

            if ($this->program->programType->name == Program::DEALER_FINANCING) {
              $anchor_bearing_fees += 0;
              $vendor_bearing_fees += round(
                floor($legible_amount / $fee->per_amount) *
                  $fee->value *
                  Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date)),
                2
              );
            } else {
              $anchor_bearing_fees += round(
                ($fee->anchor_bearing_discount / 100) *
                  (floor($legible_amount / $fee->per_amount) *
                    $fee->value *
                    Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date))),
                2
              );
              $vendor_bearing_fees += round(
                ($fee->vendor_bearing_discount / 100) *
                  (floor($legible_amount / $fee->per_amount) *
                    $fee->value *
                    Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date))),
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

            if ($this->program->programType->name == Program::DEALER_FINANCING) {
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

    $total_amount = $this->invoice_total_amount;
    $eligibility = $vendor_configuration->eligibility;

    $original_discount =
      ($eligibility / 100) *
      $total_amount *
      ($total_roi / 100) *
      (Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date)) / 365);

    // Tax on discount
    $discount_tax_amount = 0;
    if ($tax_on_discount && $tax_on_discount > 0) {
      $discount_tax_amount = ($tax_on_discount / 100) * $original_discount;
    }

    $discount = 0;
    if ($total_roi > 0) {
      if ($this->program->programType->name == Program::DEALER_FINANCING) {
        $discount = $original_discount;
      } else {
        $discount =
          ($vendor_discount_details->vendor_discount_bearing / $total_roi) *
          ($eligibility / 100) *
          $total_amount *
          ($total_roi / 100) *
          (Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date)) / 365);
      }
    }

    if ($this->program->programType->name == Program::VENDOR_FINANCING) {
      if ($this->program->discountDetails->first()?->discount_type == Invoice::FRONT_ENDED) {
        $amount = $legible_amount - $fees_tax_amount - $discount - $discount_tax_amount - $vendor_bearing_fees;
      } else {
        $amount = $legible_amount - $fees_tax_amount - $vendor_bearing_fees;
      }
    }

    if ($this->program->discountDetails->first()?->fee_type == Invoice::REAR_ENDED) {
      $amount = $amount + $fees_tax_amount + $vendor_bearing_fees;
    }

    return [
      'actual_remittance' => round($amount, 2),
      'discount' => round($discount, 2),
      'tax_on_discount' => round($tax_on_discount, 2),
    ];
  }

  public function getDaysPastDueAttribute()
  {
    if ($this->financing_status === 'disbursed' && now()->greaterThan(Carbon::parse($this->due_date))) {
      return now()->diffInDays(Carbon::parse($this->due_date));
    }

    return 0;
  }

  // Used in calculation of balance of invoice
  public function getOverdueAmountAttribute()
  {
    $amount = 0;

    if ($this->financing_status == 'disbursed' || $this->financing_status == 'closed') {
      // Get Cbs Transactions that were created past the due date
      $amount = CbsTransaction::whereHas('paymentRequest', function ($query) {
        $query->where('invoice_id', $this->id);
      })
        ->whereDate('created_at', '>', Carbon::parse($this->due_date))
        ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
        ->whereIn('status', ['Created', 'Permanently Failed', 'Failed'])
        ->sum('amount');
    }

    return round($amount, 2);
  }

  /**
   * Get the paid overdue amount
   *
   * @param  string  $value
   * @return string
   */
  public function getPaidOverdueAmountAttribute()
  {
    $amount = 0;

    if ($this->financing_status == 'disbursed' || $this->financing_status == 'closed') {
      // Get Cbs transactions that were created past the due date
      CbsTransaction::whereHas('paymentRequest', function ($query) {
        $query->where('invoice_id', $this->id);
      })
        ->whereDate('created_at', '>', Carbon::parse($this->due_date))
        ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
        ->where('status', 'Successful')
        ->sum('amount');
    }

    return round($amount, 2);
  }

  // Calculation for general over due amount
  public function getOverdueAttribute()
  {
    $amount = 0;

    if ($this->financing_status == 'disbursed' || $this->financing_status == 'closed') {
      // Get Cbs Transactions that were created past the due date
      CbsTransaction::whereHas('paymentRequest', function ($query) {
        $query->where('invoice_id', $this->id);
      })
        ->whereDate('created_at', '>', Carbon::parse($this->due_date))
        ->whereIn('transaction_type', [CbsTransaction::OVERDUE_ACCOUNT])
        ->sum('amount');
    }

    return round($amount, 2);
  }

  public function resolveStatus()
  {
    switch ($this->status) {
      case 'pending':
        return 'bg-label-primary';
        break;
      case 'approved':
        return 'bg-label-success';
        break;
      case 'rejected':
        return 'bg-label-danger';
        break;
      case 'overdue':
        return 'bg-label-danger';
        break;
      case 'disbursed':
        return 'bg-label-success';
        break;
      default:
        return 'bg-label-primary';
        break;
    }
  }

  public function getOverdueAmount()
  {
    $amount = 0;
    if ($this->financing_status === 'disbursed' && now()->greaterThan(Carbon::parse($this->due_date))) {
      $amount = PaymentRequest::where('invoice_id', $this->id)
        ->whereDate('created_at', '>', $this->due_date)
        ->whereIn('status', ['created', 'approved'])
        ->sum('amount');
    }

    return round($amount, 2);
  }

  /**
   * Get the user can approve attribute
   *
   * @param  string  $value
   * @return string
   */
  public function getUserCanApproveAttribute()
  {
    if ($this->status === 'pending') {
      // Invoice is still at vendor company level
      if ($this->program->programType->name === Program::DEALER_FINANCING) {
        if (
          auth()
            ->user()
            ->hasPermissionTo('View Invoices')
        ) {
          if (!$this->user_has_approved) {
            return true;
          }

          return false;
        }

        return false;
      } else {
        if ($this->program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE) {
          if (
            auth()
              ->user()
              ->can('Invoice Checker')
          ) {
            if (!$this->user_has_approved) {
              return true;
            }

            return false;
          }
        } else {
          if (
            auth()
              ->user()
              ->can('Seller Invoice Checker')
          ) {
            if (!$this->user_has_approved) {
              return true;
            }

            return false;
          }
        }
      }
    }

    if ($this->status === 'submitted') {
      if ($this->program->account_status != 'active') {
        return false;
      }

      $vendor_configuration = ProgramVendorConfiguration::where('company_id', $this->company_id)
        ->when($this->buyer_id, function ($query) {
          $query->where('buyer_id', $this->buyer_id);
        })
        ->where('program_id', $this->program_id)
        ->first();

      if (!$vendor_configuration || !$vendor_configuration->is_approved || $vendor_configuration->status != 'active') {
        return false;
      }

      // Invoice has been submitted to anchor company for approval
      if ($this->program->programType->name === Program::DEALER_FINANCING) {
        // Check if user is in authorization matrix group
        $authorization_matrix = CompanyAuthorizationMatrix::where('company_id', $this->program->anchor->id)
          ->where('min_pi_amount', '<=', $this->total + $this->total_invoice_taxes)
          ->where('max_pi_amount', '>=', $this->total + $this->total_invoice_taxes)
          ->where('status', 'active')
          ->where('program_type_id', $this->program->program_type_id)
          ->first();

        $rules = AuthorizationMatrixRule::where('matrix_id', $authorization_matrix?->id)->pluck('group_id');

        $authorization_group = CompanyAuthorizationGroup::where('company_id', $this->program->anchor->id)
          ->whereIn('id', $rules)
          ->where('status', 'active')
          ->pluck('id');

        $user_authorization_group = CompanyUserAuthorizationGroup::where('company_id', $this->program->anchor->id)
          ->whereIn('group_id', $authorization_group)
          ->where('user_id', auth()->id())
          ->first();

        if ($user_authorization_group) {
          $anchor_users = $this->program->anchor->users->pluck('id');

          $current_anchor_approvals = InvoiceApproval::where('invoice_id', $this->id)
            ->whereIn('user_id', $anchor_users)
            ->count();

          if (
            $current_anchor_approvals == 0 &&
            auth()
              ->user()
              ->can('Manage Dealer Invoices')
          ) {
            return true;
          } elseif (
            $current_anchor_approvals >= 1 &&
            !$this->user_has_approved &&
            auth()
              ->user()
              ->can('Dealer Invoice Checker')
          ) {
            return true;
          }
        }

        return false;
      } else {
        if (
          $this->program->programType->name === Program::VENDOR_FINANCING &&
          $this->program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE
        ) {
          // Check if user is in authorization matrix group
          $authorization_matrix = CompanyAuthorizationMatrix::where('company_id', $this->program->anchor->id)
            ->where('min_pi_amount', '<=', $this->total + $this->total_invoice_taxes)
            ->where('max_pi_amount', '>=', $this->total + $this->total_invoice_taxes)
            ->where('status', 'active')
            ->where('program_type_id', $this->program->program_type_id)
            ->first();

          $rules = AuthorizationMatrixRule::where('matrix_id', $authorization_matrix?->id)->pluck('group_id');

          $authorization_group = CompanyAuthorizationGroup::where('company_id', $this->program->anchor->id)
            ->whereIn('id', $rules)
            ->where('status', 'active')
            ->pluck('id');

          $user_authorization_group = CompanyUserAuthorizationGroup::where('company_id', $this->program->anchor->id)
            ->whereIn('group_id', $authorization_group)
            ->where('user_id', auth()->id())
            ->first();

          if ($user_authorization_group) {
            $anchor_users = $this->program->anchor->users->pluck('id');

            $current_anchor_approvals = InvoiceApproval::where('invoice_id', $this->id)
              ->whereIn('user_id', $anchor_users)
              ->count();

            if (
              $current_anchor_approvals == 0 &&
              auth()
                ->user()
                ->can('Approve Invoices - Level 1')
            ) {
              return true;
            } elseif (
              $current_anchor_approvals >= 1 &&
              !$this->user_has_approved &&
              auth()
                ->user()
                ->can('Approve Invoices - Level 2')
            ) {
              return true;
            }
          }

          return false;
        } else {
          // Check if user is in authorization matrix group
          $authorization_matrix = CompanyAuthorizationMatrix::where('company_id', $this->buyer->id)
            ->where('min_pi_amount', '<=', $this->total + $this->total_invoice_taxes)
            ->where('max_pi_amount', '>=', $this->total + $this->total_invoice_taxes)
            ->where('status', 'active')
            ->where('program_type_id', $this->program->program_type_id)
            ->first();

          $rules = AuthorizationMatrixRule::where('matrix_id', $authorization_matrix?->id)->pluck('group_id');

          $authorization_group = CompanyAuthorizationGroup::where('company_id', $this->buyer->id)
            ->whereIn('id', $rules)
            ->where('status', 'active')
            ->pluck('id');

          $user_authorization_group = CompanyUserAuthorizationGroup::where('company_id', $this->buyer->id)
            ->whereIn('group_id', $authorization_group)
            ->where('user_id', auth()->id())
            ->first();

          if ($user_authorization_group) {
            $anchor_users = $this->buyer->users->pluck('id');

            $current_anchor_approvals = InvoiceApproval::where('invoice_id', $this->id)
              ->whereIn('user_id', $anchor_users)
              ->count();

            if (
              $current_anchor_approvals === 0 &&
              auth()
                ->user()
                ->can('Approve Invoices - Level 1')
            ) {
              return true;
            } elseif (
              $current_anchor_approvals >= 1 &&
              !$this->user_has_approved &&
              auth()
                ->user()
                ->can('Approve Invoices - Level 2')
            ) {
              return true;
            }
          }

          return false;
        }
      }
    }
  }

  /**
   * Get the approval stage
   *
   * @return string
   */
  public function getApprovalStageAttribute()
  {
    $status = '';
    if (Carbon::parse($this->due_date)->lessThan(now()->subDay())) {
      if ($this->financing_status === 'closed') {
        $status = $this->status;
      } elseif ($this->financing_status === 'disbursed') {
        $status = 'overdue';
      } else {
        $status = 'expired';
      }
    } elseif ($this->financing_status === 'closed') {
      $status = $this->status;
    } else {
      if ($this->status === 'submitted') {
        $status = Str::replace('_', ' ', $this->stage);
      } elseif ($this->status === 'disbursed') {
        if (Carbon::parse($this->due_date)->lessThan(now()->subDay())) {
          $status = 'overdue';
        } else {
          $status = $this->status;
        }
      } elseif ($this->status == 'denied') {
        if ($this->approvals->count() == 0) {
          $status = 'internal reject';
        } else {
          $status = $this->status;
        }
      } else {
        if (Carbon::parse($this->due_date)->lessThan(now()->subDay())) {
          $status = 'overdue';
        } else {
          $status = $this->status;
        }
      }
    }

    return Str::headline($status);
  }

  /**
   * Get the can edit attribute
   *
   * @param  string  $value
   * @return bool
   */
  public function getCanEditAttribute()
  {
    // Check if program allows anchor to edit invoices
    $anchor_can_change_due_date = $this->program->anchor_can_change_due_date;
    $days_limit_for_due_date_change = $this->program->days_limit_for_due_date_change;
    if (auth()->check()) {
      $company_users = $this->company->users->pluck('id');
      if ($this->program->programType->name == Program::VENDOR_FINANCING) {
        if ($this->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
          // Check if user is in owner company of invoice
          if (collect($company_users)->contains(auth()->id())) {
            if (
              auth()
                ->user()
                ->hasPermissionTo('Edit Invoice') &&
              ($this->status === 'created' || $this->status === 'pending' || $this->status === 'submitted') &&
              $this->paymentRequests->count() <= 0
            ) {
              return true;
            } else {
              return false;
            }
          } else {
            // User is in anchor company
            if (
              auth()
                ->user()
                ->hasPermissionTo('Change Invoice Due Date') &&
              ($this->status == 'created' || $this->status == 'pending' || $this->status == 'submitted') &&
              $this->paymentRequests->count() <= 0 &&
              $anchor_can_change_due_date &&
              now()->diffInDays($this->created_at) <= $days_limit_for_due_date_change
            ) {
              return true;
            } else {
              return false;
            }
          }
        } else {
          // User is in buyer company
          if (
            auth()
              ->user()
              ->hasPermissionTo('Change Invoice Due Date') &&
            ($this->status == 'created' || $this->status == 'pending' || $this->status == 'submitted') &&
            $this->paymentRequests->count() <= 0 &&
            $anchor_can_change_due_date &&
            now()->diffInDays($this->created_at) <= $days_limit_for_due_date_change
          ) {
            return true;
          } else {
            return false;
          }
        }
      }
    }

    return false;
  }

  public function getCanEditFeesAttribute()
  {
    if (auth()->check()) {
      if (
        auth()
          ->user()
          ->hasAllPermissions(['Approve Invoices - Level 1'])
      ) {
        return true;
      } else {
        return false;
      }
    } else {
      return false;
    }
  }

  /**
   * Get the user has approved attribute
   *
   * @return boolean
   */
  public function getUserHasApprovedAttribute()
  {
    return $this->approvals()
      ->where('user_id', auth()->id())
      ->exists();
  }

  /**
   * Get the user has approved
   *
   * @return boolean
   */
  // public function getUserHasApprovedAttribute()
  // {
  //   if (auth()->check()) {
  //     if ($this->program->programType->name == Program::DEALER_FINANCING) {
  //       if ($this->status == 'submitted') {
  //         if (
  //           auth()
  //             ->user()
  //             ->hasPermissionTo('Invoice Checker')
  //         ) {
  //           $has_approved = $this->approvals()
  //             ->where('user_id', auth()->id())
  //             ->exists();

  //           if ($has_approved) {
  //             return true;
  //           }

  //           return false;
  //         }

  //         return true;
  //       }
  //     }

  //     if ($this->program->programType->name == Program::VENDOR_FINANCING) {
  //       if ($this->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
  //         if ($this->status == 'submitted') {
  //           $has_approved = $this->approvals()
  //             ->where('user_id', auth()->id())
  //             ->exists();

  //           if ($has_approved) {
  //             return true;
  //           }

  //           return false;
  //         }
  //         if ($this->status == 'pending') {
  //           $has_approved = $this->approvals()
  //             ->where('user_id', auth()->id())
  //             ->exists();

  //           if (
  //             $has_approved &&
  //             auth()
  //               ->user()
  //               ->hasPermissionTo('Invoice Checker')
  //           ) {
  //             return true;
  //           }

  //           return false;
  //         }
  //       } else {
  //         $has_approved = $this->approvals()
  //           ->where('user_id', auth()->id())
  //           ->exists();

  //         if ($has_approved) {
  //           return true;
  //         }

  //         return false;
  //       }
  //     }
  //     return true;
  //   }

  //   return false;
  // }

  /**
   * Get the charged fees
   *
   * @param  string  $value
   * @return string
   */
  public function getChargedFeesAttribute()
  {
    if (
      $this->financing_status == 'submitted' ||
      $this->financing_status == 'financed' ||
      $this->financing_status == 'disbursed' ||
      $this->financing_status == 'closed'
    ) {
      return round(
        PaymentRequestAccount::whereHas('paymentRequest', function ($query) {
          $query->where('invoice_id', $this->id);
        })
          ->whereIn('type', ['program_fees', 'program_fees_taxes', 'tax_on_fees'])
          ->sum('amount'),
        2
      );
    }

    return 0.0;
  }

  public function getAmount()
  {
    if (!$this->invoiceItems()->exists()) {
      return $this->total_amount;
    }

    $amount = 0;
    foreach ($this->invoiceItems as $key => $item) {
      $amount += $item->quantity * $item->price_per_quantity;
    }

    return round($amount, 2);
  }

  public function getCanDeleteAttribute()
  {
    if (auth()->check()) {
      if (
        auth()
          ->user()
          ->hasPermissionTo('Delete Invoice')
      ) {
        if ($this->status == 'created' || $this->status == 'submitted' || $this->status == 'pending') {
          return true;
        } else {
          return false;
        }
      } else {
        return false;
      }
    } else {
      return false;
    }

    return false;
  }

  public function getCanDeleteAttachmentAttribute()
  {
    if (auth()->check()) {
      if (
        auth()
          ->user()
          ->hasPermissionTo('Delete Attachments from Invoices')
      ) {
        if ($this->status == 'created' || $this->status == 'submitted' || $this->status == 'pending') {
          return true;
        } else {
          return false;
        }
      } else {
        return false;
      }
    } else {
      return false;
    }
  }

  /**
   * Get the closure date attribute
   *
   * @return string
   */
  public function getClosureDateAttribute()
  {
    if ($this->financing_status === 'closed') {
      return CbsTransaction::whereHas('paymentRequest', function ($query) {
        $query->where('invoice_id', $this->id);
      })
        ->where('transaction_type', CbsTransaction::BANK_INVOICE_PAYMENT)
        ->where('status', 'Successful')
        ->latest()
        ->first()?->transaction_date;
    }

    return null;
  }

  /**
   * Get the closure transaction reference attribute
   *
   * @return string
   */
  public function getClosureTransactionReferenceAttribute()
  {
    if ($this->financing_status === 'closed') {
      return CbsTransaction::whereHas('paymentRequest', function ($query) {
        $query->where('invoice_id', $this->id);
      })
        ->where('transaction_type', CbsTransaction::BANK_INVOICE_PAYMENT)
        ->where('status', 'Successful')
        ->latest()
        ->first()?->transaction_reference;
    }

    return null;
  }

  // Vendor Financing Requesting
  public function requestFinance(ProgramVendorConfiguration $vendor_configurations, $credit_to, $payment_date)
  {
    $this->update([
      'calculated_total_amount' => $this->invoice_total_amount,
    ]);

    // Check if payment date falls beyond the max financing period
    $max_financing_period = $vendor_configurations->program->max_financing_days;
    $date = Carbon::parse($payment_date);

    if (
      $this->paymentRequests->count() <= 0 &&
      $date->diffInDays(Carbon::parse($this->due_date)) <= $max_financing_period
    ) {
      $vendor_financing = ProgramType::where('name', Program::VENDOR_FINANCING)->first();
      $vendor_financing_receivable = ProgramCode::where('name', Program::VENDOR_FINANCING_RECEIVABLE)->first();
      $dealer_financing = ProgramType::where('name', Program::DEALER_FINANCING)->first();
      $factoring_with_recourse = ProgramCode::where('name', Program::FACTORING_WITH_RECOURSE)->first();
      $factoring_without_recourse = ProgramCode::where('name', Program::FACTORING_WITHOUT_RECOURSE)->first();

      $discount_income_bank_account = null;
      $fees_income_bank_account = null;
      $tax_income_bank_account = null;
      $noa_text = null;
      $reference_number = '';

      $words = explode(' ', $this->company->name);
      $acronym = '';

      foreach ($words as $w) {
        $acronym .= mb_substr($w, 0, 1);
      }

      // Get Bank Configured Receivable Accounts
      if ($this->program->programType->name == Program::VENDOR_FINANCING) {
        if ($this->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
          // Vendor Financing Receivable
          $bank_details = ProgramVendorBankDetail::select('name_as_per_bank', 'account_number')->find($credit_to);

          $vendor_discount_details = ProgramVendorDiscount::select(
            'total_roi',
            'anchor_discount_bearing',
            'vendor_discount_bearing',
            'penal_discount_on_principle',
            'grace_period',
            'grace_period_discount'
          )
            ->where('company_id', $this->company_id)
            ->where('program_id', $this->program_id)
            ->first();
          $vendor_fees = ProgramVendorFee::select(
            'anchor_bearing_discount',
            'vendor_bearing_discount',
            'type',
            'value',
            'per_amount',
            'taxes',
            'fee_name',
            'charge_type',
            'account_number',
            'account_name'
          )
            ->where('company_id', $this->company_id)
            ->where('program_id', $this->program_id)
            ->get();

          $discount_income_bank_account = BankProductsConfiguration::where('bank_id', $this->program->bank->id)
            ->where('section', 'Vendor Finance Receivable')
            ->where('product_code_id', $vendor_financing_receivable->id)
            ->where('product_type_id', $vendor_financing->id)
            ->where('name', 'Discount Income Account')
            ->first();
          $discount_receivable_bank_account = BankProductsConfiguration::where('bank_id', $this->program->bank->id)
            ->where('section', 'Vendor Finance Receivable')
            ->where('product_code_id', $vendor_financing_receivable->id)
            ->where('product_type_id', $vendor_financing->id)
            ->where('name', 'Discount Receivable Account')
            ->first();
          $advanced_discount_bank_account = BankProductsConfiguration::where('bank_id', $this->program->bank->id)
            ->where('section', 'Vendor Finance Receivable')
            ->where('product_code_id', $vendor_financing_receivable->id)
            ->where('product_type_id', $vendor_financing->id)
            ->where('name', 'Advance Discount Account')
            ->first();
          $unrealized_discount_bank_account = BankProductsConfiguration::where('bank_id', $this->program->bank->id)
            ->where('section', 'Vendor Finance Receivable')
            ->where('product_code_id', $vendor_financing_receivable->id)
            ->where('product_type_id', $vendor_financing->id)
            ->where('name', 'Unrealised Discount Account')
            ->first();
          $fees_income_bank_account = BankProductsConfiguration::where('bank_id', $this->program->bank->id)
            ->where('section', 'Vendor Finance Receivable')
            ->where('product_code_id', $vendor_financing_receivable->id)
            ->where('product_type_id', $vendor_financing->id)
            ->where('name', 'Fee Income Account')
            ->first();
          $noa_text = NoaTemplate::where('product_type', 'vendor_financing')
            ->where('status', 'active')
            ->where('bank_id', $this->program->bank_id)
            ->first();

          $reference_number =
            'VFR0' .
            $this->program->bank_id .
            '' .
            Carbon::parse($payment_date)->format('y') .
            '000' .
            Helpers::generateSequentialReferenceNumber($this->program->bank_id, Program::VENDOR_FINANCING, [
              Program::VENDOR_FINANCING_RECEIVABLE,
            ]);
        } else {
          $bank_details = ProgramBankDetails::select('name_as_per_bank', 'account_number')->find($credit_to);

          $vendor_discount_details = ProgramVendorDiscount::select(
            'total_roi',
            'anchor_discount_bearing',
            'vendor_discount_bearing',
            'penal_discount_on_principle',
            'grace_period',
            'grace_period_discount'
          )
            ->where('buyer_id', $this->buyer_id)
            ->where('program_id', $this->program_id)
            ->first();
          $vendor_fees = ProgramVendorFee::select(
            'anchor_bearing_discount',
            'vendor_bearing_discount',
            'type',
            'value',
            'per_amount',
            'taxes',
            'fee_name',
            'charge_type',
            'account_number',
            'account_name'
          )
            ->where('company_id', $this->company_id)
            ->where('buyer_id', $this->buyer_id)
            ->where('program_id', $this->program_id)
            ->get();

          if ($this->program->programCode->name === Program::FACTORING_WITHOUT_RECOURSE) {
            // Factoring without recourse
            $discount_income_bank_account = BankProductsConfiguration::where('bank_id', $this->program->bank->id)
              ->where('section', 'Factoring Without Recourse')
              ->where('product_type_id', $vendor_financing->id)
              ->where('product_code_id', $this->program->program_code_id)
              ->where('name', 'Discount Income Account')
              ->first();
            $discount_receivable_bank_account = BankProductsConfiguration::where('bank_id', $this->program->bank->id)
              ->where('section', 'Factoring Without Recourse')
              ->where('product_type_id', $vendor_financing->id)
              ->where('product_code_id', $this->program->program_code_id)
              ->where('name', 'Discount Receivable Account')
              ->first();
            $advanced_discount_bank_account = BankProductsConfiguration::where('bank_id', $this->program->bank->id)
              ->where('section', 'Factoring Without Recourse')
              ->where('product_type_id', $vendor_financing->id)
              ->where('product_code_id', $this->program->program_code_id)
              ->where('name', 'Advance Discount Account')
              ->first();
            $unrealized_discount_bank_account = BankProductsConfiguration::where('bank_id', $this->program->bank->id)
              ->where('section', 'Factoring Without Recourse')
              ->where('product_type_id', $vendor_financing->id)
              ->where('product_code_id', $this->program->program_code_id)
              ->where('name', 'Unrealised Discount Account')
              ->first();
            $fees_income_bank_account = BankProductsConfiguration::where('bank_id', $this->program->bank->id)
              ->where('section', 'Factoring Without Recourse')
              ->where('product_type_id', $vendor_financing->id)
              ->where('product_code_id', $this->program->program_code_id)
              ->where('name', 'Fee Income Account')
              ->first();
            $noa_text = NoaTemplate::where('product_type', 'factoring')
              ->where('status', 'active')
              ->where('bank_id', $this->program->bank_id)
              ->first();

            $reference_number =
              'FWR0' .
              $this->program->bank_id .
              '' .
              Carbon::parse($payment_date)->format('y') .
              '000' .
              Helpers::generateSequentialReferenceNumber($this->program->bank_id, Program::VENDOR_FINANCING, [
                Program::FACTORING_WITHOUT_RECOURSE,
                Program::FACTORING_WITH_RECOURSE,
              ]);
          } else {
            // Factoring with recourse
            $discount_income_bank_account = BankProductsConfiguration::where('bank_id', $this->program->bank->id)
              ->where('section', 'Factoring With Recourse')
              ->where('product_type_id', $vendor_financing->id)
              ->where('product_code_id', $this->program->program_code_id)
              ->where('name', 'Discount Income Account')
              ->first();
            $discount_receivable_bank_account = BankProductsConfiguration::where('bank_id', $this->program->bank->id)
              ->where('section', 'Factoring With Recourse')
              ->where('product_type_id', $vendor_financing->id)
              ->where('product_code_id', $this->program->program_code_id)
              ->where('name', 'Discount Receivable Account')
              ->first();
            $advanced_discount_bank_account = BankProductsConfiguration::where('bank_id', $this->program->bank->id)
              ->where('section', 'Factoring With Recourse')
              ->where('product_type_id', $vendor_financing->id)
              ->where('product_code_id', $this->program->program_code_id)
              ->where('name', 'Advance Discount Account')
              ->first();
            $unrealized_discount_bank_account = BankProductsConfiguration::where('bank_id', $this->program->bank->id)
              ->where('section', 'Factoring With Recourse')
              ->where('product_type_id', $vendor_financing->id)
              ->where('product_code_id', $this->program->program_code_id)
              ->where('name', 'Unrealised Discount Account')
              ->first();
            $fees_income_bank_account = BankProductsConfiguration::where('bank_id', $this->program->bank->id)
              ->where('section', 'Factoring With Recourse')
              ->where('product_type_id', $vendor_financing->id)
              ->where('product_code_id', $this->program->program_code_id)
              ->where('name', 'Fee Income Account')
              ->first();

            $reference_number =
              'FR0' .
              $this->program->bank_id .
              '' .
              Carbon::parse($payment_date)->format('y') .
              '000' .
              Helpers::generateSequentialReferenceNumber($this->program->bank_id, Program::VENDOR_FINANCING, [
                Program::FACTORING_WITH_RECOURSE,
                Program::FACTORING_WITHOUT_RECOURSE,
              ]);
          }
        }
      } else {
        // Dealer Financing
        $bank_details = ProgramVendorBankDetail::select('name_as_per_bank', 'account_number')->find($credit_to);

        $vendor_discount_details = ProgramVendorDiscount::select(
          'total_roi',
          'anchor_discount_bearing',
          'vendor_discount_bearing',
          'penal_discount_on_principle',
          'grace_period',
          'grace_period_discount'
        )
          ->where('company_id', $this->company_id)
          ->where('program_id', $this->program_id)
          ->first();

        $vendor_fees = ProgramVendorFee::select(
          'anchor_bearing_discount',
          'vendor_bearing_discount',
          'type',
          'value',
          'per_amount',
          'taxes',
          'fee_name',
          'charge_type',
          'account_number',
          'account_name'
        )
          ->where('company_id', $this->company_id)
          ->where('program_id', $this->program_id)
          ->get();

        $discount_income_bank_account = BankProductsConfiguration::where('bank_id', $this->program->bank->id)
          ->where('product_type_id', $dealer_financing->id)
          ->where('product_code_id', null)
          ->where('name', 'Discount Income Account')
          ->first();
        $discount_receivable_bank_account = BankProductsConfiguration::where('bank_id', $this->program->bank->id)
          ->where('product_type_id', $dealer_financing->id)
          ->where('product_code_id', null)
          ->where('name', 'Discount Receivable from Overdraft')
          ->first();
        // DF Doesn't have Advanced Discount Account Yet
        $advanced_discount_bank_account = BankProductsConfiguration::where('bank_id', $this->program->bank->id)
          ->where('product_type_id', $dealer_financing->id)
          ->where('product_code_id', null)
          ->where('name', 'Discount Income Account')
          ->first();
        $unrealized_discount_bank_account = BankProductsConfiguration::where('bank_id', $this->program->bank->id)
          ->where('product_type_id', $dealer_financing->id)
          ->where('product_code_id', null)
          ->where('name', 'Unrealised Discount Account')
          ->first();
        $fees_income_bank_account = BankProductsConfiguration::where('bank_id', $this->program->bank->id)
          ->where('product_type_id', $dealer_financing->id)
          ->where('product_code_id', null)
          ->where('name', 'Fee Income Account')
          ->first();
        $tax_income_bank_account = BankProductsConfiguration::where('bank_id', $this->program->bank->id)
          ->where('product_type_id', $dealer_financing->id)
          ->where('product_code_id', null)
          ->where('name', 'Tax Account Number')
          ->first();
        $noa_text = NoaTemplate::where('product_type', 'dealer_financing')
          ->where('status', 'active')
          ->where('bank_id', $this->program->bank_id)
          ->first();

        $latest_payment_reference_number = PaymentRequest::whereHas('invoice', function ($query) {
          $query->whereHas('program', function ($query) {
            $query->where('bank_id', $this->program->bank_id)->whereHas('programType', function ($query) {
              $query->where('name', Program::DEALER_FINANCING);
            });
          });
        })->max('id');

        $reference_number =
          'DF0' .
          $this->program->bank_id .
          '' .
          Carbon::parse($payment_date)->format('y') .
          '000' .
          Helpers::generateSequentialReferenceNumber($this->program->bank_id, Program::DEALER_FINANCING);
      }

      if (!$noa_text) {
        $noa_text = NoaTemplate::where('product_type', 'generic')
          ->where('status', 'active')
          ->first();
      }

      $discount_type = $this->program->discountDetails->first()?->discount_type;
      $fee_type = $this->program->discountDetails->first()?->fee_type;

      if (!$discount_type) {
        $discount_type = self::FRONT_ENDED;
      }

      if (!$fee_type) {
        $fee_type = self::FRONT_ENDED;
      }

      // Get Tax on Discount Value
      $tax_on_discount = ProgramDiscount::select('tax_on_discount')
        ->where('program_id', $this->program_id)
        ->first()?->tax_on_discount;

      $eligibility = $vendor_configurations->eligibility;
      $total_amount = $this->calculated_total_amount;
      if ($total_amount == 0) {
        $total_amount = $this->invoice_total_amount;
      }

      $total_roi = $vendor_discount_details ? $vendor_discount_details->total_roi : 0;
      $legible_amount = ($eligibility / 100) * $total_amount;

      // FRONT ENDED
      // Fee charges
      $fees_amount = 0;
      $anchor_bearing_fees = 0;
      $vendor_bearing_fees = 0;
      $fees_tax_amount = 0;
      if ($vendor_fees->count() > 0) {
        foreach ($vendor_fees as $fee) {
          if ($fee->type === 'amount') {
            if ($fee->charge_type === 'daily') {
              $fees_amount += $fee->value * Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date));

              if ($fee->taxes) {
                $fees_tax_amount += round(
                  ($fee->taxes / 100) *
                    $fee->value *
                    Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date)),
                  2
                );
              }

              if ($this->program->programType->name === Program::DEALER_FINANCING) {
                $anchor_bearing_fees += 0;
                $vendor_bearing_fees +=
                  $fee->value * Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date));
              } else {
                $anchor_bearing_fees +=
                  ($fee->anchor_bearing_discount / 100) *
                  $fee->value *
                  Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date));
                $vendor_bearing_fees +=
                  ($fee->vendor_bearing_discount / 100) *
                  $fee->value *
                  Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date));
              }
            } else {
              $fees_amount += $fee->value;

              if ($fee->taxes) {
                $fees_tax_amount += round(($fee->taxes / 100) * $fee->value, 2);
              }

              if ($this->program->programType->name === Program::DEALER_FINANCING) {
                $anchor_bearing_fees += 0;
                $vendor_bearing_fees += $fee->value;
              } else {
                $anchor_bearing_fees += ($fee->anchor_bearing_discount / 100) * $fee->value;
                $vendor_bearing_fees += ($fee->vendor_bearing_discount / 100) * $fee->value;
              }
            }
          }

          if ($fee->type === 'percentage') {
            if ($fee->charge_type === 'daily') {
              $fees_amount +=
                ($fee->value / 100) *
                $legible_amount *
                Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date));

              if ($fee->taxes) {
                $fees_tax_amount += round(
                  ($fee->taxes / 100) *
                    (($fee->value / 100) *
                      $legible_amount *
                      Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date))),
                  2
                );
              }

              if ($this->program->programType->name === Program::DEALER_FINANCING) {
                $anchor_bearing_fees += 0;
                $vendor_bearing_fees += round(
                  ($fee->value / 100) *
                    $legible_amount *
                    Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date)),
                  2
                );
              } else {
                $anchor_bearing_fees += round(
                  ($fee->anchor_bearing_discount / 100) *
                    (($fee->value / 100) *
                      $legible_amount *
                      Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date))),
                  2
                );
                $vendor_bearing_fees += round(
                  ($fee->vendor_bearing_discount / 100) *
                    (($fee->value / 100) *
                      $legible_amount *
                      Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date))),
                  2
                );
              }
            } else {
              $fees_amount += ($fee->value / 100) * $legible_amount;

              if ($fee->taxes) {
                $fees_tax_amount += round(($fee->taxes / 100) * (($fee->value / 100) * $legible_amount), 2);
              }

              if ($this->program->programType->name === Program::DEALER_FINANCING) {
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
                Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date));

              if ($fee->taxes) {
                $fees_tax_amount += round(
                  ($fee->taxes / 100) *
                    (floor($legible_amount / $fee->per_amount) *
                      $fee->value *
                      Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date))),
                  2
                );
              }

              if ($this->program->programType->name === Program::DEALER_FINANCING) {
                $anchor_bearing_fees += 0;
                $vendor_bearing_fees += round(
                  floor($legible_amount / $fee->per_amount) *
                    $fee->value *
                    Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date)),
                  2
                );
              } else {
                $anchor_bearing_fees += round(
                  ($fee->anchor_bearing_discount / 100) *
                    (floor($legible_amount / $fee->per_amount) *
                      $fee->value *
                      Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date))),
                  2
                );
                $vendor_bearing_fees += round(
                  ($fee->vendor_bearing_discount / 100) *
                    (floor($legible_amount / $fee->per_amount) *
                      $fee->value *
                      Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date))),
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

              if ($this->program->programType->name === Program::DEALER_FINANCING) {
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

      $original_discount =
        ($eligibility / 100) *
        $total_amount *
        ($total_roi / 100) *
        (Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date)) / 365);

      // Tax on discount
      $discount_tax_amount = 0;
      if ($tax_on_discount && $tax_on_discount > 0) {
        $discount_tax_amount = ($tax_on_discount / 100) * $original_discount;
      }

      $discount = $original_discount;
      if ($total_roi > 0) {
        if ($this->program->programType->name === Program::DEALER_FINANCING) {
          $discount = $original_discount;
        } else {
          if ($vendor_discount_details->anchor_discount_bearing > 0) {
            $discount =
              ($vendor_discount_details->anchor_discount_bearing / $total_roi) *
              ($eligibility / 100) *
              $total_amount *
              ($total_roi / 100) *
              (Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date)) / 365);
          } else {
            $discount = $original_discount;
          }
        }
      }

      $anchor_bearing_discount_value = round($original_discount - $discount, 2);

      if ($this->program->programType->name === Program::VENDOR_FINANCING) {
        $amount = $legible_amount - $fees_tax_amount - $discount - $discount_tax_amount - $vendor_bearing_fees;
      }

      $vendor_amount = $amount;

      // Calculate amount to disburse to vendor based on discount type and fee type
      if ($discount_type === self::REAR_ENDED) {
        $vendor_amount = $amount + $discount + $discount_tax_amount;
      }

      if ($fee_type === self::REAR_ENDED) {
        if ($this->program->programType->name === Program::VENDOR_FINANCING) {
          $vendor_amount = $vendor_amount + $fees_tax_amount + $vendor_bearing_fees;
        }
      }

      $payment_request = PaymentRequest::create([
        'reference_number' => $reference_number,
        'invoice_id' => $this->id,
        'amount' => $vendor_amount,
        'processing_fee' => round($fees_amount, 2),
        'payment_request_date' => Carbon::parse($payment_date)->format('Y-m-d'),
        'anchor_discount_bearing' => $anchor_bearing_discount_value,
        'vendor_discount_bearing' => $original_discount - $anchor_bearing_discount_value,
        'anchor_fee_bearing' => $anchor_bearing_fees,
        'vendor_fee_bearing' => $vendor_bearing_fees,
        'created_by' => auth()->id(),
      ]);

      // Credit to vendor's account
      $payment_request->paymentAccounts()->create([
        'account' => $bank_details->account_number,
        'account_name' => $bank_details->name_as_per_bank,
        'amount' => $vendor_amount,
        'type' => 'vendor_account',
        'description' => 'vendor account',
      ]);

      // Credit discount to discount account
      if ($discount > 0) {
        $payment_request->paymentAccounts()->create([
          'account' =>
            $discount_type === self::FRONT_ENDED
              ? ($advanced_discount_bank_account
                ? $advanced_discount_bank_account->value
                : 'Advance Discount Income Account')
              : $unrealized_discount_bank_account->value,
          'account_name' =>
            $discount_type === self::FRONT_ENDED
              ? ($advanced_discount_bank_account
                ? $advanced_discount_bank_account->name
                : 'Advanced Discount Income Account')
              : $unrealized_discount_bank_account->name,
          'amount' => round($discount, 2),
          'type' => 'discount',
          'description' => self::VENDOR_DISCOUNT_BEARING,
        ]);
      }

      if ($anchor_bearing_discount_value > 0) {
        // Credit anchor bearing discount to discount account
        $payment_request->paymentAccounts()->create([
          'account' =>
            $discount_type === self::FRONT_ENDED
              ? ($discount_income_bank_account
                ? $discount_income_bank_account->value
                : 'Advance Discount Income Account')
              : $unrealized_discount_bank_account->value,
          'account_name' =>
            $discount_type === self::FRONT_ENDED
              ? ($discount_income_bank_account
                ? $discount_income_bank_account->name
                : 'Discount Income Account')
              : $unrealized_discount_bank_account->name,
          'amount' => round($anchor_bearing_discount_value, 2),
          'type' => 'discount',
          'description' =>
            $this->program->programType->name === Program::VENDOR_FINANCING &&
            $this->program->programCode->name === Program::VENDOR_FINANCING_RECEIVABLE
              ? self::ANCHOR_DISCOUNT_BEARING
              : self::BUYER_DISCOUNT_BEARING,
        ]);
      }

      // Credit to tax on discount account
      if ($discount_tax_amount > 0) {
        $tax_income_account = BankTaxRate::where('bank_id', $this->program->bank_id)
          ->where('value', $tax_on_discount)
          ->where('status', 'active')
          ->first();
        if ($tax_income_account) {
          $payment_request->paymentAccounts()->create([
            'account' => $tax_income_account->account_no,
            'account_name' => 'Tax Income Bank Account',
            'amount' => round($discount_tax_amount, 2),
            'type' => 'tax_on_discount',
            'description' => 'tax on discount',
          ]);
        } else {
          $payment_request->paymentAccounts()->create([
            'account' => $tax_income_bank_account ? $tax_income_bank_account->value : 'Tax Income Bank Account',
            'account_name' => $tax_income_bank_account ? $tax_income_bank_account->name : 'Tax Income Bank Account',
            'amount' => round($discount_tax_amount, 2),
            'type' => 'tax_on_discount',
            'description' => 'tax on discount',
          ]);
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

          if ($fee->type == 'amount') {
            // Dealer Financing
            if ($this->program->programType->name === Program::DEALER_FINANCING) {
              $anchor_bearing_fees += 0;
              $vendor_bearing_fees =
                $fee->charge_type === 'fixed'
                  ? $fee->value
                  : $fee->value * Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date));
              if ($vendor_bearing_fees > 0) {
                $payment_request->paymentAccounts()->create([
                  'account' => $fee_account,
                  'account_name' => $fee_account_name,
                  'amount' => round($vendor_bearing_fees, 2),
                  'type' => 'program_fees',
                  'description' => self::VENDOR_FEE_BEARING,
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
                    Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date));
              if ($anchor_bearing_fees > 0) {
                $payment_request->paymentAccounts()->create([
                  'account' => $fee_account,
                  'account_name' => $fee_account_name,
                  'amount' => round($anchor_bearing_fees, 2),
                  'type' => 'program_fees',
                  'description' =>
                    $this->program->programType->name == Program::VENDOR_FINANCING &&
                    $this->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE
                      ? self::ANCHOR_FEE_BEARING
                      : self::BUYER_FEE_BEARING,
                  'title' => $fee->fee_name,
                ]);
              }
              $vendor_bearing_fees =
                $fee->charge_type === 'fixed'
                  ? ($fee->vendor_bearing_discount / 100) * $fee->value
                  : ($fee->vendor_bearing_discount / 100) *
                    $fee->value *
                    Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date));
              if ($vendor_bearing_fees > 0) {
                $payment_request->paymentAccounts()->create([
                  'account' => $fee_account,
                  'account_name' => $fee_account_name,
                  'amount' => round($vendor_bearing_fees, 2),
                  'type' => 'program_fees',
                  'description' => self::VENDOR_FEE_BEARING,
                  'title' => $fee->fee_name,
                ]);
              }
            }
          }

          if ($fee->type === 'percentage') {
            // Dealer Financing
            if ($this->program->programType->name == Program::DEALER_FINANCING) {
              $anchor_bearing_fees += 0;
              $vendor_bearing_fees =
                $fee->charge_type === 'fixed'
                  ? round(($fee->value / 100) * $legible_amount, 2)
                  : round(
                    ($fee->value / 100) *
                      $legible_amount *
                      Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date)),
                    2
                  );
              if ($vendor_bearing_fees > 0) {
                $payment_request->paymentAccounts()->create([
                  'account' => $fee_account,
                  'account_name' => $fee_account_name,
                  'amount' => round($vendor_bearing_fees, 2),
                  'type' => 'program_fees',
                  'description' => self::VENDOR_FEE_BEARING,
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
                        Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date))),
                    2
                  );
              if ($anchor_bearing_fees > 0) {
                $payment_request->paymentAccounts()->create([
                  'account' => $fee_account,
                  'account_name' => $fee_account_name,
                  'amount' => round($anchor_bearing_fees, 2),
                  'type' => 'program_fees',
                  'description' =>
                    $this->program->programType->name == Program::VENDOR_FINANCING &&
                    $this->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE
                      ? self::ANCHOR_FEE_BEARING
                      : self::BUYER_FEE_BEARING,
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
                        Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date))),
                    2
                  );
              if ($vendor_bearing_fees > 0) {
                $payment_request->paymentAccounts()->create([
                  'account' => $fee_account,
                  'account_name' => $fee_account_name,
                  'amount' => round($vendor_bearing_fees, 2),
                  'type' => 'program_fees',
                  'description' => self::VENDOR_FEE_BEARING,
                  'title' => $fee->fee_name,
                ]);
              }
            }
          }

          if ($fee->type === 'per amount') {
            // Dealer Financing
            if ($this->program->programType->name == Program::DEALER_FINANCING) {
              $vendor_bearing_fees =
                $fee->charge_type === 'fixed'
                  ? round(floor($legible_amount / $fee->per_amount) * $fee->value, 2)
                  : round(
                    floor($legible_amount / $fee->per_amount) *
                      $fee->value *
                      Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date)),
                    2
                  );
              if ($vendor_bearing_fees > 0) {
                $payment_request->paymentAccounts()->create([
                  'account' => $fee_account,
                  'account_name' => $fee_account_name,
                  'amount' => round($vendor_bearing_fees, 2),
                  'type' => 'program_fees',
                  'description' => self::VENDOR_FEE_BEARING,
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
                        Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date))),
                    2
                  );

              if ($anchor_bearing_fees > 0) {
                $payment_request->paymentAccounts()->create([
                  'account' => $fee_account,
                  'account_name' => $fee_account_name,
                  'amount' => round($anchor_bearing_fees, 2),
                  'type' => 'program_fees',
                  'description' =>
                    $this->program->programType->name == Program::VENDOR_FINANCING &&
                    $this->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE
                      ? self::ANCHOR_FEE_BEARING
                      : self::BUYER_FEE_BEARING,
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
                        Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date))),
                    2
                  );

              if ($vendor_bearing_fees > 0) {
                $payment_request->paymentAccounts()->create([
                  'account' => $fee_account,
                  'account_name' => $fee_account_name,
                  'amount' => round($vendor_bearing_fees, 2),
                  'type' => 'program_fees',
                  'description' => self::VENDOR_FEE_BEARING,
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
            if ($fee->type === 'amount') {
              if ($fee->taxes) {
                $fees_tax_amount =
                  $fee->charge_type === 'fixed'
                    ? round(($fee->taxes / 100) * $fee->value, 2)
                    : round(
                      ($fee->taxes / 100) *
                        $fee->value *
                        Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date)),
                      2
                    );
              }
            }

            if ($fee->type === 'percentage') {
              if ($fee->taxes) {
                $fees_tax_amount =
                  $fee->charge_type === 'fixed'
                    ? round(($fee->taxes / 100) * (($fee->value / 100) * $legible_amount), 2)
                    : round(
                      ($fee->taxes / 100) *
                        (($fee->value / 100) * $legible_amount) *
                        Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date)),
                      2
                    );
              }
            }

            if ($fee->type === 'per amount') {
              if ($fee->taxes) {
                $fees_tax_amount =
                  $fee->charge_type === 'fixed'
                    ? round(($fee->taxes / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value), 2)
                    : round(
                      ($fee->taxes / 100) *
                        (floor($legible_amount / $fee->per_amount) *
                          $fee->value *
                          Carbon::parse($payment_date)->diffInDays(Carbon::parse($this->due_date))),
                      2
                    );
              }
            }

            $tax_income_account = BankTaxRate::where('bank_id', $this->program->bank_id)
              ->where('value', $fee->taxes)
              ->where('status', 'active')
              ->first();

            if ($tax_income_account) {
              $payment_request->paymentAccounts()->create([
                'account' => $tax_income_account->account_no,
                'account_name' => 'Tax Income Bank Account',
                'amount' => round($fees_tax_amount, 2),
                'type' => 'tax_on_fees',
                'description' => 'Tax on Fees for ' . $fee->fee_name,
              ]);
            } else {
              $payment_request->paymentAccounts()->create([
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
      // END FRONT ENDED

      $invoice_setting = $this->company->invoiceSetting;

      // Days before penal
      $days_before_penal = BankGeneralProductConfiguration::where('bank_id', $this->program->bank_id)
        ->where('name', 'days before penal')
        ->where('product_type_id', $this->program->program_type_id)
        ->first();

      $this->update([
        'eligible_for_financing' => false,
        'financing_status' => 'submitted',
        'discount_charge_type' => $discount_type,
        'fee_charge_type' => $fee_type,
        'eligibility' => $vendor_configurations->eligibility,
        'penal_rate' => $vendor_discount_details->penal_discount_on_principle,
        'grace_period' => $vendor_discount_details->grace_period,
        'grace_period_discount' => $vendor_discount_details->grace_period_discount,
        'days_before_penal' => $days_before_penal ? $days_before_penal->value : 0,
        'discount_rate' => $vendor_configurations->total_toi,
      ]);

      InvoiceProcessing::where('invoice_id', $this->id)
        ->first()
        ?->update([
          'status' => 'processed',
        ]);

      // Update Program and Company Pipeline and Utilized Amounts
      $this->company->increment(
        'pipeline_amount',
        ($vendor_configurations->eligibility / 100) * $this->invoice_total_amount
      );

      $this->program->increment(
        'pipeline_amount',
        ($vendor_configurations->eligibility / 100) * $this->invoice_total_amount
      );

      $vendor_configurations->increment(
        'pipeline_amount',
        ($vendor_configurations->eligibility / 100) * $this->invoice_total_amount
      );

      if ($invoice_setting->request_financing_maker_checker) {
        FinanceRequestApproval::create([
          'payment_request_id' => $payment_request->id,
          'user_id' => auth()->id(),
        ]);
      } else {
        // $this->program->bank->notify(new PaymentRequestNotification($payment_request));

        activity($this->program->bank->id)
          ->causedBy(auth()->user())
          ->performedOn($payment_request)
          ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Vendor'])
          ->log('requested financing');

        // Check if auto approve finance requests is enabled
        if ($vendor_configurations->auto_approve_finance) {
          $payment_request->update([
            'status' => 'approved',
          ]);

          // Create CBS Transactions for the payment request
          $payment_request->createCbsTransactions();
        }
      }

      return ['status' => 'success', 'payment_request_id' => $payment_request->id];
    } else {
      if ($date->diffInDays(Carbon::parse($this->due_date)) > $max_financing_period) {
        // Remove from invoice processing
        InvoiceProcessing::where('invoice_id', $this->id)->delete();
        return ['status' => 'failed', 'message' => 'Invoice cannot be financed beyond the maximum financing period.'];
      } else {
        // If payment request already exists, return the existing payment request
        $payment_request = PaymentRequest::where('invoice_id', $this->id)->first();
        return ['status' => 'success', 'payment_request_id' => $payment_request];
      }
    }
  }

  public function requestDealerFinance(
    ProgramVendorConfiguration $vendor_configuration,
    $drawdown_amount,
    ProgramBankDetails $program_bank_details,
    $due_date,
    $payment_date = null
  ) {
    if ($this->paymentRequests->count() == 0) {
      if (!$payment_date) {
        $payment_date = now();
      }

      // Get difference in days between anchor payment and repayment date
      $diff = Carbon::parse($payment_date)->diffInDays(Carbon::parse($due_date));

      // Get vendor discount details
      $vendor_discount_details = ProgramVendorDiscount::where('company_id', $this->company_id)
        ->where('program_id', $this->program_id)
        ->where('from_day', '<=', $diff)
        ->where('to_day', '>=', $diff)
        ->latest()
        ->first();

      if (!$vendor_discount_details) {
        $vendor_discount_details = ProgramVendorDiscount::where('company_id', $this->company_id)
          ->where('program_id', $this->program_id)
          ->latest()
          ->first();
        $max_days = $vendor_discount_details->to_day;
        $this->update([
          'due_date' => now()
            ->addDays($max_days)
            ->format('Y-m-d'),
        ]);
      }

      $discount_type = $this->program->discountDetails->first()?->discount_type;
      $fee_type = $this->program->discountDetails->first()?->fee_type;

      if (!$discount_type) {
        $discount_type = self::FRONT_ENDED;
      }

      if (!$fee_type) {
        $fee_type = self::FRONT_ENDED;
      }

      // Get fees for vendor
      $vendor_fees = ProgramVendorFee::where('company_id', $this->company_id)
        ->where('program_id', $this->program_id)
        ->get();
      // Get Tax on Discount Value
      $tax_on_discount = ProgramDiscount::where('program_id', $this->program_id)->first()?->tax_on_discount;

      $eligibility = $vendor_configuration->eligibility;
      $total_amount = $this->drawdown_amount;

      $total_roi = $vendor_discount_details ? $vendor_discount_details->total_roi : 0;
      $legible_amount = ($eligibility / 100) * $total_amount;

      // Fee charges
      $fees_amount = 0;
      $fees_tax_amount = 0;
      if ($vendor_fees->count() > 0) {
        foreach ($vendor_fees as $fee) {
          if ($fee->type === 'amount') {
            $fees_amount += $fee->value;
          }
          if ($fee->type === 'percentage') {
            $fees_amount += ($fee->value / 100) * $legible_amount;
          }
          if ($fee->type === 'per amount') {
            $amounts = floor($legible_amount / $fee->per_amount);
            $fees_amount += $amounts * $fee->value;
          }
          if ($fee->taxes) {
            $fees_tax_amount += ($fee->taxes / 100) * $fees_amount;
          }
        }
      }

      $discount =
        ($eligibility / 100) *
        Str::replace(',', '', $drawdown_amount) *
        ($total_roi / 100) *
        (Carbon::parse($payment_date)->diffInDays(Carbon::parse($due_date)) / 365);

      // Tax on discount
      $discount_tax_amount = 0;
      if ($discount > 0 && $tax_on_discount && $tax_on_discount > 0) {
        $discount_tax_amount = ($tax_on_discount / 100) * $discount;
      }

      if ($this->drawdown_amount) {
        $amount = $this->drawdown_amount - $fees_amount - $discount - $fees_tax_amount - $discount_tax_amount;
      } else {
        $amount = $this->total_amount - $fees_amount - $discount - $fees_tax_amount - $discount_tax_amount;
      }

      $vendor_amount = $amount;

      // Calculate amount to disburse to vendor based on discount type and fee type
      if ($discount_type == Invoice::REAR_ENDED) {
        $vendor_amount = $amount + $discount + $discount_tax_amount;
      }

      if ($fee_type == Invoice::REAR_ENDED) {
        $vendor_amount = $vendor_amount + $fees_tax_amount + $fees_amount;
      }

      $reference_number = '';

      $words = explode(' ', $this->company->name);
      $acronym = '';

      foreach ($words as $w) {
        $acronym .= mb_substr($w, 0, 1);
      }

      if ($this->program->programType->name == Program::VENDOR_FINANCING) {
        if ($this->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
          $reference_number = 'VFR' . $this->program->bank_id . '' . $acronym . '000' . $this->id;
        } else {
          $reference_number = 'FR' . $this->program->bank_id . '' . $acronym . '000' . $this->id;
        }
      } else {
        $reference_number = 'DF' . $this->program->bank_id . '' . $acronym . '000' . $this->id;
      }

      $payment_request = PaymentRequest::create([
        'reference_number' => $reference_number,
        'invoice_id' => $this->id,
        'amount' => round($vendor_amount, 2),
        'processing_fee' => round($fees_amount, 2),
        'payment_request_date' => Carbon::parse($payment_date)->format('Y-m-d'),
        'created_by' => auth()->id(),
      ]);

      $dealer_financing = ProgramType::where('name', Program::DEALER_FINANCING)->first();

      $discount_income_bank_account = null;
      $fees_income_bank_account = null;
      $tax_income_bank_account = null;

      // Get Bank Configured Receivable Accounts
      $discount_income_bank_account = BankProductsConfiguration::where(
        'bank_id',
        $payment_request->invoice->program->bank->id
      )
        ->where('product_type_id', $dealer_financing->id)
        ->where('product_code_id', null)
        ->where('name', 'Discount Income Account')
        ->first();
      $unrealized_discount_bank_account = BankProductsConfiguration::where(
        'bank_id',
        $payment_request->invoice->program->bank->id
      )
        ->where('product_type_id', $dealer_financing->id)
        ->where('product_code_id', null)
        ->where('name', 'Unrealised Discount Account')
        ->first();
      $fees_income_bank_account = BankProductsConfiguration::where(
        'bank_id',
        $payment_request->invoice->program->bank->id
      )
        ->where('product_type_id', $dealer_financing->id)
        ->where('product_code_id', null)
        ->where('name', 'Fee Income Account')
        ->first();
      $tax_income_bank_account = BankProductsConfiguration::where(
        'bank_id',
        $payment_request->invoice->program->bank->id
      )
        ->where('product_type_id', $dealer_financing->id)
        ->where('product_code_id', null)
        ->where('name', 'Tax Account Number')
        ->first();

      // Credit to vendor's account
      $payment_request->paymentAccounts()->create([
        'account' => $program_bank_details->account_number,
        'account_name' => $program_bank_details->name_as_per_bank,
        'amount' => round($vendor_amount, 2),
        'type' => 'vendor_account',
        'description' => 'Vendor Account',
      ]);

      // Credit discount to discount account
      if ($discount > 0) {
        $payment_request->paymentAccounts()->create([
          'account' =>
            $discount_type == self::FRONT_ENDED
              ? ($discount_income_bank_account
                ? $discount_income_bank_account->value
                : 'Adv_Disc_Inc_Acc')
              : $unrealized_discount_bank_account->value,
          'account_name' =>
            $discount_type == self::FRONT_ENDED
              ? ($discount_income_bank_account
                ? $discount_income_bank_account->name
                : 'Advanced Discount Account')
              : $unrealized_discount_bank_account->name,
          'amount' => round($discount, 2),
          'type' => 'discount',
          'description' => self::DEALER_DISCOUNT_BEARING,
        ]);
      }

      // Credit Fees to Fees Income Account
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

          if ($fee->type == 'amount') {
            $fees_amount = $fee->value;
            if ($fees_amount > 0) {
              $payment_request->paymentAccounts()->create([
                'account' => $fee_account,
                'account_name' => $fee_account_name,
                'amount' => round($fees_amount, 2),
                'type' => 'program_fees',
                'title' => $fee->fee_name,
                'description' => 'Fees for ' . $fee->fee_name,
              ]);
            }
          }
          if ($fee->type == 'percentage') {
            $fees_amount = ($fee->value / 100) * $legible_amount;
            if ($fees_amount > 0) {
              $payment_request->paymentAccounts()->create([
                'account' => $fee_account,
                'account_name' => $fee_account_name,
                'amount' => round($fees_amount, 2),
                'type' => 'program_fees',
                'title' => $fee->fee_name,
                'description' => 'Fees for ' . $fee->fee_name,
              ]);
            }
          }
          if ($fee->type == 'per amount') {
            $amounts = floor($legible_amount / $fee->per_amount);
            $fees_amount = $amounts * $fee->value;
            if ($fees_amount > 0) {
              $payment_request->paymentAccounts()->create([
                'account' => $fee_account,
                'account_name' => $fee_account_name,
                'amount' => round($fees_amount, 2),
                'type' => 'program_fees',
                'title' => $fee->fee_name,
                'description' => 'Fees for ' . $fee->fee_name,
              ]);
            }
          }
        }
      }

      // Credit Fee Taxes to Fees taxes Account
      $fees_amount = 0;
      $fees_tax_amount = 0;
      if ($vendor_fees->count() > 0) {
        foreach ($vendor_fees as $fee) {
          if ($fee->type == 'amount') {
            $fees_amount += $fee->value;
          }
          if ($fee->type == 'percentage') {
            $fees_amount += ($fee->value / 100) * $legible_amount;
          }
          if ($fee->type == 'per amount') {
            $amounts = floor($legible_amount / $fee->per_amount);
            $fees_amount += $amounts * $fee->value;
          }

          if ($fee->taxes) {
            $fees_tax_amount += ($fee->taxes / 100) * $fees_amount;

            $tax_income_account = BankTaxRate::where('bank_id', $this->program->bank_id)
              ->where('value', $fee->taxes)
              ->where('status', 'active')
              ->first();

            if ($tax_income_account) {
              $payment_request->paymentAccounts()->create([
                'account' => $tax_income_account->account_no,
                'account_name' => 'Tax Income Bank Account',
                'amount' => round($fees_tax_amount, 2),
                'type' => 'tax_on_fees',
                'description' => 'Tax on Fees for ' . $fee->fee_name,
              ]);
            } else {
              $payment_request->paymentAccounts()->create([
                'account' => $tax_income_bank_account ? $tax_income_bank_account->value : 'Tax_Inc_Acc',
                'account_name' => $tax_income_bank_account ? $tax_income_bank_account->name : 'Tax Income Account',
                'amount' => round($fees_tax_amount, 2),
                'type' => 'tax_on_fees',
                'description' => 'Tax on Fees for ' . $fee->fee_name,
              ]);
            }
          }
        }
      }

      // Credit to tax on discount account
      if ($tax_on_discount > 0) {
        if ($discount_tax_amount > 0) {
          if ($tax_income_bank_account) {
            $payment_request->paymentAccounts()->create([
              'account' => $tax_income_bank_account->value,
              'account_name' => $tax_income_bank_account->name,
              'amount' => round($discount_tax_amount, 2),
              'type' => 'tax_on_discount',
              'description' => 'Tax on Discount',
            ]);
          } else {
            $payment_request->paymentAccounts()->create([
              'account' => $fees_income_bank_account ? $fees_income_bank_account->value : 'Fees Income Bank Account',
              'account_name' => $fees_income_bank_account
                ? $fees_income_bank_account->name
                : 'Fees Income Bank Account',
              'amount' => round($discount_tax_amount, 2),
              'type' => 'tax_on_discount',
              'description' => 'Tax on Discount',
            ]);
          }
        }
      }

      // Check if requires checker approval
      $purchase_order_setting = $this->company->purchaseOrderSetting;

      // Days before penal
      $days_before_penal = BankGeneralProductConfiguration::where('bank_id', $this->program->bank_id)
        ->where('name', 'days before penal')
        ->where('product_type_id', $this->program->program_type_id)
        ->first();

      $this->update([
        'eligible_for_financing' => false,
        'financing_status' => 'submitted',
        'calculated_total_amount' => $drawdown_amount,
        'discount_charge_type' => $discount_type,
        'fee_charge_type' => $fee_type,
        'eligibility' => $vendor_configuration->eligibility,
        'penal_rate' => $vendor_discount_details->penal_discount_on_principle,
        'grace_period' => $vendor_discount_details->grace_period,
        'grace_period_discount' => $vendor_discount_details->grace_period_discount,
        'days_before_penal' => $days_before_penal ? $days_before_penal->value : 0,
        'discount_rate' => $vendor_configuration->total_toi,
      ]);

      // Update Program and Company Pipeline and Utilized Amounts
      $this->company->increment('pipeline_amount', $drawdown_amount);

      $this->program->increment('pipeline_amount', $drawdown_amount);

      $vendor_configuration->increment('pipeline_amount', $drawdown_amount);

      if ($purchase_order_setting->request_finance_add_repayment) {
        FinanceRequestApproval::create([
          'payment_request_id' => $payment_request->id,
          'user_id' => auth()->id(),
        ]);
      } else {
        // Check if auto approve finance requests is enabled
        if ($vendor_configuration->auto_approve_finance) {
          $payment_request->update([
            'status' => 'approved',
          ]);

          // Create CBS Transactions for the payment request
          $payment_request->createCbsTransactions();
        }

        // $invoice->program->bank->notify(new PaymentRequestNotification($payment_request));

        activity($this->program->bank->id)
          ->causedBy(auth()->user())
          ->performedOn($payment_request)
          ->withProperties(['ip' => request()->ip(), 'device_info' => request()->userAgent(), 'user_type' => 'Dealer'])
          ->log('initiated drawdown');

        $noa_text = NoaTemplate::where('product_type', 'dealer_financing')
          ->where('status', 'active')
          ->where('bank_id', $this->program->bank_id)
          ->first();

        if (!$noa_text) {
          $noa_text = NoaTemplate::where('product_type', 'generic')
            ->where('status', 'active')
            ->first();
        }

        // Send NOA
        $data = [];
        $data['{date}'] = Carbon::parse($this->invoice_date)->format('d M Y');
        $data['{buyerName}'] = $this->company->name;
        $data['{anchorName}'] = $this->program->anchor->name;
        $data['{company}'] = $this->company->name;
        $data['{anchorCompanyUniqueID}'] = $this->program->anchor->unique_identification_number;
        $data['{time}'] = now()->format('d M Y');
        $data['{agreementDate}'] = now()->format('d M Y');
        $data['{contract}'] = '';
        $data['{anchorAccountName}'] = $this->program->bankDetails->first()->account_name;
        $data['{anchorAccountNumber}'] = $this->program->bankDetails->first()->account_number;
        $data['{anchorCustomerId}'] = '';
        $data['{anchorBranch}'] = $this->program->anchor->branch_code;
        $data['{anchorIFSCCode}'] = '';
        $data['{anchorAddress}'] =
          $this->program->anchor->postal_code .
          ' ' .
          $this->program->anchor->address .
          ' ' .
          $this->program->anchor->city .
          ' ';
        $data['{penalnterestRate}'] = $vendor_discount_details?->penal_discount_on_principle;
        $data['{sellerName}'] = $this->company->name;

        $noa = '';

        // // Notify Bank of new payment request
        // foreach ($payment_request->invoice->program->bank->users as $bank_user) {
        //   if ($noa_text != null) {
        //     $noa = $noa_text->body;
        //     foreach ($data as $key => $val) {
        //       $noa = str_replace($key, $val, $noa);
        //     }

        //     $pdf = Pdf::loadView('pdf.noa', [
        //       'data' => $noa,
        //     ])->setPaper('a4', 'landscape');
        //   }

        //   SendMail::dispatchAfterResponse($bank_user->email, 'PaymentRequested', [
        //     'payment_request_id' => $payment_request->id,
        //     'link' => config('app.url') . '/' . $payment_request->invoice->program->bank->url,
        //     'type' => 'dealer_financing',
        //     'noa' => $noa_text != null ? $pdf->output() : null,
        //   ]);
        // }

        // foreach ($this->program->bank->users as $bank_user) {
        //   SendMail::dispatchAfterResponse($bank_user->email, 'PaymentRequested', [
        //     'payment_request_id' => $payment_request->id,
        //     'link' => config('app.url'),
        //     'type' =>
        //       $this->program->programType->name == Program::VENDOR_FINANCING &&
        //       $this->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE
        //         ? 'vendor_financing'
        //         : 'factoring',
        //     'noa' => $noa_text != null ? $noa : null,
        //   ]);
        // }

        // if ($this->program->programType->name == Program::VENDOR_FINANCING) {
        //   if ($this->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        //     foreach ($this->program->anchor->users as $user) {
        //       SendMail::dispatchAfterResponse($user->email, 'PaymentRequested', [
        //         'payment_request_id' => $payment_request->id,
        //         'link' => config('app.url'),
        //         'type' => 'vendor_financing',
        //         'noa' => $noa_text != null ? $noa : null,
        //       ]);
        //     }
        //   } else {
        //     if ($this->buyer && $this->buyer->users->count() > 0) {
        //       foreach ($this->buyer->users as $user) {
        //         SendMail::dispatchAfterResponse($user->email, 'PaymentRequested', [
        //           'payment_request_id' => $payment_request->id,
        //           'link' => config('app.url'),
        //           'type' => 'factoring',
        //           'noa' => $noa_text != null ? $noa : null,
        //         ]);
        //       }
        //     }
        //   }
        // } else {
        //   foreach ($this->program->anchor->users as $user) {
        //     SendMail::dispatchAfterResponse($user->email, 'PaymentRequested', [
        //       'payment_request_id' => $payment_request->id,
        //       'link' => config('app.url'),
        //       'type' => 'vendor_financing',
        //       'noa' => $noa_text != null ? $noa : null,
        //     ]);
        //   }
        // }
      }

      return ['status' => 'success', 'payment_request_id' => $payment_request->id];
    } else {
      $payment_request = PaymentRequest::where('invoice_id', $this->id)->first();
      return ['status' => 'success', 'payment_request_id' => $payment_request];
    }
  }

  public function getDiscountAttribute()
  {
    $amount = 0;

    $amount = PaymentRequestAccount::whereHas('paymentRequest', function ($q) {
      $q->where('invoice_id', $this->id);
    })
      // ->whereDate('created_at', '<', Carbon::parse($this->due_date))
      ->where('type', 'discount')
      ->sum('amount');

    return round($amount, 2);
  }

  public function getProgramFeesAttribute()
  {
    $amount = 0;

    $amount = PaymentRequestAccount::whereHas('paymentRequest', function ($q) {
      $q->where('invoice_id', $this->id);
    })
      // ->whereDate('created_at', '<', Carbon::parse($this->due_date))
      ->whereIn('type', ['program_fees', 'program_fees_taxes', 'tax_on_fees'])
      ->sum('amount');

    return round($amount, 2);
  }

  /**
   * Get the anchor bearing fees
   *
   * @param  string  $value
   * @return string
   */
  public function getVendorBearingFeesAttribute()
  {
    if ($this->program->programType->name == Program::VENDOR_FINANCING) {
      if ($this->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        $vendor_fee_details = ProgramVendorFee::select(
          'vendor_bearing_discount',
          'anchor_bearing_discount',
          'dealer_bearing'
        )
          ->where('company_id', $this->company_id)
          ->where('program_id', $this->program_id)
          ->first();
      } else {
        $vendor_fee_details = ProgramVendorFee::select(
          'vendor_bearing_discount',
          'anchor_bearing_discount',
          'dealer_bearing'
        )
          ->where('buyer_id', $this->buyer_id)
          ->where('program_id', $this->program_id)
          ->first();
      }
    } else {
      $vendor_fee_details = ProgramVendorFee::select(
        'dealer_bearing',
        'vendor_bearing_discount',
        'anchor_bearing_discount'
      )
        ->where('company_id', $this->company_id)
        ->where('program_id', $this->program_id)
        ->first();
    }

    if ($this->program->programType->name == Program::DEALER_FINANCING) {
      return $vendor_fee_details->dealer_bearing && $this->program_fees
        ? round(($vendor_fee_details->dealer_bearing / 100) * $this->program_fees, 2)
        : 0;
    }

    return $vendor_fee_details->vendor_discount_bearing && $this->program_fees
      ? round(($vendor_fee_details->vendor_discount_bearing / 100) * $this->program_fees, 2)
      : 0;
  }

  /**
   * Get the anchor bearing discount
   *
   * @param  string  $value
   * @return string
   */
  public function getVendorBearingDiscountAttribute()
  {
    if ($this->program->programType->name == 'Vendor Financing') {
      if ($this->program->programCode->name == 'Vendor Financing Receivable') {
        $vendor_discount_details = ProgramVendorDiscount::select(
          'total_roi',
          'vendor_discount_bearing',
          'anchor_discount_bearing'
        )
          ->where('company_id', $this->company_id)
          ->where('program_id', $this->program_id)
          ->first();
      } else {
        $vendor_discount_details = ProgramVendorDiscount::select(
          'total_roi',
          'vendor_discount_bearing',
          'anchor_discount_bearing'
        )
          ->where('buyer_id', $this->buyer_id)
          ->where('program_id', $this->program_id)
          ->first();
      }
    } else {
      $vendor_discount_details = ProgramVendorDiscount::select(
        'total_roi',
        'vendor_discount_bearing',
        'anchor_discount_bearing'
      )
        ->where('company_id', $this->company_id)
        ->where('program_id', $this->program_id)
        ->first();
    }

    return $vendor_discount_details->vendor_bearing_discount && $this->discount > 0
      ? round(($vendor_discount_details->vendor_bearing_discount / 100) * $this->discount, 2)
      : 0;
  }

  /**
   * Get the anchor bearing fees
   *
   * @param  string  $value
   * @return string
   */
  public function getAnchorBearingFeesAttribute()
  {
    if ($this->program->programType->name == 'Vendor Financing') {
      if ($this->program->programCode->name == 'Vendor Financing Receivable') {
        $vendor_fee_details = ProgramVendorFee::select(
          'vendor_bearing_discount',
          'anchor_bearing_discount',
          'dealer_bearing'
        )
          ->where('company_id', $this->company_id)
          ->where('program_id', $this->program_id)
          ->first();
      } else {
        $vendor_fee_details = ProgramVendorFee::select(
          'vendor_bearing_discount',
          'anchor_bearing_discount',
          'dealer_bearing'
        )
          ->where('buyer_id', $this->buyer_id)
          ->where('program_id', $this->program_id)
          ->first();
      }
    } else {
      $vendor_fee_details = ProgramVendorFee::select(
        'vendor_bearing_discount',
        'anchor_bearing_discount',
        'dealer_bearing'
      )
        ->where('company_id', $this->company_id)
        ->where('program_id', $this->program_id)
        ->first();
    }

    if ($this->program->programType->name == 'Dealer Financing') {
      return $vendor_fee_details->dealer_bearing && $this->program_fees
        ? round(($vendor_fee_details->dealer_bearing / 100) * $this->program_fees, 2)
        : 0;
    }

    return $vendor_fee_details->anchor_bearing_discount && $this->program_fees
      ? round(($vendor_fee_details->anchor_bearing_discount / 100) * $this->program_fees, 2)
      : 0;
  }

  /**
   * Get the anchor bearing discount
   *
   * @param  string  $value
   * @return string
   */
  public function getAnchorBearingDiscountAttribute()
  {
    if ($this->program->programType->name == 'Vendor Financing') {
      if ($this->program->programCode->name == 'Vendor Financing Receivable') {
        $vendor_discount_details = ProgramVendorDiscount::select(
          'total_roi',
          'vendor_discount_bearing',
          'anchor_discount_bearing'
        )
          ->where('company_id', $this->company_id)
          ->where('program_id', $this->program_id)
          ->first();
      } else {
        $vendor_discount_details = ProgramVendorDiscount::select(
          'total_roi',
          'vendor_discount_bearing',
          'anchor_discount_bearing'
        )
          ->where('buyer_id', $this->buyer_id)
          ->where('program_id', $this->program_id)
          ->first();
      }
    } else {
      $vendor_discount_details = ProgramVendorDiscount::select(
        'total_roi',
        'vendor_discount_bearing',
        'anchor_discount_bearing'
      )
        ->where('company_id', $this->company_id)
        ->where('program_id', $this->program_id)
        ->first();
    }

    return $vendor_discount_details->anchor_discount_bearing && $this->discount > 0
      ? round(($vendor_discount_details->anchor_discount_bearing / 100) * $this->discount, 2)
      : 0;
  }

  /**
   * Get the can request financing today based on program min and max financing days
   *
   * @return boolean
   */
  public function getCanRequestTodayAttribute()
  {
    if (
      $this->status == 'approved' &&
      $this->eligible_for_financing &&
      Carbon::parse($this->due_date)->greaterThanOrEqualTo(now()->format('Y-m-d'))
    ) {
      $max_financing_days = $this->program->max_financing_days;
      $min_date = '';

      if ($max_financing_days > 0) {
        $min_date = Carbon::parse($this->due_date)->subDays($max_financing_days);

        if (now()->greaterThanOrEqualTo($min_date)) {
          return true;
        }

        return false;
      }
    }

    return false;
  }

  /**
   * Get the window to request financing
   * @return string
   */
  public function getFinancingRequestWindowAttribute()
  {
    if ($this->eligible_for_financing && $this->status == 'approved') {
      $min_financing_days = $this->program->min_financing_days;
      $max_financing_days = $this->program->max_financing_days;
      $max_date = '';
      $min_date = '';

      $max_date = Carbon::parse($this->due_date)
        ->subDays($min_financing_days)
        ->format('d M Y');

      if ($max_financing_days > 0) {
        $min_date = Carbon::parse($this->due_date)
          ->subDays($max_financing_days)
          ->format('d M Y');

        if (now()->greaterThan($min_date)) {
          $min_date = now()->format('d M Y');
        }
      }

      return 'You can request financing between ' . $min_date . ' and ' . $max_date . '.';
    }
  }

  public function canRequestFinancing(): bool
  {
    if (!$this->eligible_for_financing) {
      return false;
    }

    if ($this->company->is_blocked) {
      return false;
    }

    $program = Program::find($this->program_id);

    // Check if program is active
    if ($program->account_status == 'suspended') {
      // Notify user
      return false;
    }

    // Get Anchor Configurations
    if ($this->program->programType->name == Program::DEALER_FINANCING) {
      $vendor_configurations = ProgramVendorConfiguration::where('company_id', $this->company->id)
        ->where('program_id', $this->program_id)
        ->first();
    } else {
      if ($this->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE) {
        $vendor_configurations = ProgramVendorConfiguration::where('company_id', $this->company->id)
          ->where('program_id', $this->program_id)
          ->first();
      } else {
        $vendor_configurations = ProgramVendorConfiguration::where('company_id', $this->company->id)
          ->where('program_id', $this->program_id)
          ->where('buyer_id', $this->buyer_id)
          ->first();
      }
    }

    // Check if company can make the request on the program
    if (!$vendor_configurations->is_approved || $vendor_configurations->status == 'inactive') {
      // Notify bank of request to unblock
      foreach ($this->company->bank->users as $user) {
        SendMail::dispatchAfterResponse($user->email, 'RequestToUnblock', ['company_id' => $this->company->id]);
      }

      // Notify user
      return false;
    }

    $invoice_total_amount = $this->invoice_total_amount;

    // Check limits at OD Level
    $sanctioned_limit = $vendor_configurations->sanctioned_limit;
    $utilized_amount = $vendor_configurations->utilized_amount;
    $pipeline_amount = $vendor_configurations->pipeline_amount;

    // Get Retain Limit as set in Bank Configuration
    $bank_configurations = BankGeneralProductConfiguration::where('bank_id', $this->program->bank_id)
      ->where('product_type_id', $this->program->program_type_id)
      ->where('name', 'retain limit')
      ->first();

    if ($bank_configurations->value > 0) {
      $retain_amount = ($bank_configurations->value / 100) * $sanctioned_limit;
      $remainder = $sanctioned_limit - $retain_amount;
      $potential_utilization_amount = $utilized_amount + $pipeline_amount + $this->invoice_total_amount;
      if ($potential_utilization_amount > $remainder) {
        return false;
      }
    }

    $available_limit = $sanctioned_limit - $utilized_amount - $pipeline_amount - $invoice_total_amount;
    if ($available_limit <= 0) {
      // Notify bank of request to unblock
      foreach ($this->company->bank->users as $user) {
        SendMail::dispatchAfterResponse($user->email, 'RequestToIncreaseFundingLimit', [
          'company_id' => $this->company->id,
          'approved_limit' => $vendor_configurations->sanctioned_limit,
          'current_exposure' => $utilized_amount,
          'pipeline_requests' => $pipeline_amount,
          'available_limit' => $sanctioned_limit - $utilized_amount - $pipeline_amount,
        ]);
      }

      return false;
    }

    // Check at program level
    $program_limit = $program->program_limit;
    $utilized_amount = $program->utilized_amount;
    $pipeline_amount = $program->pipeline_amount;
    $available_limit = $program_limit - $utilized_amount - $pipeline_amount - $invoice_total_amount;
    if ($available_limit <= 0) {
      // Notify bank of request to unblock
      foreach ($this->company->bank->users as $user) {
        SendMail::dispatchAfterResponse($user->email, 'RequestToIncreaseFundingLimit', [
          'company_id' => $this->company->id,
          'approved_limit' => $vendor_configurations->sanctioned_limit,
          'current_exposure' => $utilized_amount,
          'pipeline_requests' => $pipeline_amount,
          'available_limit' => $program_limit - $utilized_amount - $pipeline_amount,
        ]);
      }

      return false;
    }

    // Check if request exceeds company top level borrower limit
    $top_level_borrower_limit = $this->company->top_level_borrower_limit;
    $utilized_amount = $this->company->total_utilized_amount;
    $pipeline_amount = $this->company->total_pipeline_amount;
    $available_limit = $top_level_borrower_limit - $utilized_amount - $pipeline_amount - $invoice_total_amount;
    // if ($available_limit <= 0) {
    //   return false;
    // }

    // Check if request will exceed drawing power
    if ($vendor_configurations->drawing_power > 0) {
      if ($this->invoice_total_amount > $vendor_configurations->drawing_power) {
        // Notify bank of request to unblock
        foreach ($this->company->bank->users as $user) {
          SendMail::dispatchAfterResponse($user->email, 'RequestToIncreaseFundingLimit', [
            'company_id' => $this->company->id,
            'approved_limit' => $vendor_configurations->sanctioned_limit,
            'current_exposure' => $utilized_amount,
            'pipeline_requests' => $pipeline_amount,
            'available_limit' => $available_limit,
          ]);
        }

        return false;
      }
    }

    return true;
  }

  public function notifyUsers($type)
  {
    switch ($type) {
      case 'LoanClosing':
        // If vendor financing receivable, notify anchor
        if (
          $this->program->programType->name == Program::VENDOR_FINANCING &&
          $this->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE
        ) {
          $anchor_users = $this->program->anchor->users;
          foreach ($anchor_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'LoanClosing', ['invoice_id' => $this->id]);
          }
        }
        // If factoring, notify buyer
        if (
          $this->program->programType->name == Program::VENDOR_FINANCING &&
          ($this->program->programCode->name == Program::FACTORING_WITH_RECOURSE ||
            $this->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE)
        ) {
          $buyer_users = $this->buyer->users;
          foreach ($buyer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'LoanClosing', ['invoice_id' => $this->id]);
          }
        }
        // If dealer financing, notify dealer users
        if ($this->program->programType->name == Program::DEALER_FINANCING) {
          $dealer_users = $this->company->users;
          foreach ($dealer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'LoanClosing', ['invoice_id' => $this->id]);
          }
        }
        // Notify bank RMs users
        $bank_users = $this->program->bankUserDetails;
        foreach ($bank_users as $user) {
          SendMail::dispatchAfterResponse($user->email, 'LoanClosing', ['invoice_id' => $this->id]);
        }
        break;
      case 'LoanDisbursal':
        // If vendor financing receivable, notify anchor
        if (
          $this->program->programType->name == Program::VENDOR_FINANCING &&
          $this->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE
        ) {
          $vendor_users = $this->company->users;
          foreach ($vendor_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'LoanDisbursal', ['invoice_id' => $this->id]);
          }
        }
        // If factoring, notify buyer
        if (
          $this->program->programType->name == Program::VENDOR_FINANCING &&
          ($this->program->programCode->name == Program::FACTORING_WITH_RECOURSE ||
            $this->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE)
        ) {
          $anchor_users = $this->program->anchor->users;
          foreach ($anchor_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'LoanDisbursal', ['invoice_id' => $this->id]);
          }
        }
        // If dealer financing, notify dealer users
        if ($this->program->programType->name == Program::DEALER_FINANCING) {
          $dealer_users = $this->company->users;
          foreach ($dealer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'LoanDisbursal', ['invoice_id' => $this->id]);
          }
        }
        // Notify bank RMs users
        $bank_users = $this->program->bankUserDetails;
        foreach ($bank_users as $user) {
          SendMail::dispatchAfterResponse($user->email, 'LoanDisbursal', ['invoice_id' => $this->id]);
        }
        break;
      case 'FullRepayment':
        // If vendor financing receivable, notify anchor
        if (
          $this->program->programType->name == Program::VENDOR_FINANCING &&
          $this->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE
        ) {
          $anchor_users = $this->program->anchor->users;
          foreach ($anchor_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'FullRepayment', ['invoice_id' => $this->id]);
          }
        }
        // If factoring, notify buyer
        if (
          $this->program->programType->name == Program::VENDOR_FINANCING &&
          ($this->program->programCode->name == Program::FACTORING_WITH_RECOURSE ||
            $this->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE)
        ) {
          $buyer_users = $this->buyer->users;
          foreach ($buyer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'FullRepayment', ['invoice_id' => $this->id]);
          }
        }
        // If dealer financing, notify dealer users
        if ($this->program->programType->name == Program::DEALER_FINANCING) {
          $dealer_users = $this->company->users;
          foreach ($dealer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'FullRepayment', ['invoice_id' => $this->id]);
          }
        }
        // Notify bank RMs users
        $bank_users = $this->program->bankUserDetails;
        foreach ($bank_users as $user) {
          SendMail::dispatchAfterResponse($user->email, 'FullRepayment', ['invoice_id' => $this->id]);
        }
        break;
      case 'PartialRepayment':
        // If vendor financing receivable, notify anchor
        if (
          $this->program->programType->name == Program::VENDOR_FINANCING &&
          $this->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE
        ) {
          $anchor_users = $this->program->anchor->users;
          foreach ($anchor_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'PartialRepayment', ['invoice_id' => $this->id]);
          }
        }
        // If factoring, notify buyer
        if (
          $this->program->programType->name == Program::VENDOR_FINANCING &&
          ($this->program->programCode->name == Program::FACTORING_WITH_RECOURSE ||
            $this->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE)
        ) {
          $buyer_users = $this->buyer->users;
          foreach ($buyer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'PartialRepayment', ['invoice_id' => $this->id]);
          }
        }
        // If dealer financing, notify dealer users
        if ($this->program->programType->name == Program::DEALER_FINANCING) {
          $dealer_users = $this->company->users;
          foreach ($dealer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'PartialRepayment', ['invoice_id' => $this->id]);
          }
        }
        // Notify bank RMs users
        $bank_users = $this->program->bankUserDetails;
        foreach ($bank_users as $user) {
          SendMail::dispatchAfterResponse($user->email, 'PartialRepayment', ['invoice_id' => $this->id]);
        }
        break;
      case 'OverdueFullRepayment':
        // If vendor financing receivable, notify anchor
        if (
          $this->program->programType->name == Program::VENDOR_FINANCING &&
          $this->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE
        ) {
          $anchor_users = $this->program->anchor->users;
          foreach ($anchor_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'OverdueFullRepayment', [
              'invoice_id' => $this->id,
              'amount' => $this->overdue_amount,
            ]);
          }
        }
        // If factoring, notify buyer
        if (
          $this->program->programType->name == Program::VENDOR_FINANCING &&
          ($this->program->programCode->name == Program::FACTORING_WITH_RECOURSE ||
            $this->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE)
        ) {
          $buyer_users = $this->buyer->users;
          foreach ($buyer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'OverdueFullRepayment', [
              'invoice_id' => $this->id,
              'amount' => $this->overdue_amount,
            ]);
          }
        }
        // If dealer financing, notify dealer users
        if ($this->program->programType->name == Program::DEALER_FINANCING) {
          $dealer_users = $this->company->users;
          foreach ($dealer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'OverdueFullRepayment', [
              'invoice_id' => $this->id,
              'amount' => $this->overdue_amount,
            ]);
          }
        }
        break;
      case 'InvoicePaymentProcessed':
        // If vendor financing receivable, notify anchor
        if (
          $this->program->programType->name == Program::VENDOR_FINANCING &&
          $this->program->programCode->name == 'Vendor Financing Receivable'
        ) {
          $anchor_users = $this->program->anchor->users;
          foreach ($anchor_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'InvoicePaymentProcessed', [
              'invoice_id' => $this->id,
            ]);
          }
        }
        // If factoring, notify buyer
        if (
          $this->program->programType->name == Program::VENDOR_FINANCING &&
          ($this->program->programCode->name == 'Factoring With Recourse' ||
            $this->program->programCode->name == 'Factoring Without Recourse')
        ) {
          $buyer_users = $this->buyer->users;
          foreach ($buyer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'InvoicePaymentProcessed', [
              'invoice_id' => $this->id,
            ]);
          }
        }
        // If dealer financing, notify dealer users
        if ($this->program->programType->name == Program::DEALER_FINANCING) {
          $dealer_users = $this->company->users;
          foreach ($dealer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'InvoicePaymentProcessed', [
              'invoice_id' => $this->id,
            ]);
          }
        }
        break;
      case 'BalanceInvoicePayment':
        // If vendor financing receivable, notify anchor
        if (
          $this->program->programType->name == Program::VENDOR_FINANCING &&
          $this->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE
        ) {
          $anchor_users = $this->program->anchor->users;
          foreach ($anchor_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'BalanceInvoicePayment', ['invoice_id' => $this->id]);
          }
        }
        // If factoring, notify buyer
        if (
          $this->program->programType->name == Program::VENDOR_FINANCING &&
          ($this->program->programCode->name == Program::FACTORING_WITH_RECOURSE ||
            $this->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE)
        ) {
          $buyer_users = $this->buyer->users;
          foreach ($buyer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'BalanceInvoicePayment', ['invoice_id' => $this->id]);
          }
        }
        // If dealer financing, notify dealer users
        if ($this->program->programType->name == Program::DEALER_FINANCING) {
          $dealer_users = $this->company->users;
          foreach ($dealer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'BalanceInvoicePayment', ['invoice_id' => $this->id]);
          }
        }
        break;
      case 'InvoicePaymentReceivedBySeller':
        // If vendor financing receivable, notify anchor
        if (
          $this->program->programType->name == Program::VENDOR_FINANCING &&
          $this->program->programCode->name == Program::VENDOR_FINANCING_RECEIVABLE
        ) {
          $anchor_users = $this->program->anchor->users;
          foreach ($anchor_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'InvoicePaymentReceivedBySeller', [
              'invoice_id' => $this->id,
            ]);
          }
        }
        // If factoring, notify buyer
        if (
          $this->program->programType->name == Program::VENDOR_FINANCING &&
          ($this->program->programCode->name == Program::FACTORING_WITH_RECOURSE ||
            $this->program->programCode->name == Program::FACTORING_WITHOUT_RECOURSE)
        ) {
          $buyer_users = $this->buyer->users;
          foreach ($buyer_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'InvoicePaymentReceivedBySeller', [
              'invoice_id' => $this->id,
            ]);
          }
        }
        // If dealer financing, notify dealer users
        if ($this->program->programType->name == Program::DEALER_FINANCING) {
          $anchor_users = $this->program->anchor->users;
          foreach ($anchor_users as $user) {
            SendMail::dispatchAfterResponse($user->email, 'InvoicePaymentReceivedBySeller', [
              'invoice_id' => $this->id,
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

  public function getAllTransactionsSuccessfulAttribute()
  {
    $status = false;
    $pending_transactions = 0;

    $pending_transactions += CbsTransaction::whereHas('paymentRequest', function ($query) {
      $query->where('invoice_id', $this->id);
    })
      ->where('status', '!=', 'Successful')
      ->count();

    if ($pending_transactions <= 0) {
      $status = true;
    }

    return $status;
  }
}
// REAR ENDED DISCOUNT LOGIC
// if ($discount_type == 'Front Ended') {
// } else {
//   //   // INFO: Rear ended
//   //   // Fee charges
//   //   $fees_amount = 0;
//   //   $anchor_bearing_fees = 0;
//   //   $vendor_bearing_fees = 0;
//   //   $fees_tax_amount = 0;
//   //   if ($vendor_fees->count() > 0) {
//   //     foreach ($vendor_fees as $fee) {
//   //       if ($fee->type == 'amount') {
//   //         $fees_amount += $fee->value;

//   //         if ($fee->taxes) {
//   //           $fees_tax_amount += round(($fee->taxes / 100) * $fee->value, 2);
//   //         }

//   //         if ($this->program->programType->name == 'Dealer Financing') {
//   //           $anchor_bearing_fees += 0;
//   //           $vendor_bearing_fees += $fee->value;
//   //         } else {
//   //           $anchor_bearing_fees += ($fee->anchor_bearing_discount / 100) * $fee->value;
//   //           $vendor_bearing_fees += ($fee->vendor_bearing_discount / 100) * $fee->value;
//   //         }
//   //       }

//   //       if ($fee->type == 'percentage') {
//   //         $fees_amount += ($fee->value / 100) * $legible_amount;

//   //         if ($fee->taxes) {
//   //           $fees_tax_amount += round(($fee->taxes / 100) * (($fee->value / 100) * $legible_amount), 2);
//   //         }

//   //         if ($this->program->programType->name == 'Dealer Financing') {
//   //           $anchor_bearing_fees += 0;
//   //           $vendor_bearing_fees += round(($fee->value / 100) * $legible_amount, 2);
//   //         } else {
//   //           $anchor_bearing_fees += round(
//   //             ($fee->anchor_bearing_discount / 100) * (($fee->value / 100) * $legible_amount),
//   //             2
//   //           );
//   //           $vendor_bearing_fees += round(
//   //             ($fee->vendor_bearing_discount / 100) * (($fee->value / 100) * $legible_amount),
//   //             2
//   //           );
//   //         }
//   //       }

//   //       if ($fee->type == 'per amount') {
//   //         $fees_amount += floor($legible_amount / $fee->per_amount) * $fee->value;

//   //         if ($fee->taxes) {
//   //           $fees_tax_amount += round(
//   //             ($fee->taxes / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value),
//   //             2
//   //           );
//   //         }

//   //         if ($this->program->programType->name == 'Dealer Financing') {
//   //           $anchor_bearing_fees += 0;
//   //           $vendor_bearing_fees += round(floor($legible_amount / $fee->per_amount) * $fee->value, 2);
//   //         } else {
//   //           $anchor_bearing_fees += round(
//   //             ($fee->anchor_bearing_discount / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value),
//   //             2
//   //           );
//   //           $vendor_bearing_fees += round(
//   //             ($fee->vendor_bearing_discount / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value),
//   //             2
//   //           );
//   //         }
//   //       }
//   //     }
//   //   }

//   //   if ($this->program->programType->name == 'Vendor Financing') {
//   //     if ($this->program->programCode->name == 'Vendor Financing Receivable') {
//   //       $amount = $legible_amount - $fees_tax_amount - $vendor_bearing_fees;
//   //     } else {
//   //       $amount = $legible_amount - $fees_tax_amount - $anchor_bearing_fees;
//   //     }
//   //   }

//   //   $payment_request = PaymentRequest::create([
//   //     'reference_number' => $reference_number,
//   //     'invoice_id' => $this->id,
//   //     'amount' => round($amount, 2),
//   //     'payment_request_date' => Carbon::parse($payment_date)->format('Y-m-d'),
//   //   ]);

//   //   // Credit to vendor's account
//   //   $payment_request->paymentAccounts()->create([
//   //     'account' => $bank_details->account_number,
//   //     'account_name' => $bank_details->name_as_per_bank,
//   //     'amount' => round($amount, 2),
//   //     'type' => 'vendor_account',
//   //     'description' => 'vendor account',
//   //   ]);

//   //   if ($vendor_fees->count() > 0) {
//   //     foreach ($vendor_fees as $fee) {
//   //       if ($fee->type == 'amount') {
//   //         if ($this->program->programType->name == 'Dealer Financing') {
//   //           $anchor_bearing_fees += 0;
//   //           $vendor_bearing_fees = $fee->value;
//   //           if ($vendor_bearing_fees > 0) {
//   //             $payment_request->paymentAccounts()->create([
//   //               'account' => $fees_income_bank_account ? $fees_income_bank_account->value : 'Fee_Inc_Acc',
//   //               'account_name' => $fees_income_bank_account
//   //                 ? $fees_income_bank_account->name
//   //                 : 'Fees Income Account',
//   //               'amount' => $vendor_bearing_fees,
//   //               'type' => 'program_fees',
//   //               'description' => 'vendor fee bearing',
//   //               'title' => $fee->fee_name,
//   //             ]);
//   //           }
//   //         } else {
//   //           $anchor_bearing_fees = ($fee->anchor_bearing_discount / 100) * $fee->value;
//   //           if ($anchor_bearing_fees > 0) {
//   //             $payment_request->paymentAccounts()->create([
//   //               'account' => $fees_income_bank_account ? $fees_income_bank_account->value : 'Fee_Inc_Acc',
//   //               'account_name' => $fees_income_bank_account
//   //                 ? $fees_income_bank_account->name
//   //                 : 'Fees Income Account',
//   //               'amount' => round($anchor_bearing_fees, 2),
//   //               'type' => 'program_fees',
//   //               'description' => 'anchor fee bearing',
//   //               'title' => $fee->fee_name,
//   //             ]);
//   //           }
//   //           $vendor_bearing_fees = ($fee->vendor_bearing_discount / 100) * $fee->value;
//   //           if ($vendor_bearing_fees > 0) {
//   //             $payment_request->paymentAccounts()->create([
//   //               'account' => $fees_income_bank_account ? $fees_income_bank_account->value : 'Fee_Inc_Acc',
//   //               'account_name' => $fees_income_bank_account
//   //                 ? $fees_income_bank_account->name
//   //                 : 'Fees Income Account',
//   //               'amount' => $vendor_bearing_fees,
//   //               'type' => 'program_fees',
//   //               'description' => 'vendor fee bearing',
//   //               'title' => $fee->fee_name,
//   //             ]);
//   //           }
//   //         }
//   //       }

//   //       if ($fee->type == 'percentage') {
//   //         if ($this->program->programType->name == 'Dealer Financing') {
//   //           $anchor_bearing_fees += 0;
//   //           $vendor_bearing_fees = round(($fee->value / 100) * $legible_amount, 2);
//   //           if ($vendor_bearing_fees > 0) {
//   //             $payment_request->paymentAccounts()->create([
//   //               'account' => $fees_income_bank_account ? $fees_income_bank_account->value : 'Fee_Inc_Acc',
//   //               'account_name' => $fees_income_bank_account
//   //                 ? $fees_income_bank_account->name
//   //                 : 'Fees Income Account',
//   //               'amount' => $vendor_bearing_fees,
//   //               'type' => 'program_fees',
//   //               'description' => 'vendor fee bearing',
//   //               'title' => $fee->fee_name,
//   //             ]);
//   //           }
//   //         } else {
//   //           $anchor_bearing_fees = round(
//   //             ($fee->anchor_bearing_discount / 100) * (($fee->value / 100) * $legible_amount),
//   //             2
//   //           );
//   //           if ($anchor_bearing_fees > 0) {
//   //             $payment_request->paymentAccounts()->create([
//   //               'account' => $fees_income_bank_account ? $fees_income_bank_account->value : 'Fee_Inc_Acc',
//   //               'account_name' => $fees_income_bank_account
//   //                 ? $fees_income_bank_account->name
//   //                 : 'Fees Income Account',
//   //               'amount' => round($anchor_bearing_fees, 2),
//   //               'type' => 'program_fees',
//   //               'description' => 'anchor fee bearing',
//   //               'title' => $fee->fee_name,
//   //             ]);
//   //           }
//   //           $vendor_bearing_fees = round(
//   //             ($fee->vendor_bearing_discount / 100) * (($fee->value / 100) * $legible_amount),
//   //             2
//   //           );
//   //           if ($vendor_bearing_fees > 0) {
//   //             $payment_request->paymentAccounts()->create([
//   //               'account' => $fees_income_bank_account ? $fees_income_bank_account->value : 'Fee_Inc_Acc',
//   //               'account_name' => $fees_income_bank_account
//   //                 ? $fees_income_bank_account->name
//   //                 : 'Fees Income Account',
//   //               'amount' => $vendor_bearing_fees,
//   //               'type' => 'program_fees',
//   //               'description' => 'vendor fee bearing',
//   //               'title' => $fee->fee_name,
//   //             ]);
//   //           }
//   //         }
//   //       }

//   //       if ($fee->type == 'per amount') {
//   //         if ($this->program->programType->name == 'Dealer Financing') {
//   //           $anchor_bearing_fees += 0;
//   //           $vendor_bearing_fees = round(floor($legible_amount / $fee->per_amount) * $fee->value, 2);
//   //           if ($vendor_bearing_fees > 0) {
//   //             $payment_request->paymentAccounts()->create([
//   //               'account' => $fees_income_bank_account ? $fees_income_bank_account->value : 'Fee_Inc_Acc',
//   //               'account_name' => $fees_income_bank_account
//   //                 ? $fees_income_bank_account->name
//   //                 : 'Fees Income Account',
//   //               'amount' => $vendor_bearing_fees,
//   //               'type' => 'program_fees',
//   //               'description' => 'vendor fee bearing',
//   //               'title' => $fee->fee_name,
//   //             ]);
//   //           }
//   //         } else {
//   //           $anchor_bearing_fees = round(
//   //             ($fee->anchor_bearing_discount / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value),
//   //             2
//   //           );

//   //           if ($anchor_bearing_fees > 0) {
//   //             $payment_request->paymentAccounts()->create([
//   //               'account' => $fees_income_bank_account ? $fees_income_bank_account->value : 'Fee_Inc_Acc',
//   //               'account_name' => $fees_income_bank_account
//   //                 ? $fees_income_bank_account->name
//   //                 : 'Fees Income Account',
//   //               'amount' => round($anchor_bearing_fees, 2),
//   //               'type' => 'program_fees',
//   //               'description' => 'anchor fee bearing',
//   //               'title' => $fee->fee_name,
//   //             ]);
//   //           }

//   //           $vendor_bearing_fees += round(
//   //             ($fee->vendor_bearing_discount / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value),
//   //             2
//   //           );

//   //           if ($vendor_bearing_fees > 0) {
//   //             $payment_request->paymentAccounts()->create([
//   //               'account' => $fees_income_bank_account ? $fees_income_bank_account->value : 'Fee_Inc_Acc',
//   //               'account_name' => $fees_income_bank_account
//   //                 ? $fees_income_bank_account->name
//   //                 : 'Fees Income Account',
//   //               'amount' => $vendor_bearing_fees,
//   //               'type' => 'program_fees',
//   //               'description' => 'vendor fee bearing',
//   //               'title' => $fee->fee_name,
//   //             ]);
//   //           }
//   //         }
//   //       }
//   //     }
//   //   }

//   //   // Credit Fee Taxes to Fees taxes Account
//   //   if ($fees_tax_amount > 0) {
//   //     if ($vendor_fees->count() > 0) {
//   //       foreach ($vendor_fees as $fee) {
//   //         if ($fee->type == 'amount') {
//   //           $fees_amount += $fee->value;

//   //           if ($fee->taxes) {
//   //             $fees_tax_amount = round(($fee->taxes / 100) * $fee->value, 2);
//   //           }
//   //         }

//   //         if ($fee->type == 'percentage') {
//   //           $fees_amount += ($fee->value / 100) * $legible_amount;

//   //           if ($fee->taxes) {
//   //             $fees_tax_amount = round(($fee->taxes / 100) * (($fee->value / 100) * $legible_amount), 2);
//   //           }
//   //         }

//   //         if ($fee->type == 'per amount') {
//   //           $fees_amount += floor($legible_amount / $fee->per_amount) * $fee->value;

//   //           if ($fee->taxes) {
//   //             $fees_tax_amount = round(
//   //               ($fee->taxes / 100) * (floor($legible_amount / $fee->per_amount) * $fee->value),
//   //               2
//   //             );
//   //           }
//   //         }

//   //         $tax_income_account = BankTaxRate::where('bank_id', $this->program->bank_id)
//   //           ->where('value', $fee->taxes)
//   //           ->where('status', 'active')
//   //           ->first();

//   //         if ($tax_income_account) {
//   //           $payment_request->paymentAccounts()->create([
//   //             'account' => $tax_income_account->account_no,
//   //             'account_name' => 'Tax Income Bank Account',
//   //             'amount' => round($fees_tax_amount, 2),
//   //             'type' => 'tax_on_fees',
//   //             'description' => 'Tax on Fees for ' . $fee->fee_name,
//   //           ]);
//   //         } else {
//   //           $payment_request->paymentAccounts()->create([
//   //             'account' => $fees_income_bank_account
//   //               ? $fees_income_bank_account->value
//   //               : 'Fees Income Bank Account',
//   //             'account_name' => $fees_income_bank_account
//   //               ? $fees_income_bank_account->name
//   //               : 'Fees Income Bank Account',
//   //             'amount' => round($fees_tax_amount, 2),
//   //             'type' => 'tax_on_fees',
//   //             'description' => 'Tax on Fees for ' . $fee->fee_name,
//   //           ]);
//   //         }
//   //       }
//   //     }
//   //   }
//   //   // End Rear Ended
// }
