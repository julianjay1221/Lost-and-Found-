<?php

namespace App\Http\Controllers;

use App\Models\ItemReport;
use App\Services\FoundItemMatchNotifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class AdminReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureAdmin($request);

        $reports = ItemReport::query()
            ->with('user')
            ->when(
                $request->filled('status'),
                fn (Builder $query) => $query->where('status', $request->query('status')),
                fn (Builder $query) => $query->notArchived()
            )
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $stats = [
            'total' => ItemReport::query()->notArchived()->count(),
            'pending' => ItemReport::query()->where('status', ItemReport::STATUS_PENDING)->count(),
            'approved' => ItemReport::query()->where('status', ItemReport::STATUS_APPROVED)->count(),
            'claimed' => ItemReport::query()->whereIn('status', [ItemReport::STATUS_CLAIMED, ItemReport::STATUS_CLOSED])->count(),
            'archived' => ItemReport::query()->where('status', ItemReport::STATUS_ARCHIVED)->count(),
            'rejected' => ItemReport::query()->where('status', ItemReport::STATUS_REJECTED)->count(),
            'blocked_ips' => ItemReport::query()
                ->where('status', ItemReport::STATUS_BLOCKED)
                ->whereNotNull('ip_address')
                ->distinct()
                ->count('ip_address'),
            'spam' => ItemReport::query()->where('is_spam', true)->count(),
            'submissions_24h' => ItemReport::query()->where('created_at', '>=', now()->subDay())->count(),
        ];

        $claimAlerts = ItemReport::query()
            ->with(['user', 'matchedReport.user'])
            ->where('type', ItemReport::TYPE_LOST)
            ->whereNotNull('matched_report_id')
            ->where('status', ItemReport::STATUS_CLAIMED)
            ->latest('claimed_at')
            ->limit(6)
            ->get();

        return view('admin.dashboard', compact('reports', 'stats', 'claimAlerts'));
    }

    public function approve(Request $request, ItemReport $itemReport, FoundItemMatchNotifier $notifier): RedirectResponse
    {
        $this->ensureAdmin($request);

        $wasAlreadyApproved = $itemReport->status === ItemReport::STATUS_APPROVED;

        $itemReport->update([
            'status' => ItemReport::STATUS_APPROVED,
            'is_spam' => false,
            'admin_notes' => $request->input('admin_notes'),
            'reviewed_at' => now(),
        ]);

        $notifiedCount = $wasAlreadyApproved ? 0 : $notifier->notifyLostOwners($itemReport->refresh());

        return back()->with(
            'status',
            $notifiedCount > 0
                ? "Report approved. {$notifiedCount} matching lost item owner(s) were notified."
                : 'Report approved.'
        );
    }

    public function reject(Request $request, ItemReport $itemReport): RedirectResponse
    {
        $this->ensureAdmin($request);

        $itemReport->update([
            'status' => ItemReport::STATUS_REJECTED,
            'admin_notes' => $request->input('admin_notes'),
            'reviewed_at' => now(),
        ]);

        return back()->with('status', 'Report rejected.');
    }

    public function block(Request $request, ItemReport $itemReport): RedirectResponse
    {
        $this->ensureAdmin($request);

        if ($itemReport->photo_path) {
            File::delete(public_path($itemReport->photo_path));
        }

        $itemReport->delete();

        return redirect()
            ->route('admin.dashboard')
            ->with('status', 'Spam report removed from admin history.');
    }

    public function close(Request $request, ItemReport $itemReport): RedirectResponse
    {
        $this->ensureAdmin($request);

        abort_unless($itemReport->status === ItemReport::STATUS_CLAIMED, 403);

        $itemReport->update([
            'status' => ItemReport::STATUS_CLOSED,
            'admin_notes' => $request->input('admin_notes'),
            'closed_at' => now(),
        ]);

        return redirect()
            ->route('admin.dashboard', ['status' => ItemReport::STATUS_CLOSED])
            ->with('status', 'Case closed.');
    }

    public function archive(Request $request, ItemReport $itemReport): RedirectResponse
    {
        $this->ensureAdmin($request);

        abort_unless(
            in_array($itemReport->status, [ItemReport::STATUS_CLAIMED, ItemReport::STATUS_CLOSED], true),
            403
        );

        $itemReport->update([
            'status' => ItemReport::STATUS_ARCHIVED,
            'admin_notes' => $request->input('admin_notes', $itemReport->admin_notes),
            'archived_at' => now(),
        ]);

        return redirect()
            ->route('admin.dashboard')
            ->with('status', 'Report moved to archive history.');
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
