<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankDocument extends Model
{
  use HasFactory;

  /**
   * The attributes that aren't mass assignable.
   *
   * @var array
   */
  protected $guarded = [];

  /**
   * Get the bank that owns the BankDocument
   */
  public function bank(): BelongsTo
  {
    return $this->belongsTo(Bank::class);
  }

  /**
   * Get the programType that owns the BankDocument
   *
   * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
   */
  public function programType(): BelongsTo
  {
    return $this->belongsTo(ProgramType::class, 'product_type_id', 'id');
  }

  /**
   * Get the programCode that owns the BankDocument
   *
   * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
   */
  public function programCode(): BelongsTo
  {
    return $this->belongsTo(ProgramCode::class, 'product_code_id', 'id');
  }
}
