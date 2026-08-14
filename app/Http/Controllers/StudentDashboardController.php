<?php

namespace App\Http\Controllers;

use App\Models\ItemReport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless($request->user()?->isStudent(), 403);

        $reports = ItemReport::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->limit(8)
            ->get();

        $stats = [
            'total' => ItemReport::query()->where('user_id', $request->user()->id)->count(),
            'pending' => ItemReport::query()->where('user_id', $request->user()->id)->where('status', ItemReport::STATUS_PENDING)->count(),
            'approved' => ItemReport::query()->where('user_id', $request->user()->id)->where('status', ItemReport::STATUS_APPROVED)->count(),
            'claimed' => ItemReport::query()->where('user_id', $request->user()->id)->claimedOrClosed()->count(),
        ];

        $matchAlerts = $this->foundMatchAlerts($request->user()->id);
        $claimedAlerts = $this->claimedLostReportAlerts($request->user()->id);

        return view('student.dashboard', compact('reports', 'stats', 'matchAlerts', 'claimedAlerts'));
    }

    private function foundMatchAlerts(int $userId): Collection
    {
        $lostReports = ItemReport::query()
            ->where('user_id', $userId)
            ->where('type', ItemReport::TYPE_LOST)
            ->whereIn('status', [ItemReport::STATUS_APPROVED, ItemReport::STATUS_FOUND])
            ->latest('updated_at')
            ->get();

        if ($lostReports->isEmpty()) {
            return collect();
        }

        return $lostReports
            ->flatMap(function (ItemReport $lostReport) {
                return ItemReport::query()
                    ->public()
                    ->where('type', ItemReport::TYPE_FOUND)
                    ->where(function (Builder $query) use ($lostReport) {
                        $query
                            ->where('category', $lostReport->category)
                            ->orWhere('item_name', 'like', '%' . $lostReport->item_name . '%')
                            ->orWhere('location', 'like', '%' . $lostReport->location . '%');
                    })
                    ->latest('updated_at')
                    ->limit(3)
                    ->get()
                    ->map(fn (ItemReport $foundReport) => [
                        'lost' => $lostReport,
                        'found' => $foundReport,
                    ]);
            })
            ->unique(fn (array $alert) => $alert['lost']->id . '-' . $alert['found']->id)
            ->take(6)
            ->values();
    }

    private function claimedLostReportAlerts(int $userId): Collection
    {
        $foundReportIds = ItemReport::query()
            ->where('user_id', $userId)
            ->where('type', ItemReport::TYPE_FOUND)
            ->latest('updated_at')
            ->pluck('id');

        if ($foundReportIds->isEmpty()) {
            return collect();
        }

        return ItemReport::query()
            ->with('matchedReport')
            ->where('type', ItemReport::TYPE_LOST)
            ->where('user_id', '!=', $userId)
            ->whereIn('matched_report_id', $foundReportIds)
            ->claimedOrClosed()
            ->latest('claimed_at')
            ->limit(6)
            ->get()
            ->map(fn (ItemReport $lostReport) => [
                'found' => $lostReport->matchedReport,
                'lost' => $lostReport,
            ])
            ->filter(fn (array $alert) => $alert['found'] !== null)
            ->take(6)
            ->values();
    }
}
