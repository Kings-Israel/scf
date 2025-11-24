<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyAuthorizationGroup extends Model
{
  use HasFactory;

  /**
   * The attributes that aren't mass assignable.
   *
   * @var array
   */
  protected $guarded = [];

  /**
   * Get the company that owns the CompanyAuthorizationGroup
   */
  public function company(): BelongsTo
  {
    return $this->belongsTo(Company::class);
  }

  /**
   * Get the programType that owns the CompanyAuthorizationGroup
   */
  public function programType(): BelongsTo
  {
    return $this->belongsTo(ProgramType::class);
  }

  /**
   * Get all of the rules for the CompanyAuthorizationGroup
   */
  public function rules(): HasMany
  {
    return $this->hasMany(AuthorizationMatrixRule::class, 'group_id', 'id');
  }

  /**
   * Get all of the authorizationUsers for the CompanyAuthorizationGroup
   */
  public function authorizationUsers(): HasMany
  {
      return $this->hasMany(CompanyUserAuthorizationGroup::class, 'group_id', 'id');
  }
}
