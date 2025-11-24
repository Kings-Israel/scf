<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FinanceRequestApproval extends Model
{
  use HasFactory;

  protected $guarded = [];

  protected $appends = ['can_approve'];

  /**
   * Get the user that owns the FinanceRequestApproval
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Get the paymentRequest that owns the FinanceRequestApproval
   */
  public function paymentRequest(): BelongsTo
  {
    return $this->belongsTo(PaymentRequest::class);
  }

  public function getCanApproveAttribute(): bool
  {
    if ($this->status == 'rejected') {
      return false;
    }

    if (auth()->check()) {
      if ($this->paymentRequest->invoice->company->users->count() <= 1) {
        return true;
      }

      if (auth()->id() != $this->user_id) {
        return true;
      }

      return false;
    }

    return false;
  }
}
