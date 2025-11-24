<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UploadDocument extends Model
{
  use HasFactory;

  protected $guarded = [];

  /**
   * Get all of the documents for the UploadDocument
   */
  public function companyDocuments(): HasMany
  {
    return $this->hasMany(Document::class, 'uuid', 'slug');
  }

  /**
   * Get the pipeline that owns the UploadDocument
   */
  public function pipeline(): BelongsTo
  {
    return $this->belongsTo(Pipeline::class);
  }
}
