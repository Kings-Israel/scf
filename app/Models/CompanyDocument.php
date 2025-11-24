<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyDocument extends Model
{
  use HasFactory;

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = ['company_id', 'name', 'expiry_date', 'status', 'path', 'rejected_reason'];

  /**
   * Get the path
   *
   * @param  string  $value
   * @return string
   */
  public function getPathAttribute($value)
  {
    return config('app.url') . '/storage/company/documents/' . $value;
  }

  /**
   * Get the company that owns the CompanyDocument
   */
  public function company(): BelongsTo
  {
    return $this->belongsTo(Company::class);
  }

  public function resolveStatus(): string
  {
    switch ($this->status) {
      case 'accepted':
        return 'bg-label-success';
        break;
      case 'rejected':
        return 'bg-label-danger';
        break;
      default:
        return 'bg-label-primary';
        break;
    }
  }
}
