<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompanyInvoiceSetting extends Model
{
  use HasFactory;

  /**
   * The attributes that aren't mass assignable.
   *
   * @var array
   */
  protected $guarded = [];

  /**
   * The attributes that should be cast to native types.
   *
   * @var array
   */
  protected $casts = [
    'maker_checker_creating_updating' => 'bool',
    'auto_request_financing' => 'bool',
    'request_financing_maker_checker' => 'bool',
  ];

  /**
   * Get the company that owns the CompanyInvoiceSetting
   */
  public function company(): BelongsTo
  {
    return $this->belongsTo(Company::class);
  }

  /**
   * Get the proposedUpdate associated with the Program
   */
  public function proposedUpdate(): HasOne
  {
    return $this->hasOne(InvoiceSettingsChange::class, 'invoice_setting_id', 'id');
  }
}
