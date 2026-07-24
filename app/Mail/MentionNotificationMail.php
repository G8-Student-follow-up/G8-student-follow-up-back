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
    public $commentMessage;
    public $board;

    public function __construct(User $mentionedBy, Card $card, string $commentMessage)
    {
        $this->mentionedBy = $mentionedBy;
        $this->card = $card;
        $this->commentMessage = $commentMessage;
        $this->board = $card->board;
    }

    public function build()
    {
        return $this->subject("{$this->mentionedBy->name} mentioned you in a comment")
                    ->view('emails.mention-notification');
    }
}