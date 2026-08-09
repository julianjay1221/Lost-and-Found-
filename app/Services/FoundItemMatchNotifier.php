<?php

namespace App\Services;

use App\Models\ItemReport;
use App\Notifications\FoundItemMatchApproved;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class FoundItemMatchNotifier
{
    public function __construct(private readonly SmsSender $smsSender)
    {
    }

    public function notifyLostOwners(ItemReport $foundReport): int
    {
        if ($foundReport->type !== ItemReport::TYPE_FOUND || $foundReport->status !== ItemReport::STATUS_APPROVED) {
            return 0;
        }

        $notified = 0;

        $this->matchingLostReports($foundReport)
            ->each(function (ItemReport $lostReport) use ($foundReport, &$notified) {
                $contactSent = false;
                $lostOwner = $lostReport->user;
                $context = [
                    'lost_report_id' => $lostReport->id,
                    'found_report_id' => $foundReport->id,
                ];

                if ($lostOwner) {
                    $lostOwner->notify(new FoundItemMatchApproved($lostReport, $foundReport));
                }

                if ($phone = $this->notificationPhone($lostReport)) {
                    $contactSent = $this->smsSender->send(
                        $phone,
                        $this->smsMessage($lostReport, $foundReport),
                        $context
                    ) || $contactSent;
                }

                if ($email = $this->notificationEmail($lostReport)) {
                    Notification::route('mail', $email)
                        ->notify(new FoundItemMatchApproved($lostReport, $foundReport));

                    $contactSent = true;
                }

                if ($contactSent) {
                    $notified++;
                }
            });

        return $notified;
    }

    private function smsMessage(ItemReport $lostReport, ItemReport $foundReport): string
    {
        return Str::limit(
            "Found item match: {$foundReport->item_name} may match your lost {$lostReport->item_name}. Pick up: {$foundReport->location}. Finder {$this->finderContactDetails($foundReport)}.",
            300
        );
    }

    private function finderContactDetails(ItemReport $foundReport): string
    {
        $details = [
            'Name: ' . $foundReport->contact_name,
        ];

        if ($foundReport->contact_phone) {
            $details[] = 'Phone: ' . $foundReport->contact_phone;
        }

        if ($foundReport->contact_email) {
            $details[] = 'Email: ' . $foundReport->contact_email;
        }

        return implode(', ', $details);
    }

    private function notificationPhone(ItemReport $lostReport): ?string
    {
        return $lostReport->contact_phone ?: $lostReport->user?->contact_phone;
    }

    private function notificationEmail(ItemReport $lostReport): ?string
    {
        return $lostReport->contact_email ?: $lostReport->user?->email;
    }

    private function matchingLostReports(ItemReport $foundReport)
    {
        return ItemReport::query()
            ->with('user')
            ->where('id', '!=', $foundReport->id)
            ->where('type', ItemReport::TYPE_LOST)
            ->whereIn('status', [ItemReport::STATUS_PENDING, ItemReport::STATUS_APPROVED])
            ->where(function (Builder $query) use ($foundReport) {
                $query
                    ->where('category', $foundReport->category)
                    ->orWhere('item_name', 'like', '%' . $foundReport->item_name . '%')
                    ->orWhere('location', 'like', '%' . $foundReport->location . '%');
            })
            ->latest('updated_at')
            ->get();
    }
}
