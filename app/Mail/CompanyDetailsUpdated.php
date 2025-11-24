<?php

namespace App\Mail;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompanyDetailsUpdated extends Mailable
{
  use Queueable, SerializesModels;

  public $company_changes = array();

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct(public Company $company, public string $name, public string $url, public string $logo = '')
  {
    $this->company_changes = $company->proposedUpdate->changes;
  }

  /**
   * Get the message envelope.
   *
   * @return \Illuminate\Mail\Mailables\Envelope
   */
  public function envelope()
  {
      return new Envelope(
          subject: 'Company Details Updated',
      );
  }

  /**
   * Get the message content definition.
   *
   * @return \Illuminate\Mail\Mailables\Content
   */
  public function content()
  {
      return new Content(
          markdown: 'content.bank.email.company-details-updated',
          with: [
            'company' => $this->company,
            'changes' => $this->company_changes,
            'name' => $this->name,
            'url' => $this->url,
            'logo' => $this->logo
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
