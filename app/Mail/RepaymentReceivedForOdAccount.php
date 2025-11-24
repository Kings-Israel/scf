<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class RepaymentReceivedForOdAccount extends Mailable
{
  use Queueable, SerializesModels;

  public $template;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct(public string $od_account, public float $amount, public string $distributor_name, public string $debit_from, public string $name, public string $logo)
  {
    $this->template = EmailTemplate::where('name', 'Repayment received for OD A/C')->first();
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
    $data['od_account_number'] = $this->od_account;

    if ($this->template) {
      $subject = $this->template->subject;
      foreach ($data as $key => $val) {
        $subject = str_replace('{' . $key . '}', $val, $subject);
      }
    } else {
      $subject = 'Repayment received for OD A/C';
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
    $data['{amount}'] = $this->amount;
    $data['{od_account_number}'] = $this->od_account;
    $data['{distributor_name}'] = $this->distributor_name;
    $data['{debit_ac_no}'] = $this->debit_from;
    // if (count($this->particulars) > 0) {
    //   $data['{particulars}'] = $this->particulars;
    // } else {
    // }
    $data['Particulars: {particulars}<br>'] = '';
    $data['{name}'] = $this->name;

    $mail_text = $this->template ? $this->template->body : NULL;

    foreach ($data as $key => $val) {
      $mail_text = str_replace($key, (gettype($val) == 'integer' || gettype($val) == 'double') ? ' ' . number_format($val) : ' ' . $val, $mail_text);
    }
    return new Content(
      markdown: 'content.bank.email.repayment-received',
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
