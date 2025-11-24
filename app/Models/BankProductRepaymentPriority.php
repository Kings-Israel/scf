<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class BankProductRepaymentPriority extends Model
{
  use HasFactory;

  /**
   * The attributes that aren't mass assignable.
   *
   * @var array
   */
  protected $guarded = [];

  /**
   * Get the bank that owns the BankProductRepaymentPriority
   */
  public function bank(): BelongsTo
  {
    return $this->belongsTo(Bank::class);
  }

  /**
   * Get the productType that owns the BankProductRepaymentPriority
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
