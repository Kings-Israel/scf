<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class BankWithholdingTaxConfiguration extends Model
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
    'branch_specific' => 'bool',
  ];

  /**
   * Get the bank that owns the BankWithholdingTaxConfiguration
   */
  public function bank(): BelongsTo
  {
    return $this->belongsTo(Bank::class);
  }

  public function configurationChanges(): MorphMany
  {
    return $this->morphMany(ProposedConfigurationChange::class, 'configurable');
  }
}
