<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PaymentRequestAccount extends Model
{
  use HasFactory;

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
  protected $appends = ['can_show'];

  /**
   * Get the description
   *
   * @param  string  $value
   * @return string
   */
  public function getDescriptionAttribute($value)
  {
    return Str::title($value);
  }

  /**
   * Get the can show
   *
   * @param  string  $value
   * @return boolean
   */
  public function getCanShowAttribute()
  {
    $discount_transactions = ['discount', 'tax_on_discount'];
    $fee_transactions = ['program_fees', 'tax_on_fees'];
    if (Carbon::parse($this->paymentRequest->invoice->due_date)->greaterThan($this->updated_at)) {
      if (
        $this->paymentRequest->invoice->discount_charge_type == 'Rear Ended' &&
        collect($discount_transactions)->contains($this->type)
      ) {
        return false;
      }

      if (
        $this->paymentRequest->invoice->fee_charge_type == 'Rear Ended' &&
        collect($fee_transactions)->contains($this->type)
      ) {
        return false;
      }
    }

    if ($this->type == 'vendor_account') {
      return false;
    }

    return true;
  }

  /**
   * Get the paymentRequest that owns the PaymentRequestAccount
   */
  public function paymentRequest(): BelongsTo
  {
    return $this->belongsTo(PaymentRequest::class);
  }
}
