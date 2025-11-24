<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CronLog extends Model
{
  use HasFactory;

  /**
   * The attributes that aren't mass assignable.
   *
   * @var array
   */
  protected $guarded = [];

  /**
   * The accessors to append to the model's array form.
   *
   * @var array
   */
  protected $appends = ['time_taken'];

  /**
   * Get the time taken
   *
   * @param  string  $value
   * @return string
   */
  public function getTimeTakenAttribute()
  {
    $time = 0;
    if ($this->start_time && $this->end_time) {
      $time = Carbon::parse($this->start_time)->diffInMinutes(Carbon::parse($this->end_time));
    }
    return $time;
  }
}
