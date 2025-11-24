<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class TermsConditionsConfig extends Model
{
  use HasFactory;

  /**
   * The attributes that aren't mass assignable.
   *
   * @var array
   */
  protected $guarded = [];

  /**
   * Get the status
   *
   * @param  string  $value
   * @return string
   */
  public function getStatusAttribute($value)
  {
    return Str::title($value);
  }

  /**
   * Get the bank that owns the TermsConditionsConfig
   *
   * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
   */
  public function bank(): BelongsTo
  {
    return $this->belongsTo(Bank::class);
  }

  public function change(): MorphOne
  {
    return $this->morphOne(ProposedConfigurationChange::class, 'configurable');
  }

  /**
   * Get the purchaseOrderSetting associated with the Company
   */
  public function proposedUpdate(): MorphOne
  {
    return $this->morphOne(BankConfigChange::class, 'configurable');
  }
}
