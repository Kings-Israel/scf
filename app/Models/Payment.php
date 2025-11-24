<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
  use HasFactory;

  /**
   * The attributes that aren't mass assignable.
   *
   * @var array
   */
  protected $guarded = [];

  /**
   * Get the invoice that owns the Payment
   */
  public function invoice(): BelongsTo
  {
    return $this->belongsTo(Invoice::class);
  }

  /**
   * Get the amount
   *
   * @param  string  $value
   * @return string
   */
  public function getAmountAttribute($value)
  {
    return round($value, 2);
  }
}
