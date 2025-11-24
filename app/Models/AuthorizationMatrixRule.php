<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthorizationMatrixRule extends Model
{
  use HasFactory;

  /**
   * The attributes that aren't mass assignable.
   *
   * @var array
   */
  protected $guarded = [];

  /**
   * Get the authorizationGroup that owns the AuthorizationMatrixRule
   */
  public function authorizationMatrix(): BelongsTo
  {
    return $this->belongsTo(CompanyAuthorizationMatrix::class, 'matrix_id', 'id');
  }

  /**
   * Get the authorizationMatrix that owns the AuthorizationMatrixRule
   *
   * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
   */
  public function authorizationGroup(): BelongsTo
  {
      return $this->belongsTo(CompanyAuthorizationGroup::class, 'matrix_id', 'id');
  }
}
