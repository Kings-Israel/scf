<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramBankDetails extends Model
{
  use HasFactory;

  protected $fillable = [
    'program_id',
    'name',
    'account_number',
    'bank_name',
    'branch',
    'swift_code',
    'account_type',
    'name_as_per_bank',
  ];

  /**
   * Get the program that owns the ProgramCompanyRole
   */
  public function program(): BelongsTo
  {
    return $this->belongsTo(Program::class);
  }
}
