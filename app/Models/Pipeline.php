<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pipeline extends Model
{
  use HasFactory;

  protected $guarded = [];

  /**
   * The accessors to append to the model's array form.
   *
   * @var array
   */
  protected $appends = ['can_view', 'can_edit', 'can_activate'];

  /**
   * Get the user that owns the Pipeline
   *
   * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by', 'id');
  }

  /**
   * Get all of the uploadedDocuments for the Pipeline
   */
  public function uploadedDocuments(): HasMany
  {
    return $this->hasMany(UploadDocument::class);
  }

  /**
   * Scope a query to only include all crm approved documents
   *
   * @param  \Illuminate\Database\Eloquent\Builder $query
   * @return \Illuminate\Database\Eloquent\Builder
   */
  public function scopeCrmApproved($query)
  {
    return $query->whereHas('uploadedDocuments', function ($query) {
      $query->where('sent_to_bank', true);
    });
  }

  public function hasAllDocumentsApproved()
  {
    $required_documents = UploadDocument::where('pipeline_id', $this->id)->first();

    if ($required_documents) {
      $not_uploaded_documents = [];
      foreach (json_decode(json_decode($required_documents->documents)) as $document) {
        $uploaded = Document::where('pipeline_id', $this->id)
          ->where('document_name', $document)
          ->latest()
          ->first();

        if (!$uploaded || (($uploaded && $uploaded->status == 'rejected') || $uploaded->status == 'pending')) {
          array_push($not_uploaded_documents, $document);
        }
      }
      // info($not_uploaded_documents);
      if (count($not_uploaded_documents) == 0) {
        return true;
      }

      return false;
    }

    return true;
  }

  public function hasUploadedAllDocuments()
  {
    $document = UploadDocument::where('pipeline_id', $this->id)->first();

    $required_documents_count = count(explode(',', str_replace('"', '', str_replace('\\', '', $document->documents))));
    $uploaded_documents_count = Document::where('uuid', $document->slug)->count();

    if ($required_documents_count > $uploaded_documents_count) {
      return false;
    }

    return true;
  }

  /**
   * Get the can view companies
   *
   * @param  string  $value
   * @return string
   */
  public function getCanViewAttribute()
  {
    return auth()
      ->user()
      ->hasPermissionTo('View Companies');
  }

  /**
   * Get the can edit companies
   *
   * @param  string  $value
   * @return string
   */
  public function getCanEditAttribute()
  {
    return auth()
      ->user()
      ->hasPermissionTo('Add/Edit Companies');
  }

  /**
   * Get the can activate companies
   *
   * @param  string  $value
   * @return string
   */
  public function getCanActivateAttribute()
  {
    return auth()
      ->user()
      ->hasPermissionTo('Activate/Deactivate Companies');
  }
}
