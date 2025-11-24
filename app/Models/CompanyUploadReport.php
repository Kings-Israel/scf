<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyUploadReport extends Model
{
  use HasFactory;

  /**
   * The attributes that aren't mass assignable.
   *
   * @var array
   */
  protected $guarded = [];

  /**
   * Get the bank that owns the CompanyUploadReport
   *
   * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
   */
  public function bank(): BelongsTo
  {
    return $this->belongsTo(Bank::class);
  }
}
