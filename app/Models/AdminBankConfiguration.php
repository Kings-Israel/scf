<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminBankConfiguration extends Model
{
  use HasFactory;

  /**
   * The attributes that aren't mass assignable.
   *
   * @var array
   */
  protected $guarded = [];

  /**
   * Get the bank that owns the AdminBankConfiguration
   */
  public function bank(): BelongsTo
  {
    return $this->belongsTo(Bank::class);
  }

  /**
   * Get the date format
   *
   * @param  string  $value
   * @return string
   */
  public function getDateFormatAttribute($value)
  {
    if ($value) {
      return explode('(Ex.', $value)[0];
    }

    return 'DD MMM YYYY';
  }
}
