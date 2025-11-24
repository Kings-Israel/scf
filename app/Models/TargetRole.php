<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TargetRole extends Model
{
  use HasFactory;

  protected $guarded = [];

  /**
   * Get all of the AccessGroups for the Role
   *
   * @return \Illuminate\Database\Eloquent\Relations\HasMany
   */
  public function AccessGroups(): HasMany
  {
    return $this->hasMany(AccessRightGroup::class);
  }
}
