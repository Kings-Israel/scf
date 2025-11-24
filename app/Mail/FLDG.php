<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\ProgramVendorConfiguration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Contracts\Queue\ShouldQueue;

class FLDG extends Mailable
{
  use Queueable, SerializesModels;

  public $template;
  public $logo;

  /**
   * Create a new message instance.
   *
   * @return void
   */
  public function __construct(public Company $company, public array $dealers)
  {
    $this->template = EmailTemplate::where('name', 'FLDG')->first();
    $this->logo = $company->bank->adminConfiguration()->exists() ? $company->bank->adminConfiguration->logo : config('app.url') . '/assets/img/branding/logo-name.png';
  }

  /**
   * Get the message envelope.
   *
   * @return \Illuminate\Mail\Mailables\Envelope
   */
  public function envelope()
  {
    $subject = $this->template?->subject;

    return new Envelope(
      subject: $subject ? $subject : 'FLDG',
    );
  }

  /**
   * Get the message content definition.
   *
   * @return \Illuminate\Mail\Mailables\Content
   */
  public function content()
  {
    $table_data = '';

    foreach ($this->dealers as $dealer) {
      $od_account = ProgramVendorConfiguration::whereHas('program', function ($query) {
        $query->whereHas('anchor', function ($query) {
          $query->where('name', $this->company->name);
        })
          ->whereHas('bank', function ($query) {
            $query->where('name', $this->company->bank->name);
          });
      })
        ->where('company_id', $dealer)
        ->first();

      $table_data .= '<tr><td>' . $od_account->company->name . '</td><td>' . $od_account->payment_account_number . '</td></tr>';
    }

    $data = array();
    $data['date'] = now()->format('d M Y');
    $data['table'] =
      '<table>
    <thead>
    <tr>
    <th>Dealer</th>
    <th>Loan/OD Account</th>
    </tr>
    </thead>
    <tbody>
    ' . $table_data . '
    </tbody>
    </table>';

    $mail_text = $this->template ? $this->template->body : '';

    foreach ($data as $key => $val) {
      $mail_text = str_replace('{' . $key . '}', (gettype($val) == 'integer' || gettype($val) == 'double') ? ' ' . number_format($val) : ' ' . $val, $mail_text);
    }

    return new Content(
      markdown: 'content.email.fldg',
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
