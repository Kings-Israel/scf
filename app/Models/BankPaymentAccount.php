<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankPaymentAccount extends Model
{
  use HasFactory;

  protected $fillable = [
    'bank_id', 'account_name', 'account_number', 'is_active'
  ];

  /**
   * The attributes that should be cast to native types.
   *
   * @var array
   */
  protected $casts = [
    'is_active' => 'bool',
  ];

  /**
   * Get the bank that owns the BankPaymentAccount
   */
  public function bank(): BelongsTo
  {
    return $this->belongsTo(Bank::class);
  }
}
