<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceApproval extends Model
{
  use HasFactory;

  protected $guarded = [];

  /**
   * Get the user that owns the PurchaseOrderApproval
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Get the invoice that owns the invoiceApproval
   */
  public function invoice(): BelongsTo
  {
    return $this->belongsTo(Invoice::class);
  }
}
