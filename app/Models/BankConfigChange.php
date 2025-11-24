<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BankConfigChange extends Model
{
  use HasFactory;

  protected $guarded = [];

  protected $appends = ['can_approve'];

  protected $casts = [
    'changes' => 'array'
  ];

  public function configurable(): MorphTo
  {
    return $this->morphTo('configurable');
  }

  /**
   * Get the user that owns the ProposedConfigurationChange
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by', 'id');
  }

  public function getCanApproveAttribute(): bool
  {
    if (auth()->check()) {
      if (auth()->id() != $this->created_by) {
        return true;
      }

      return false;
    }

    return false;
  }
}
