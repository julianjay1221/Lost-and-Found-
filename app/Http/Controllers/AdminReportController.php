<?php

namespace App\Http\Controllers;

use App\Models\ItemReport;
use App\Services\FoundItemMatchNotifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->whereNull('claim_confirmed_at')
            ->latest('claimed_at')
            ->limit(6)
            ->get();

        return view('admin.dashboard', compact('reports', 'stats', 'claimAlerts'));
    }

    public function approve(Request $request, ItemReport $itemReport, FoundItemMatchNotifier $notifier): RedirectResponse|JsonResponse
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
        $resolvedCount = $wasAlreadyApproved ? 0 : $notifier->markMatchingLostReportsFound($itemReport->refresh());

        $message = $resolvedCount > 0
                ? "Report approved. {$resolvedCount} matching lost item report(s) marked as found."
                : ($notifiedCount > 0
                    ? "Report approved. {$notifiedCount} matching lost item owner(s) were notified."
                    : 'Report approved.');

        return $this->actionResponse(
            $request,
            $itemReport,
            ItemReport::STATUS_APPROVED,
            'Report approved successfully.',
            back()->with('status', $message)
        );
    }

    public function reject(Request $request, ItemReport $itemReport): RedirectResponse|JsonResponse
    {
        $this->ensureAdmin($request);

        $itemReport->update([
            'status' => ItemReport::STATUS_REJECTED,
            'admin_notes' => $request->input('admin_notes'),
            'reviewed_at' => now(),
        ]);

        return $this->actionResponse(
            $request,
            $itemReport,
            ItemReport::STATUS_REJECTED,
            'Report rejected successfully.',
            back()->with('status', 'Report rejected.')
        );
    }

    public function block(Request $request, ItemReport $itemReport): RedirectResponse|JsonResponse
    {
        $this->ensureAdmin($request);

        $itemReport->update([
            'status' => ItemReport::STATUS_BLOCKED,
            'is_spam' => true,
            'admin_notes' => $request->input('admin_notes', $itemReport->admin_notes),
            'blocked_at' => now(),
            'reviewed_at' => now(),
        ]);

        return $this->actionResponse(
            $request,
            $itemReport,
            ItemReport::STATUS_BLOCKED,
            'Report marked as spam successfully.',
            redirect()
                ->route('admin.dashboard', ['status' => ItemReport::STATUS_BLOCKED])
                ->with('status', 'Spam report moved to spam tab.')
        );
    }

    public function close(Request $request, ItemReport $itemReport): RedirectResponse|JsonResponse
    {
        $this->ensureAdmin($request);

        abort_unless(in_array($itemReport->status, [ItemReport::STATUS_FOUND, ItemReport::STATUS_CLAIMED], true), 403);

        $itemReport->update([
            'status' => ItemReport::STATUS_CLOSED,
            'admin_notes' => $request->input('admin_notes'),
            'closed_at' => now(),
        ]);

        return $this->actionResponse(
            $request,
            $itemReport,
            ItemReport::STATUS_CLOSED,
            'Case closed successfully.',
            redirect()
                ->route('admin.dashboard', ['status' => ItemReport::STATUS_CLOSED])
                ->with('status', 'Case closed.')
        );
    }

    public function confirmClaim(Request $request, ItemReport $itemReport): RedirectResponse|JsonResponse
    {
        $this->ensureAdmin($request);

        abort_unless($itemReport->type === ItemReport::TYPE_LOST, 403);
        abort_unless($itemReport->status === ItemReport::STATUS_CLAIMED, 403);
        abort_unless($itemReport->matchedReport !== null, 403);

        $confirmedAt = now();

        $itemReport->update([
            'claim_confirmed_at' => $confirmedAt,
            'admin_notes' => $request->input('admin_notes', $itemReport->admin_notes),
        ]);

        $itemReport->matchedReport->update([
            'status' => ItemReport::STATUS_CLAIMED,
            'claimed_at' => $itemReport->matchedReport->claimed_at ?? $itemReport->claimed_at ?? $confirmedAt,
            'claim_confirmed_at' => $confirmedAt,
        ]);

        return $this->actionResponse(
            $request,
            $itemReport,
            ItemReport::STATUS_CLAIMED,
            'Claim confirmed successfully.',
            redirect()
                ->route('admin.dashboard')
                ->with('status', 'Claim confirmed. The claimed found item is now visible on the public dashboard.')
        );
    }

    public function archive(Request $request, ItemReport $itemReport): RedirectResponse|JsonResponse
    {
        $this->ensureAdmin($request);

        abort_unless(
            in_array($itemReport->status, [ItemReport::STATUS_FOUND, ItemReport::STATUS_CLAIMED, ItemReport::STATUS_CLOSED], true),
            403
        );

        $itemReport->update([
            'status' => ItemReport::STATUS_ARCHIVED,
            'admin_notes' => $request->input('admin_notes', $itemReport->admin_notes),
            'archived_at' => now(),
        ]);

        return $this->actionResponse(
            $request,
            $itemReport,
            ItemReport::STATUS_ARCHIVED,
            'Report archived successfully.',
            redirect()
                ->route('admin.dashboard')
                ->with('status', 'Report moved to archive history.')
        );
    }

    private function actionResponse(
        Request $request,
        ItemReport $itemReport,
        string $targetStatus,
        string $message,
        RedirectResponse $fallback
    ): RedirectResponse|JsonResponse {
        if (! $request->expectsJson()) {
            return $fallback;
        }

        return response()->json([
            'message' => $message,
            'target_status' => $targetStatus,
            'target_url' => route('admin.dashboard', ['status' => $targetStatus]),
            'report' => [
                'id' => $itemReport->id,
                'status' => $itemReport->status,
                'is_spam' => $itemReport->is_spam,
            ],
        ]);
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
