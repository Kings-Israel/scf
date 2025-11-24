<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramType extends Model
{
  use HasFactory;

  /**
   * Get all of the programs for the ProgramType
   */
  public function programs(): HasMany
  {
    return $this->hasMany(Program::class);
  }

  /**
   * Get all of the programCodes for the ProgramType
   */
  public function programCodes(): HasMany
  {
    return $this->hasMany(ProgramCode::class);
  }
}
