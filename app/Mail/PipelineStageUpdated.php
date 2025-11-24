<?php

namespace App\Mail;

use App\Models\Pipeline;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PipelineStageUpdated extends Mailable
{
  use Queueable, SerializesModels;

  public $pipeline;
  public $newStage;

  public function __construct(Pipeline $pipeline, $newStage, public string $logo)
  {
    $this->pipeline = $pipeline;
    $this->newStage = $newStage;
  }

  /**
   * Get the message envelope.
   *
   * @return \Illuminate\Mail\Mailables\Envelope
   */
  public function envelope()
  {
    $subject = 'Pipeline Stage Updated';

    return new Envelope(subject: $subject);
  }

  /**
   * Get the message content definition.
   *
   * @return \Illuminate\Mail\Mailables\Content
   */
  public function content()
  {
    return new Content(
      markdown: 'content.email.pipeline_stage_updated',
      with: [
        'pipeline' => $this->pipeline,
        'newStage' => $this->newStage,
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
