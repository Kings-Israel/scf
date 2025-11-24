<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
  use HasFactory;

  protected $guarded = [];

  protected $casts = [
    'properties' => 'array',
  ];

  public function user()
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  public function pipeline()
  {
    return $this->belongsTo(Pipeline::class);
  }

  public function scopeWithUserAndPipeline($query)
  {
    return $query->with(['user:id,name', 'pipeline:id,name']);
  }
}
