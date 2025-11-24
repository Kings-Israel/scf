<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoleType extends Model
{
  use HasFactory;

  protected $guarded = [];

  /**
   * Get all of the Groups for the RoleType
   *
   * @return \Illuminate\Database\Eloquent\Relations\HasMany
   */
  public function Groups(): HasMany
  {
    return $this->hasMany(TargetRole::class);
  }
}
