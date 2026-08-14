<?php

namespace App\Notifications;

use App\Models\ItemReport;
use App\Models\User;
use Illuminate\Notifications\Notification;

class ItemClaimed extends Notification
{
    public function __construct(
        private readonly ItemReport $lostReport,
        private readonly ItemReport $foundReport,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $isAdmin = $notifiable instanceof User && $notifiable->isAdmin();

        return [
            'title' => $isAdmin ? 'Item claim needs confirmation' : 'Your found item was claimed',
            'message' => $this->messageFor($isAdmin),
            'lost_report_id' => $this->lostReport->id,
            'found_report_id' => $this->foundReport->id,
            'claimant_id' => $this->lostReport->user_id,
            'finder_id' => $this->foundReport->user_id,
            'url' => $isAdmin
                ? route('admin.dashboard', ['status' => ItemReport::STATUS_CLAIMED])
                : route('reports.show', $this->foundReport),
        ];
    }

    private function messageFor(bool $isAdmin): string
    {
        if ($isAdmin) {
            return "{$this->lostReport->contact_name} claimed {$this->foundReport->item_name}. Please confirm the claim.";
        }

        return "{$this->lostReport->contact_name} claimed your found {$this->foundReport->item_name}. Admin will confirm the claim.";
    }
}
