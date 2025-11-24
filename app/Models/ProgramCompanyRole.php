<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramCompanyRole extends Model
{
  use HasFactory;

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = ['program_id', 'company_id', 'role_id'];

  /**
   * Get the company that owns the ProgramCompanyRole
   */
  public function company(): BelongsTo
  {
    return $this->belongsTo(Company::class);
  }

  /**
   * Get the program that owns the ProgramCompanyRole
   */
  public function program(): BelongsTo
  {
    return $this->belongsTo(Program::class);
  }

  /**
   * Get the programRole that owns the ProgramCompanyRole
   */
  public function programRole(): BelongsTo
  {
    return $this->belongsTo(ProgramRole::class, 'role_id', 'id');
  }
}
