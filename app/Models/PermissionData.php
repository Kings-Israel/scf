<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PermissionData extends Model
{
  use HasFactory;

  protected $guarded = [];

  public function roleIDs()
  {
    return $this->hasMany(RoleId::class);
  }

  /**
   * Get the change associated with the PermissionData
   *
   * @return \Illuminate\Database\Eloquent\Relations\HasOne
   */
  public function change(): HasOne
  {
    return $this->hasOne(RoleChange::class);
  }
}
