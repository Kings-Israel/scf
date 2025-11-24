<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleId extends Model
{
  use HasFactory;

  protected $guarded = [];

  public function role(): BelongsTo
  {
    return $this->belongsTo(PermissionData::class);
  }

  /**
   * Get the AccessRightGroup that owns the RoleID
   *
   * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
   */
  public function AccessRightGroup(): BelongsTo
  {
    return $this->belongsTo(AccessRightGroup::class);
  }
}
