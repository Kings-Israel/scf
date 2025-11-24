<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class BankGeneralProductConfiguration extends Model
{
  use HasFactory;

  protected $guarded = [];

  /**
   * The attributes that should be cast to native types.
   *
   * @var array
   */
  protected $casts = [
    'input_options' => 'array',
  ];

  /**
   * Get the bank that owns the BankProductsConfiguration
   */
  public function bank(): BelongsTo
  {
    return $this->belongsTo(Bank::class);
  }

  /**
   * Get the productType that owns the BankGeneralProductConfiguration
   */
  public function productType(): BelongsTo
  {
    return $this->belongsTo(ProgramType::class, 'product_type_id', 'id');
  }

  public function configurationChanges(): MorphMany
  {
    return $this->morphMany(ProposedConfigurationChange::class, 'configurable');
  }
}
