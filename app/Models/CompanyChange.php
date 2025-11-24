<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyChange extends Model
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
   * Get the user that owns the CompanyChange
   */
  public function user(): BelongsTo
  {
      return $this->belongsTo(User::class);
  }

  /**
   * Get the company that owns the CompanyChange
   */
  public function company(): BelongsTo
  {
      return $this->belongsTo(Company::class);
  }

  /**
   * Check if can approve changes
   *
   * @return bool
   */
  public function getCanApproveAttribute()
  {
    if (auth()->check()) {
      if (auth()->id() == $this->user_id) {
        return false;
      }

      return true;
    }

    return false;
  }
}
