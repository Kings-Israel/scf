<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AnchorConfigurationChange extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['can_approve'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
      'data' => 'array',
    ];

    public function configurable(): MorphTo
    {
      return $this->morphTo('configurable');
    }

    /**
     * Get the user that owns the AnchorConfigurationChange
     */
    public function user(): BelongsTo
    {
      return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * Get the company that owns the AnchorConfigurationChange
     */
    public function company(): BelongsTo
    {
      return $this->belongsTo(Company::class);
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
