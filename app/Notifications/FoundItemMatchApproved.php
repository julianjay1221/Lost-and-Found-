<?php

namespace App\Notifications;

use App\Models\ItemReport;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FoundItemMatchApproved extends Notification
{
    public function __construct(
        private readonly ItemReport $lostReport,
        private readonly ItemReport $foundReport,
    ) {
    }

    public function via(object $notifiable): array
    {
        if (! $notifiable instanceof AnonymousNotifiable) {
            return ['database'];
        }

        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Possible match found for your lost item')
            ->greeting('Hello ' . $this->lostReport->contact_name . ',')
            ->line('A found item was approved and may match your lost item report.')
            ->line('Lost item: ' . $this->lostReport->item_name)
            ->line('Found item: ' . $this->foundReport->item_name)
            ->line('Pick Up Location: ' . $this->foundReport->location)
            ->line('Finder name: ' . $this->foundReport->contact_name);

        if ($this->foundReport->contact_phone) {
            $message->line('Finder phone: ' . $this->foundReport->contact_phone);
        }

        if ($this->foundReport->contact_email) {
            $message->line('Finder email: ' . $this->foundReport->contact_email);
        }

        return $message
            ->line('This notice was sent using the contact details available for your lost item report.')
            ->action('View Your Lost Report', route('reports.show', $this->lostReport))
            ->line('Please review the match and claim the item if it belongs to you.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Found item match approved',
            'message' => "{$this->foundReport->item_name} may match your lost {$this->lostReport->item_name}. Finder: {$this->finderContactSummary()}.",
            'lost_report_id' => $this->lostReport->id,
            'found_report_id' => $this->foundReport->id,
            'url' => route('reports.show', $this->lostReport),
        ];
    }

    private function finderContactSummary(): string
    {
        $details = [$this->foundReport->contact_name];

        if ($this->foundReport->contact_phone) {
            $details[] = 'Phone: ' . $this->foundReport->contact_phone;
        }

        if ($this->foundReport->contact_email) {
            $details[] = 'Email: ' . $this->foundReport->contact_email;
        }

        return implode(', ', $details);
    }
}
