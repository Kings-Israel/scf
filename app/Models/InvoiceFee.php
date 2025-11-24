<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceFee extends Model
{
  use HasFactory;

  protected $guarded = [];

  /**
   * Get the invoice that owns the InvoiceFee
   */
  public function invoice(): BelongsTo
  {
    return $this->belongsTo(Invoice::class);
  }
}
