<?php

namespace App\Http\Controllers;

use App\Models\ItemCategory;
use App\Models\ItemReport;
use App\Models\User;
use App\Notifications\ItemClaimed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportController extends Controller
{
    private const CUSTOM_CATEGORY_VALUE = '__custom_category__';

    public function index(Request $request): View
    {
        $reports = ItemReport::query()
            ->public()
            ->when($request->filled('type'), fn (Builder $query) => $query->where('type', $request->query('type')))
            ->when($request->filled('category'), fn (Builder $query) => $query->where('category', $request->query('category')))
            ->when($request->filled('q'), function (Builder $query) use ($request) {
                $term = '%' . $request->query('q') . '%';

                $query->where(function (Builder $query) use ($term) {
                    $query
                        ->where('item_name', 'like', $term)
                        ->orWhere('location', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->latest('updated_at')
            ->paginate(9)
            ->withQueryString();

        $categories = $this->categoryOptions();

        $stats = [
            'lost' => ItemReport::query()->public()->where('type', ItemReport::TYPE_LOST)->count(),
            'found' => ItemReport::query()->public()->where('type', ItemReport::TYPE_FOUND)->count(),
            'claimed' => ItemReport::query()->claimedOrClosed()->count(),
        ];
        $publicBoardVersion = ItemReport::query()->public()->max('updated_at');

        return view('reports.index', compact('reports', 'categories', 'stats', 'publicBoardVersion'));
    }

    public function publicReportStatus(Request $request): JsonResponse
    {
        $ids = collect($request->query('ids', []))
            ->filter(fn ($id) => filter_var($id, FILTER_VALIDATE_INT) !== false)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->take(12);

        return response()->json([
            'public_ids' => ItemReport::query()
                ->public()
                ->whereKey($ids)
                ->pluck('id')
                ->values(),
            'latest_updated_at' => ItemReport::query()->public()->max('updated_at'),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->isStudent(), 403);

        $categories = $this->categoryOptions($request->user()->id);
        $customCategoryValue = self::CUSTOM_CATEGORY_VALUE;

        return view('reports.create', compact('categories', 'customCategoryValue'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isStudent(), 403);

        $request->merge([
            'item_name' => is_string($request->input('item_name')) ? trim($request->input('item_name')) : $request->input('item_name'),
            'category' => is_string($request->input('category')) ? trim($request->input('category')) : $request->input('category'),
            'category_custom' => is_string($request->input('category_custom')) ? trim($request->input('category_custom')) : $request->input('category_custom'),
            'contact_email' => is_string($request->input('contact_email')) ? trim($request->input('contact_email')) : $request->input('contact_email'),
            'contact_phone' => is_string($request->input('contact_phone')) ? trim($request->input('contact_phone')) : $request->input('contact_phone'),
        ]);

        $data = $request->validate([
            'type' => ['required', Rule::in([ItemReport::TYPE_LOST, ItemReport::TYPE_FOUND])],
            'item_name' => ['nullable', 'string', 'max:120'],
            'category' => ['required', 'string', 'max:80'],
            'category_custom' => [
                'nullable',
                Rule::requiredIf($request->input('category') === self::CUSTOM_CATEGORY_VALUE),
                'string',
                'max:80',
                Rule::notIn([self::CUSTOM_CATEGORY_VALUE]),
            ],
            'happened_at' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:1000'],
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_phone' => ['required', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'photo' => ['nullable', 'image', 'max:4096'],
        ]);

        if ($request->hasFile('photo')) {
            File::ensureDirectoryExists(public_path('uploads/item-reports'));

            $photo = $request->file('photo');
            $fileName = Str::uuid() . '.' . $photo->extension();
            $photo->move(public_path('uploads/item-reports'), $fileName);

            $data['photo_path'] = 'uploads/item-reports/' . $fileName;
        }

        if ($data['category'] === self::CUSTOM_CATEGORY_VALUE) {
            $data['category'] = $data['category_custom'];
        }

        ItemCategory::findOrCreateByName($data['category']);

        unset($data['photo']);
        unset($data['category_custom']);

        $data['user_id'] = $request->user()->id;
        $data['status'] = ItemReport::STATUS_PENDING;
        $data['ip_address'] = $request->ip();

        $report = ItemReport::create($data);

        return redirect()
            ->route('reports.show', $report)
            ->with('status', 'Report submitted for admin review.');
    }

    public function show(Request $request, ItemReport $itemReport): View
    {
        $claimableLostReport = $request->user()->isStudent()
            ? $this->claimableLostReportForFound($request->user()->id, $itemReport)
            : null;

        abort_unless(
            $itemReport->user_id === $request->user()->id || $request->user()->isAdmin() || $claimableLostReport !== null,
            403
        );

        $canSeePotentialMatches = $request->user()->isStudent()
            && $itemReport->user_id === $request->user()->id
            && $itemReport->type === ItemReport::TYPE_LOST
            && in_array($itemReport->status, [ItemReport::STATUS_APPROVED, ItemReport::STATUS_FOUND], true);

        $matches = $canSeePotentialMatches ? $this->matchingReports($itemReport) : collect();

        return view('reports.show', compact('itemReport', 'matches', 'canSeePotentialMatches', 'claimableLostReport'));
    }

    public function claim(Request $request, ItemReport $itemReport): RedirectResponse
    {
        abort_unless($request->user()->isStudent(), 403);
        abort_unless($itemReport->user_id === $request->user()->id, 403);
        abort_unless($itemReport->type === ItemReport::TYPE_LOST, 403);
        abort_unless(in_array($itemReport->status, [ItemReport::STATUS_APPROVED, ItemReport::STATUS_FOUND], true), 403);

        $data = $request->validate([
            'matched_report_id' => ['required', 'integer'],
        ]);

        $matchedReport = $this->matchingReportsQuery($itemReport)
            ->where('status', ItemReport::STATUS_APPROVED)
            ->whereKey($data['matched_report_id'])
            ->first();

        abort_unless($matchedReport, 403);

        $claimedAt = now();

        DB::transaction(function () use ($itemReport, $matchedReport, $claimedAt) {
            $itemReport->update([
                'status' => ItemReport::STATUS_CLAIMED,
                'claimed_at' => $claimedAt,
                'claim_confirmed_at' => null,
                'matched_report_id' => $matchedReport->id,
            ]);

            $matchedReport->update([
                'status' => ItemReport::STATUS_CLAIMED,
                'claimed_at' => $claimedAt,
                'claim_confirmed_at' => null,
            ]);
        });

        $itemReport->refresh();
        $matchedReport->refresh();

        if ($matchedReport->user) {
            $matchedReport->user->notify(new ItemClaimed($itemReport, $matchedReport));
        }

        Notification::send(
            User::query()->where('role', 'admin')->get(),
            new ItemClaimed($itemReport, $matchedReport)
        );

        return redirect()
            ->route('reports.show', $itemReport)
            ->with('status', 'Item successfully claimed. The finder and admin have been notified.');
    }

    public function destroy(Request $request, ItemReport $itemReport): RedirectResponse
    {
        abort_unless($request->user()->isStudent(), 403);
        abort_unless($itemReport->user_id === $request->user()->id, 403);
        abort_unless($itemReport->canBeDeletedByStudent(), 403);

        if ($itemReport->photo_path) {
            File::delete(public_path($itemReport->photo_path));
        }

        $itemReport->delete();

        return redirect()
            ->route('student.dashboard')
            ->with('status', 'Report deleted.');
    }

    private function matchingReports(ItemReport $itemReport)
    {
        return $this->matchingReportsQuery($itemReport)
            ->latest('updated_at')
            ->limit(4)
            ->get();
    }

    private function matchingReportsQuery(ItemReport $itemReport): Builder
    {
        return ItemReport::query()
            ->public()
            ->where('id', '!=', $itemReport->id)
            ->where('type', $itemReport->oppositeType())
            ->when(
                $itemReport->type === ItemReport::TYPE_LOST,
                fn (Builder $query) => $query->where('status', ItemReport::STATUS_APPROVED)
            )
            ->where(function (Builder $query) use ($itemReport) {
                $query->where('category', $itemReport->category);

                if (filled($itemReport->item_name)) {
                    $query->orWhere('item_name', 'like', '%' . $itemReport->item_name . '%');
                }

                if (filled($itemReport->location)) {
                    $query->orWhere('location', 'like', '%' . $itemReport->location . '%');
                }
            });
    }

    private function claimableLostReportForFound(int $userId, ItemReport $itemReport): ?ItemReport
    {
        if (
            $itemReport->type !== ItemReport::TYPE_FOUND
            || $itemReport->status !== ItemReport::STATUS_APPROVED
            || $itemReport->user_id === $userId
        ) {
            return null;
        }

        return ItemReport::query()
            ->where('user_id', $userId)
            ->where('type', ItemReport::TYPE_LOST)
            ->whereIn('status', [ItemReport::STATUS_APPROVED, ItemReport::STATUS_FOUND])
            ->where(function (Builder $query) use ($itemReport) {
                $query->where('matched_report_id', $itemReport->id)
                    ->orWhere(function (Builder $query) use ($itemReport) {
                        $query->where('category', $itemReport->category);

                        if (filled($itemReport->item_name)) {
                            $query->orWhere('item_name', 'like', '%' . $itemReport->item_name . '%');
                        }

                        if (filled($itemReport->location)) {
                            $query->orWhere('location', 'like', '%' . $itemReport->location . '%');
                        }
                    });
            })
            ->latest('updated_at')
            ->first();
    }

    private function categoryOptions(?int $userId = null)
    {
        $savedCategories = ItemCategory::query()
            ->orderBy('name')
            ->pluck('name');

        $reportCategories = ItemReport::query()
            ->when($userId, function (Builder $query) use ($userId) {
                $query->where(function (Builder $query) use ($userId) {
                    $query->public()
                        ->orWhere('user_id', $userId);
                });
            }, fn (Builder $query) => $query->public())
            ->select('category')
            ->distinct()
            ->pluck('category');

        return collect(ItemReport::DEFAULT_CATEGORIES)
            ->merge($savedCategories)
            ->merge($reportCategories)
            ->map(fn ($category) => trim((string) $category))
            ->filter()
            ->reject(fn ($category) => $category === self::CUSTOM_CATEGORY_VALUE)
            ->unique(fn ($category) => Str::lower($category))
            ->sortBy(fn ($category) => Str::lower($category))
            ->values();
    }
}
