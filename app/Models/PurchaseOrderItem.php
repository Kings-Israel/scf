<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
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
  protected $appends = ['total'];

  /**
   * Get the purchaseOrder that owns the PurchaseOrderItem
   *
   * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
   */
  public function purchaseOrder(): BelongsTo
  {
    return $this->belongsTo(PurchaseOrder::class);
  }

  public function getTotalAttribute()
  {
    return $this->quantity * $this->price_per_quantity;
  }
}
