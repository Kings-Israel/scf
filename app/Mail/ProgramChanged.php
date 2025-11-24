<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\Program;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProgramChanged extends Mailable
{
  use Queueable, SerializesModels;

  public $template;
  public $program;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct(string $program, public string $url, public string $name, public string $type, public string $logo = '')
  {
    $this->program = Program::find($program);

    $this->template = EmailTemplate::where('name', 'Program Changed')->first();
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
        $subject = str_replace('{'.$key.'}', $val, $subject);
      }
    } else {
      $subject = 'Program Changed';
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
    $data['program_name'] = $this->program->name;
    $data['maker_name'] = $this->name;

    $mail_text = $this->template ? $this->template->body : '';

    foreach ($data as $key => $val) {
      $mail_text = str_replace('{'.$key.'}', (gettype($val) == 'integer' || gettype($val) == 'double') ? number_format($val) : $val, $mail_text);
    }

    return new Content(
      markdown: 'content.bank.email.program-changed',
      with: [
        'program' => $this->program,
        'mail_text' => $mail_text,
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
