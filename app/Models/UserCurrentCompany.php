<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserCurrentCompany extends Model
{
  use HasFactory;

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = ['user_id', 'company_id', 'platform'];

  /**
   * Get the user that owns the UserCurrentCompany
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Get the company that owns the UserCurrentCompany
   */
  public function company(): BelongsTo
  {
    return $this->belongsTo(Company::class);
  }
}
