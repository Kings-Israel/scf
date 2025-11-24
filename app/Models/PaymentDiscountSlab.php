<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentDiscountSlab extends Model
{
  use HasFactory;

  protected $guarded = [];

  /**
   * Get the company that owns the PaymentDiscountSlab
   */
  public function company(): BelongsTo
  {
    return $this->belongsTo(Company::class);
  }

  public function discountSlabs(): HasMany
  {
    return $this->hasMany(DiscountSlab::class);
  }
}
