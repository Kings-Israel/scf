<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompanyRelationshipManager extends Model
{
  use HasFactory;

  protected $guarded = [];

  /**
   * Get the company that owns the CompanyRelationshipManager
   */
  public function company(): BelongsTo
  {
    return $this->belongsTo(Company::class);
  }
}
