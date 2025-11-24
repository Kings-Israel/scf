<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRequestApproval extends Model
{
  use HasFactory;

  /**
   * The attributes that aren't mass assignable.
   *
   * @var array
   */
  protected $guarded = [];

  /**
   * Get the user that owns the PaymentRequestApproval
   *
   * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Get the paymentRequest that owns the PaymentRequestApproval
   *
   * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
   */
  public function paymentRequest(): BelongsTo
  {
    return $this->belongsTo(PaymentRequest::class);
  }
}
