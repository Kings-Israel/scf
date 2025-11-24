<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyTax extends Model
{
  use HasFactory;

  protected $guarded = [];

  /**
   * Get the company that owns the CompanyTax
   */
  public function company(): BelongsTo
  {
    return $this->belongsTo(Company::class);
  }
}
