<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class BankProductsConfiguration extends Model
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
   * Get the bank that owns the BankProductsConfiguration
   */
  public function bank(): BelongsTo
  {
    return $this->belongsTo(Bank::class);
  }

  /**
   * Get the productType that owns the BankProductsConfiguration
   */
  public function productType(): BelongsTo
  {
    return $this->belongsTo(ProgramType::class, 'product_type_id', 'id');
  }

  /**
   * Get the productCode that owns the BankProductsConfiguration
   */
  public function productCode(): BelongsTo
  {
    return $this->belongsTo(ProgramCode::class, 'product_code_id', 'id');
  }

  public function configurationChanges(): MorphMany
  {
    return $this->morphMany(ProposedConfigurationChange::class, 'configurable');
  }
}
