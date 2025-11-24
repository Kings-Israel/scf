<?php

namespace App\Mail;

use App\Models\Program;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProgramCreated extends Mailable
{
  use Queueable, SerializesModels;

  public EmailTemplate $template;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct(public Program $program, public string $logo)
  {
    $this->template = EmailTemplate::where('name', 'Program Created')->first();
  }

  /**
   * Get the message envelope.
   *
   * @return \Illuminate\Mail\Mailables\Envelope
   */
  public function envelope()
  {
    $subject = '';
    $data = array();
    $data['program_name'] = $this->program->name;

    if ($this->template) {
      $subject = $this->template->subject;
      foreach ($data as $key => $val) {
        $subject = str_replace('{' . $key . '}', $val, $subject);
      }
    } else {
      $subject = 'New Program - ' . $this->program->name . ' created and awaiting approval';
    }

    return new Envelope(
      subject: $subject,
    );
  }

  /**
   * Get the message content definition.
   *
   * @return \Illuminate\Mail\Mailables\Content
   */
  public function content()
  {
    $data = array();
    $data['{program_name}'] = $this->program->name;
    $data['{maker_name}'] = User::find($this->program->created_by)->name;

    $mail_text = $this->template ? $this->template->body : NULL;

    foreach ($data as $key => $val) {
      $mail_text = str_replace($key, ' ' . $val, $mail_text);
    }

    return new Content(
      markdown: 'content.bank.email.program-created',
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
