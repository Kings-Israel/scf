<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceItem extends Model
{
  use HasFactory;

  protected $guarded = [];

  /**
   * Get the invoice that owns the InvoiceItem
   */
  public function invoice(): BelongsTo
  {
    return $this->belongsTo(Invoice::class);
  }

  /**
   * Get the invoiceTax associated with the InvoiceItem
   */
  public function invoiceTaxes(): HasMany
  {
    return $this->hasMany(InvoiceTax::class);
  }

  /**
   * Get the invoiceDiscount associated with the InvoiceItem
   */
  public function invoiceDiscount(): HasOne
  {
    return $this->hasOne(InvoiceDiscount::class);
  }

  public function calculateTotal(): int
  {
    return $this->quantity * $this->price_per_quantity;
  }

  /**
   * Get the taxes total
   *
   * @param  string  $value
   * @return string
   */
  public function getTaxesAttribute()
  {
    $taxes = 0;
    foreach ($this->invoiceTaxes as $tax) {
      $taxes += $tax->value;
    }

    return $taxes;
  }

  /**
   * Get the discounts total
   *
   * @return string
   */
  public function getDiscountAttribute()
  {
    $discount = 0;

    if ($this->invoiceDiscount) {
      $discount =
        $this->invoiceDiscount->type === 'percentage'
          ? ($this->invoiceDiscount->value / 100) * $this->calculateTotal()
          : $this->invoiceDiscount->value;
    }

    return $discount;
  }
}
