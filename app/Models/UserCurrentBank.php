<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCurrentBank extends Model
{
  use HasFactory;

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = ['bank_id', 'user_id'];

  /**
   * Get the user that owns the UserCurrentBank
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Get the bank that owns the UserCurrentBank
   */
  public function bank(): BelongsTo
  {
    return $this->belongsTo(Bank::class);
  }
}
