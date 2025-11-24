<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceDiscount extends Model
{
  use HasFactory;

  protected $guarded = [];

  /**
   * Get the invoice that owns the InvoiceDiscount
   */
  public function invoice(): BelongsTo
  {
    return $this->belongsTo(Invoice::class);
  }

  /**
   * Get the invoiceItem that owns the InvoiceDiscount
   */
  public function invoiceItem(): BelongsTo
  {
    return $this->belongsTo(InvoiceItem::class);
  }
}
