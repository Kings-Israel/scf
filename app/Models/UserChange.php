<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserChange extends Model
{
  use HasFactory;

  protected $casts = [
    'changes' => 'array'
  ];

  /**
   * The attributes that aren't mass assignable.
   *
   * @var array
   */
  protected $guarded = [];

  /**
   * Get the user that owns the UserChange
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Get the createdBy that owns the UserChange
   */
  public function createdBy(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by', 'id');
  }
}
