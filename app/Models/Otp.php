<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Otp extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
      'expires_at' => 'timestamp',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'otps';

    /**
     * Get the user that owns the Otp
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
