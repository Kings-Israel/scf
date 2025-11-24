<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class ProgramVendorFee extends Model
{
  use HasFactory;

  /**
   * The attributes that aren't mass assignable.
   *
   * @var array
   */
  protected $guarded = [];

  /**
   * Get the program that owns the ProgramVendorBankDetail
   */
  public function program(): BelongsTo
  {
    return $this->belongsTo(Program::class);
  }

  /**
   * Get the company that owns the ProgramVendorBankDetail
   */
  public function company(): BelongsTo
  {
    return $this->belongsTo(Company::class);
  }

  /**
   * Get the buyer that owns the Invoice
   */
  public function buyer(): BelongsTo
  {
    return $this->belongsTo(Company::class, 'buyer_id', 'id');
  }
}
