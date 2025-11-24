<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderApproval extends Model
{
  use HasFactory;

  protected $fillable = ['purchase_order_id', 'user_id'];

  /**
   * Get the user that owns the PurchaseOrderApproval
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Get the purchaseOrder that owns the PurchaseOrderApproval
   */
  public function purchaseOrder(): BelongsTo
  {
    return $this->belongsTo(PurchaseOrder::class);
  }
}
