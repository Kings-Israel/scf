<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankConvertionRate extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the bank that owns the BankConvertionRate
     */
    public function bank(): BelongsTo
    {
      return $this->belongsTo(Bank::class);
    }
}
