<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramFee extends Model
{
  use HasFactory;

  protected $guarded = [];

  /**
   * Get the program that owns the ProgramCompanyRole
   */
  public function program(): BelongsTo
  {
    return $this->belongsTo(Program::class);
  }
}
