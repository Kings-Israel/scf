<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleChange extends Model
{
  use HasFactory;

  /**
   * The attributes that aren't mass assignable.
   *
   * @var array
   */
  protected $guarded = [];

  /**
   * Get the permissionData that owns the RoleChange
   *
   * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
   */
  public function permissionData(): BelongsTo
  {
    return $this->belongsTo(PermissionData::class);
  }

  /**
   * Get the user that owns the RoleChange
   *
   * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by', 'id');
  }

  /**
   * Get the changes
   *
   * @param  string  $value
   * @return string
   */
  public function getChangesAttribute($value)
  {
    return json_decode($value, true);
  }
}
