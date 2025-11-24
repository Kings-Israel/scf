<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProgramMappingChange extends Model
{
  use HasFactory;

  protected $guarded = [];

  protected $casts = [
    'changes' => 'array'
  ];

  /**
   * The accessors to append to the model's array form.
   *
   * @var array
   */
  protected $appends = ['can_approve'];

  /**
   * Get the user that owns the ProgramMappingChange
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Get the program that owns the ProgramMappingChange
   */
  public function program(): BelongsTo
  {
    return $this->belongsTo(Program::class);
  }

  /**
   * Get the company that owns the ProgramMappingChange
   */
  public function company(): BelongsTo
  {
    return $this->belongsTo(Company::class);
  }

  /**
   * Get the can approve
   *
   * @param  string  $value
   * @return string
   */
  public function getCanApproveAttribute()
  {
    if (auth()->check()) {
      if ($this->user_id == auth()->id()) {
        return false;
      }

      return true;
    }

    return false;
  }
}
