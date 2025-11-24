<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankRejectionReason extends Model
{
  use HasFactory;

  protected $guarded = [];

  /**
   * Get the bank that owns the BankRejectionReason
   */
  public function bank(): BelongsTo
  {
    return $this->belongsTo(BankUser::class);
  }
}
