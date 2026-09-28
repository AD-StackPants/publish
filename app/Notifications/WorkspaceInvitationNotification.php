<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceInvitationNotification extends Notification
{
    public function __construct(
        public readonly Organization $organization,
        public readonly string $role,
        public readonly ?User $inviter = null
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $inviterName = $this->inviter ? $this->inviter->name : 'A team administrator';
        $orgName = $this->organization->name;
        $workspaceUrl = url('/w/'.$this->organization->slug.'/posts');

        return (new MailMessage)
            ->subject("You've been invited to join {$orgName} on SocialSyncPost")
            ->greeting('Hello!')
            ->line("{$inviterName} has invited you to join the **{$orgName}** workspace on SocialSyncPost as **{$this->role}**.")
            ->line('Collaborate on drafting, reviewing, scheduling, and publishing social posts across all connected channels.')
            ->action('Accept Invitation & Open Workspace', $workspaceUrl)
            ->line('If you already have an account, sign in with this email to access the workspace.')
            ->line('If you did not expect this invitation, you can safely ignore this email.');
    }
}
