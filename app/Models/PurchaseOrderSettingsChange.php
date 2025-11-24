<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseOrderSettingsChange extends Model
{
  use HasFactory;

  protected $guarded = [];

  protected $casts = [
    'changes' => 'array'
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
  public function companyPurchaseOrderSetting(): BelongsTo
  {
    return $this->belongsTo(CompanyPurchaseOrderSetting::class, 'po_setting_id', 'id');
  }
}
