<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProposedConfigurationChange extends Model
{
  use HasFactory;

  protected $guarded = [];

  protected $appends = ['can_approve'];

  /**
   * Get the user that owns the ProposedConfigurationChange
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function configurable(): MorphTo
  {
    return $this->morphTo('configurable');
  }

  public function modeable(): MorphTo
  {
    return $this->morphTo('modeable');
  }

  public function getCanApproveAttribute(): bool
  {
    if (auth()->check()) {
      if (auth()->id() != $this->user_id) {
        return true;
      }

      return false;
    }

    return false;
  }
}
