<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class ProgramRole extends Model
{
  use HasFactory;

  /**
   * Get all of the programs for the ProgramRole
   *
   * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
   */
  public function programs(): HasManyThrough
  {
    // program_company_roles, programs, program_roles, program_company_roles
    return $this->hasManyThrough(Program::class, ProgramCompanyRole::class, 'role_id', 'id', 'id', 'program_id');
  }

  /**
   * Get all of the companies for the ProgramRole
   *
   * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
   */
  public function companies(): HasManyThrough
  {
    return $this->hasManyThrough(Company::class, ProgramCompanyRole::class, 'role_id', 'id', 'id', 'company_id');
  }
}
