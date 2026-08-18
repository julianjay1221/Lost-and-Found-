<?php

namespace App\Http\Controllers;

use App\Models\ItemReport;
use App\Services\FoundItemMatchNotifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class AdminReportController extends Controller
{
    private const ADMIN_STATUS_HISTORY = 'history';
    private const ADMIN_STATUS_SPAM = 'spam';

    public function index(Request $request): View
    {
        $this->ensureAdmin($request);

        $requestedStatus = $request->query('status');
        $normalizedStatus = $this->normalizeDashboardStatus($requestedStatus);

        $reports = ItemReport::query()
            ->with('user')
            ->when(
                $request->filled('status'),
                fn (Builder $query) => $query->where(function (Builder $query) use ($normalizedStatus) {
                    if ($normalizedStatus === ItemReport::STATUS_BLOCKED) {
                        $query->where('status', ItemReport::STATUS_BLOCKED)
                            ->where('is_spam', true);

                        return;
                    }

                    $query->where('status', $normalizedStatus ?? ItemReport::STATUS_PENDING);
                }),
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
            'history' => ItemReport::query()->where('status', ItemReport::STATUS_ARCHIVED)->count(),
            'blocked_ips' => ItemReport::query()
                ->where('status', ItemReport::STATUS_BLOCKED)
                ->whereNotNull('ip_address')
                ->distinct()
                ->count('ip_address'),
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

        $this->deleteReport($itemReport);

        return $this->deleteActionResponse(
            $request,
            'Report rejected and deleted successfully.',
            'Report rejected and deleted.'
        );
    }

    public function block(Request $request, ItemReport $itemReport): RedirectResponse|JsonResponse
    {
        $this->ensureAdmin($request);

        $this->deleteReport($itemReport);

        return $this->deleteActionResponse(
            $request,
            'Spam report deleted successfully.',
            'Spam report deleted.'
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
            self::ADMIN_STATUS_HISTORY,
            'Report moved to history successfully.',
            redirect()
                ->route('admin.dashboard', ['status' => self::ADMIN_STATUS_HISTORY])
                ->with('status', 'Report moved to history successfully.')
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
            'target_status' => $this->presentDashboardStatus($targetStatus),
            'target_url' => route('admin.dashboard', ['status' => $this->presentDashboardStatus($targetStatus)]),
            'report' => [
                'id' => $itemReport->id,
                'status' => $itemReport->status,
                'is_spam' => $itemReport->is_spam,
            ],
        ]);
    }

    private function deleteActionResponse(
        Request $request,
        string $message,
        string $fallbackMessage
    ): RedirectResponse|JsonResponse {
        $returnStatus = $this->dashboardReturnStatus($request->input('return_status'));
        $targetUrl = $this->dashboardUrlForStatus($returnStatus);

        if (! $request->expectsJson()) {
            return redirect($targetUrl)->with('status', $fallbackMessage);
        }

        return response()->json([
            'message' => $message,
            'target_status' => $returnStatus ?? 'all',
            'target_url' => $targetUrl,
            'report' => [
                'deleted' => true,
            ],
        ]);
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }

    private function normalizeDashboardStatus(?string $status): ?string
    {
        return match ($status) {
            self::ADMIN_STATUS_HISTORY => ItemReport::STATUS_ARCHIVED,
            self::ADMIN_STATUS_SPAM => ItemReport::STATUS_BLOCKED,
            default => $status,
        };
    }

    private function presentDashboardStatus(string $status): string
    {
        return match ($status) {
            ItemReport::STATUS_ARCHIVED => self::ADMIN_STATUS_HISTORY,
            ItemReport::STATUS_BLOCKED => self::ADMIN_STATUS_SPAM,
            default => $status,
        };
    }

    private function deleteReport(ItemReport $itemReport): void
    {
        if ($itemReport->photo_path) {
            File::delete(public_path($itemReport->photo_path));
        }

        $itemReport->delete();
    }

    private function dashboardReturnStatus(?string $status): ?string
    {
        $status = is_string($status) ? trim($status) : null;

        return in_array($status, ['all', ItemReport::STATUS_PENDING, ItemReport::STATUS_APPROVED, ItemReport::STATUS_FOUND, ItemReport::STATUS_CLAIMED, ItemReport::STATUS_CLOSED, self::ADMIN_STATUS_HISTORY], true)
            ? $status
            : null;
    }

    private function dashboardUrlForStatus(?string $status): string
    {
        return $status && $status !== 'all'
            ? route('admin.dashboard', ['status' => $status])
            : route('admin.dashboard');
    }
}
