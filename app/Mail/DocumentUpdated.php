<?php

namespace App\Mail;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\Models\CompanyDocument;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class DocumentUpdated extends Mailable
{
  use Queueable, SerializesModels;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct(
    public string $user_name,
    public $document,
    public string $status,
    public string $url = '',
    public string $logo = ''
  ) {
    //
  }

  /**
   * Get the message envelope.
   *
   * @return \Illuminate\Mail\Mailables\Envelope
   */
  public function envelope()
  {
    return new Envelope(subject: 'YoFinvoice Document Updated');
  }

  /**
   * Get the message content definition.
   *
   * @return \Illuminate\Mail\Mailables\Content
   */
  public function content()
  {
    $document = null;
    $company_document = null;

    $document = Document::find($this->document->id);

    if (!$document) {
      $company_document = CompanyDocument::find($this->document->id);
    }

    return new Content(
      markdown: 'content.bank.email.document-updated',
      with: [
        'user_name' => $this->user_name,
        'document' => $document,
        'company_document' => $company_document,
        'status' => $this->status,
        'url' => $this->url,
        'logo' => $this->logo,
      ]
    );
  }

  /**
   * Get the attachments for the message.
   *
   * @return array
   */
  public function attachments()
  {
    return [];
  }
}
