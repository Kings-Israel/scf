<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NoaTemplate extends Model
{
  use HasFactory;

  /**
   * The attributes that aren't mass assignable.
   *
   * @var array
   */
  protected $guarded = [];

  public function scopeActive($query)
  {
    return $query->where('status', 'active');
  }

  /**
   * Get the bank that owns the BankBaseRate
   */
  public function bank(): BelongsTo
  {
    return $this->belongsTo(Bank::class);
  }

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
