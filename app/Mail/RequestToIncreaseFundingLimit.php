<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class RequestToIncreaseFundingLimit extends Mailable
{
  use Queueable, SerializesModels;

  public $template;
  public $logo;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct(
    public Company $company,
    public float $approved_limit,
    public float $current_exposure,
    public float $pipeline_requests,
    public float $available_limit
  ) {
    $this->template = EmailTemplate::where('name', 'Request to increase funding limit by Seller')->first();
    $this->logo = $company->bank->adminConfiguration()->exists()
      ? $company->bank->adminConfiguration->logo
      : config('app.url') . '/assets/img/branding/logo-name.png';
  }

  /**
   * Get the message envelope.
   *
   * @return \Illuminate\Mail\Mailables\Envelope
   */
  public function envelope()
  {
    $subject = '';
    $data = [];
    $data['company_name'] = $this->company->name;

    if ($this->template) {
      $subject = $this->template->subject;
      foreach ($data as $key => $val) {
        $subject = str_replace('{' . $key . '}', $val, $subject);
      }
    } else {
      $subject = $this->company->name . ' requested for funding limit to be increased.';
    }

    return new Envelope(subject: $subject);
  }

  /**
   * Get the message content definition.
   *
   * @return \Illuminate\Mail\Mailables\Content
   */
  public function content()
  {
    $data = [];
    $data['company_name'] = $this->company->name;
    $data['approve_limit'] = number_format($this->approved_limit, 2);
    $data['current_exposure'] = number_format($this->current_exposure, 2);
    $data['pipeline_request'] = number_format($this->pipeline_requests, 2);
    $data['available_limit'] = number_format($this->available_limit, 2);
    $data['table'] = '';

    $mail_text = $this->template ? $this->template->body : '';

    foreach ($data as $key => $val) {
      $mail_text = str_replace('{' . $key . '}', $val, $mail_text);
    }

    return new Content(
      markdown: 'content.email.request-to-increase-funding-limit',
      with: [
        'template' => $this->template,
        'mail_text' => $mail_text,
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
