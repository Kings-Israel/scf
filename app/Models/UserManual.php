<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserManual extends Model
{
  use HasFactory;

  protected $guarded = [];

  public function getBankUserManualAttribute($value)
  {
    if ($value) {
      return config('app.backend_url') . '/' . str_replace('public', 'storage', $value);
    }

    return null;
  }

  public function getVendorUserManualAttribute($value)
  {
    if ($value) {
      return config('app.backend_url') . '/' . str_replace('public', 'storage', $value);
    }

    return null;
  }

  public function getAnchorUserManualAttribute($value)
  {
    if ($value) {
      return config('app.backend_url') . '/' . str_replace('public', 'storage', $value);
    }

    return null;
  }

  public function getDealerUserManualAttribute($value)
  {
    if ($value) {
      return config('app.backend_url') . '/' . str_replace('public', 'storage', $value);
    }

    return null;
  }
}
