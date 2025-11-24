<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\Program;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProgramMappingChanged extends Mailable
{
  use Queueable, SerializesModels;

  public EmailTemplate $template;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct(
    public Program $program,
    public Company $company,
    public string $user_name,
    public string $logo
  ) {
    $this->template = EmailTemplate::where('name', 'Program Mapping Changed')->first();
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
    $data['anchor_name'] = $this->program->anchor->name;
    $data['seller_name'] = $this->company->name;

    if ($this->template) {
      $subject = $this->template->subject;
      foreach ($data as $key => $val) {
        $subject = str_replace('{' . $key . '}', $val, $subject);
      }
    } else {
      $subject =
        'Program Mapping between ' . $this->program->anchor->name . ' & ' . $this->company->name . ' has been updated';
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
    $data['{anchor_name}'] = $this->program->anchor->name;
    $data['{seller_company}'] = $this->company->name;
    $data['{seller_name}'] = $this->company->name;
    $data['{maker_name}'] = $this->user_name;

    $mail_text = $this->template ? $this->template->body : null;

    foreach ($data as $key => $val) {
      $mail_text = str_replace(
        $key,
        gettype($val) == 'integer' || gettype($val) == 'double' ? number_format($val) : $val,
        $mail_text
      );
    }
    return new Content(
      markdown: 'content.bank.email.program-mapping-changed',
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
