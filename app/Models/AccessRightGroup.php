<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccessRightGroup extends Model
{
  use HasFactory;

  /**
   * Get all of the AccessRights for the AccessRightGroup
   *
   * @return \Illuminate\Database\Eloquent\Relations\HasMany
   */
  public function AccessRights(): HasMany
  {
      return $this->hasMany(AccessRights::class);
  }
  /**
   * Get all of the RoleIds for the AccessRightGroup
   *
   * @return \Illuminate\Database\Eloquent\Relations\HasMany
   */
  public function RoleIds(): HasMany
  {
      return $this->hasMany(RoleId::class);
  }
}
