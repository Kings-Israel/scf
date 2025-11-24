<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceSettingsChange extends Model
{
  use HasFactory;

  protected $guarded = [];

  /**
   * The attributes that should be cast to native types.
   *
   * @var array
   */
  protected $casts = [
    'changes' => 'array',
  ];

  /**
   * Get the user that owns the PurchaseOrderSettingsChange
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Get the companyPurchaseOrderSetting that owns the PurchaseOrderSettingsChange
   *
   * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
   */
  public function companyInvoiceSetting(): BelongsTo
  {
    return $this->belongsTo(CompanyInvoiceSetting::class, 'invoice_setting_id', 'id');
  }
}
