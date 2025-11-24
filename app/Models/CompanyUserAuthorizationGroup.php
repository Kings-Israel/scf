<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyUserAuthorizationGroup extends Model
{
  use HasFactory;

  protected $guarded = [];

  /**
   * Get the user that owns the CompanyUser
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Get the company that owns the CompanyUser
   */
  public function company(): BelongsTo
  {
    return $this->belongsTo(Company::class);
  }

  /**
   * Get the company that owns the CompanyUser
   */
  public function authorizationGroup(): BelongsTo
  {
    return $this->belongsTo(CompanyAuthorizationGroup::class);
  }

  /**
   * Get the company that owns the CompanyUser
   */
  public function programType(): BelongsTo
  {
    return $this->belongsTo(ProgramType::class);
  }
}
