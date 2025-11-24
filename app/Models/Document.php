<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
  use HasFactory;

  protected $guarded = [];

  /**
   * Get the uploadedDocument that owns the Document
   */
  public function uploadedDocument(): BelongsTo
  {
    return $this->belongsTo(UploadDocument::class, 'uuid', 'slug');
  }

  /**
   * Get the pipeline that owns the Document
   *
   * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
   */
  public function pipeline(): BelongsTo
  {
    return $this->belongsTo(Pipeline::class);
  }

  public function resolveStatus(): string
  {
    switch ($this->status) {
      case 'approved':
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
