<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramCode extends Model
{
  use HasFactory;

  /**
   * Get all of the programs for the ProgramCode
   */
  public function programs(): HasMany
  {
    return $this->hasMany(Program::class);
  }

  /**
   * Get the programType that owns the ProgramCode
   */
  public function programType(): BelongsTo
  {
      return $this->belongsTo(ProgramType::class);
  }
}
