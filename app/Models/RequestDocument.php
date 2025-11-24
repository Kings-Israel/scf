<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestDocument extends Model
{
  use HasFactory;

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = ['company_id', 'name', 'status'];

  /**
   * Get the company that owns the RequestDocument
   */
  public function company(): BelongsTo
  {
      return $this->belongsTo(Company::class);
  }
}
