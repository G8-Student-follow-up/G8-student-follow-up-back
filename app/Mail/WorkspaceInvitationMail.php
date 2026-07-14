<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WorkspaceInvitationMail extends Mailable
{
    use SerializesModels;

    public $invitedBy;
    public $workspace;
    public $url;

    public function __construct(User $invitedBy, Workspace $workspace, string $url)
    {
        $this->invitedBy = $invitedBy;
        $this->workspace = $workspace;
        $this->url = $url;
    }

    public function build()
    {
        return $this->subject("{$this->invitedBy->name} invited you to {$this->workspace->name}")
                    ->view('emails.workspace-invitation');
    }
}
