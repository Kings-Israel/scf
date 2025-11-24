<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramChange extends Model
{
  use HasFactory;

  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = ['program_id', 'changes', 'user_id'];

  protected $casts = [
    'changes' => 'array'
  ];

  /**
   * Get the user that owns the ProgramChange
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Get the program that owns the ProgramChange
   */
  public function program(): BelongsTo
  {
    return $this->belongsTo(Program::class);
  }
}
