<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Board;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BoardInvitationMail extends Mailable
{
    use SerializesModels;

    public $invitedBy;
    public $board;
    public $url;

    public function __construct(User $invitedBy, Board $board, string $url)
    {
        $this->invitedBy = $invitedBy;
        $this->board = $board;
        $this->url = $url;
    }

    public function build()
    {
        return $this->subject("{$this->invitedBy->name} invited you to {$this->board->title}")
                    ->view('emails.board-invitation');
    }
}
