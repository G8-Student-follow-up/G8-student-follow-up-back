<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Card;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MentionNotificationMail extends Mailable
{
    use SerializesModels;

    public $mentionedBy;
    public $card;
    public $commentSnippet;
    public $actionUrl;

    public function __construct(User $mentionedBy, Card $card, string $commentSnippet, string $actionUrl)
    {
        $this->mentionedBy = $mentionedBy;
        $this->card = $card;
        $this->commentSnippet = $commentSnippet;
        $this->actionUrl = $actionUrl;
    }

    public function build()
    {
        return $this->subject("{$this->mentionedBy->name} mentioned you in {$this->card->title}")
                    ->view('emails.mention-notification');
    }
}
