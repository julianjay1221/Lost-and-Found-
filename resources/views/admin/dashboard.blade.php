@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
    <style>
        .admin-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 18px;
            align-items: start;
            padding: 18px;
        }

        .admin-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            align-items: center;
            gap: 10px;
            max-width: 100%;
        }

        .admin-actions form {
            display: flex;
            margin: 0;
        }

        .admin-actions .button,
        .admin-actions .ghost-button,
        .admin-actions .danger-button {
            min-width: 108px;
            min-height: 38px;
            padding: 0 16px;
            white-space: nowrap;
        }

        @media (max-width: 920px) {
            .admin-row {
                grid-template-columns: 1fr;
            }

            .admin-actions {
                justify-content: flex-start;
            }
        }

        @media (max-width: 520px) {
            .admin-actions {
                gap: 8px;
            }

            .admin-actions form,
            .admin-actions > .ghost-button {
                flex: 1 1 128px;
            }

            .admin-actions .button,
            .admin-actions .ghost-button,
            .admin-actions .danger-button {
                width: 100%;
                min-width: auto;
            }
        }
    </style>

    <section class="page-head">
        <div>
            <p class="eyebrow">Admin Side</p>
            <h1>Dashboard</h1>
            <p class="muted">Review new reports, publish valid entries, and close reunited items.</p>
        </div>
    </section>

    <section class="stats-grid">
        <article class="stat-card">
            <span>Active Reports</span>
            <strong>{{ $stats['total'] }}</strong>
        </article>
        <article class="stat-card">
            <span>Pending</span>
            <strong>{{ $stats['pending'] }}</strong>
        </article>
        <article class="stat-card">
            <span>Approved</span>
            <strong>{{ $stats['approved'] }}</strong>
        </article>
        <article class="stat-card">
            <span>Claimed</span>
            <strong>{{ $stats['claimed'] }}</strong>
        </article>
        <article class="stat-card">
            <span>History</span>
            <strong>{{ $stats['history'] }}</strong>
        </article>
        <article class="stat-card">
            <span>Blocked IPs</span>
            <strong>{{ $stats['blocked_ips'] }}</strong>
        </article>
        <article class="stat-card">
            <span>Submissions 24H</span>
            <strong>{{ $stats['submissions_24h'] }}</strong>
        </article>
    </section>

    @php
        $returnStatus = request('status', 'all');
        $statuses = [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'found' => 'Found',
            'claimed' => 'Claimed',
            'history' => 'History',
        ];

        $statusLabels = [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'found' => 'Found',
            'claimed' => 'Claimed',
            'closed' => 'Closed',
            'archived' => 'History',
        ];

        $statusBadgeClasses = [
            'archived' => 'badge-archived',
        ];
    @endphp

    @if ($claimAlerts->isNotEmpty())
        <section class="panel" style="margin-bottom: 18px;">
            <h2>Notifications</h2>

            <div class="admin-list">
                @foreach ($claimAlerts as $alert)
                    <article class="report-card" style="padding: 16px; box-shadow: none;" data-admin-report-row data-report-id="{{ $alert->id }}" data-report-status="{{ $alert->status }}">
                        <div class="report-meta">
                            <span class="badge badge-claimed">Claimed Match</span>
                            <span class="badge badge-category">{{ $alert->category }}</span>
                        </div>
                        <h3>{{ $alert->user?->name ?? 'A student' }} claimed {{ $alert->item_name }}</h3>
                        <p class="muted">
                            Matched found report:
                            {{ $alert->matchedReport?->item_name ?? 'Deleted report' }}
                            by {{ $alert->matchedReport?->user?->name ?? 'Deleted user' }}
                        </p>
                        <p class="muted">Claimed: {{ $alert->claimed_at?->format('M d, Y h:i A') ?? 'Recently' }}</p>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <a class="button" href="{{ route('reports.show', $alert) }}">Review Claimed Report</a>
                            <form method="POST" action="{{ route('admin.reports.confirm-claim', $alert) }}" data-admin-action data-target-status="claimed">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="return_status" value="{{ $returnStatus }}">
                                <button class="button" type="submit">Confirm Claim</button>
                            </form>
                            <form method="POST" action="{{ route('admin.reports.archive', $alert) }}" onsubmit="return confirm('Move this claimed report to history?');" data-admin-action data-target-status="history">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="return_status" value="{{ $returnStatus }}">
                                <button class="danger-button" type="submit">Move to History</button>
                            </form>
                            <a class="ghost-button" href="{{ route('admin.dashboard', ['status' => 'claimed']) }}">View Claimed Queue</a>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <nav class="status-filters" aria-label="Status filters">
        <a class="{{ request('status') ? 'ghost-button' : 'button' }}" href="{{ route('admin.dashboard') }}">All</a>
        @foreach ($statuses as $status => $label)
            <a class="{{ request('status') === $status ? 'button' : 'ghost-button' }}" href="{{ route('admin.dashboard', ['status' => $status]) }}">
                {{ $label }}
            </a>
        @endforeach
    </nav>

    <section class="admin-list" data-admin-report-list>
        @forelse ($reports as $report)
            <article class="panel admin-row" data-admin-report-row data-report-id="{{ $report->id }}" data-report-status="{{ $report->status }}">
                <div>
                    <div class="report-meta">
                        <span class="badge badge-{{ $report->type }}">{{ $report->type }}</span>
                        <span class="badge {{ $statusBadgeClasses[$report->status] ?? 'badge-' . $report->status }}">{{ $statusLabels[$report->status] ?? $report->status }}</span>
                        <span class="badge badge-category">{{ $report->category }}</span>
                        @if ($report->user?->isAdmin())
                            <span class="badge badge-admin">Admin</span>
                        @endif
                    </div>

                    <h2>{{ $report->item_name }}</h2>
                    <p>{{ $report->description }}</p>

                    <dl class="detail-list">
                        <div>
                            <dt>Submitted By</dt>
                            <dd>{{ $report->user?->name ?? 'Deleted user' }}</dd>
                        </div>
                        <div>
                            <dt>Location</dt>
                            <dd>{{ $report->location }}</dd>
                        </div>
                        <div>
                            <dt>Date & Time</dt>
                            <dd>{{ $report->happened_at?->format('M d, Y h:i A') ?? 'Not set' }}</dd>
                        </div>
                        <div>
                            <dt>Contact</dt>
                            <dd>{{ $report->contact_phone ?: $report->contact_email }}</dd>
                        </div>
                        <div>
                            <dt>IP Address</dt>
                            <dd>{{ $report->ip_address ?? 'Not recorded' }}</dd>
                        </div>
                        <div>
                            <dt>Submitted</dt>
                            <dd>{{ $report->created_at->format('M d, Y h:i A') }}</dd>
                        </div>
                        @if ($report->archived_at)
                            <div>
                                <dt>History</dt>
                                <dd>{{ $report->archived_at->format('M d, Y h:i A') }}</dd>
                            </div>
                        @endif
                    </dl>

                    @if ($report->admin_notes)
                        <p style="margin-top: 12px;"><strong>Admin Notes:</strong> {{ $report->admin_notes }}</p>
                    @endif

                    @if ($report->claim_confirmed_at)
                        <p style="margin-top: 12px;"><strong>Claim Confirmed:</strong> {{ $report->claim_confirmed_at->format('M d, Y h:i A') }}</p>
                    @endif
                </div>

                <div class="admin-actions">
                    @if (in_array($report->status, ['approved', 'claimed'], true))
                        @if ($report->photoUrl())
                            <a class="ghost-button" href="{{ $report->photoUrl() }}" target="_blank" rel="noreferrer">View Photo</a>
                        @endif
                    @else
                        @if (! in_array($report->status, ['approved', 'archived'], true))
                            <form method="POST" action="{{ route('admin.reports.approve', $report) }}" data-admin-action data-target-status="approved">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="return_status" value="{{ $returnStatus }}">
                                <button class="button" type="submit">Approve</button>
                            </form>
                        @endif

                        @if (! in_array($report->status, ['claimed', 'closed', 'archived'], true))
                            <form method="POST" action="{{ route('admin.reports.reject', $report) }}" data-admin-action data-target-status="rejected">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="return_status" value="{{ $returnStatus }}">
                                <button class="ghost-button" type="submit">Reject</button>
                            </form>
                        @endif

                        @if (! in_array($report->status, ['blocked', 'archived'], true))
                            <form method="POST" action="{{ route('admin.reports.block', $report) }}" onsubmit="return confirm('Move this report to the spam tab?');" data-admin-action data-target-status="spam">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="return_status" value="{{ $returnStatus }}">
                                <button class="danger-button" type="submit">Remove Spam</button>
                            </form>
                        @endif

                        @if (in_array($report->status, ['found', 'claimed'], true))
                            @if ($report->type === 'lost' && $report->status === 'claimed' && ! $report->claim_confirmed_at)
                                <form method="POST" action="{{ route('admin.reports.confirm-claim', $report) }}" data-admin-action data-target-status="claimed">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="return_status" value="{{ $returnStatus }}">
                                    <button class="button" type="submit">Confirm Claim</button>
                                </form>
                            @endif

                            @if ($report->status !== 'claimed')
                                <form method="POST" action="{{ route('admin.reports.archive', $report) }}" onsubmit="return confirm('Move this claimed report to history?');" data-admin-action data-target-status="history">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="return_status" value="{{ $returnStatus }}">
                                    <button class="danger-button" type="submit">Move to History</button>
                                </form>
                            @endif
                        @endif

                        @if ($report->photoUrl())
                            <a class="ghost-button" href="{{ $report->photoUrl() }}" target="_blank" rel="noreferrer">View Photo</a>
                        @endif
                    @endif
                </div>
            </article>
        @empty
            <div class="panel empty-state">
                <h2>No reports found</h2>
                <p class="muted">New submissions appear here as pending reports.</p>
            </div>
        @endforelse
    </section>

    @if ($reports->hasPages())
        <nav class="pager" aria-label="Admin report pages">
            @if ($reports->onFirstPage())
                <span class="ghost-button">Previous</span>
            @else
                <a class="ghost-button" href="{{ $reports->previousPageUrl() }}">Previous</a>
            @endif
            <span>Page {{ $reports->currentPage() }} of {{ $reports->lastPage() }}</span>
            @if ($reports->hasMorePages())
                <a class="ghost-button" href="{{ $reports->nextPageUrl() }}">Next</a>
            @else
                <span class="ghost-button">Next</span>
            @endif
        </nav>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const dashboardUrl = @json(route('admin.dashboard'));

            const showToast = (message) => {
                let toast = document.querySelector('[data-admin-toast]');

                if (!toast) {
                    toast = document.createElement('div');
                    toast.dataset.adminToast = '';
                    toast.style.position = 'fixed';
                    toast.style.right = '24px';
                    toast.style.bottom = '24px';
                    toast.style.zIndex = '50';
                    toast.style.maxWidth = '320px';
                    toast.style.padding = '12px 16px';
                    toast.style.borderRadius = '8px';
                    toast.style.color = '#fff';
                    toast.style.background = '#087a47';
                    toast.style.boxShadow = '0 12px 30px rgba(20,57,36,.18)';
                    toast.style.fontWeight = '700';
                    document.body.appendChild(toast);
                }

                toast.textContent = message;
                toast.hidden = false;

                window.clearTimeout(toast.hideTimer);
                toast.hideTimer = window.setTimeout(() => {
                    toast.hidden = true;
                }, 3000);
            };

            const replaceAdminContent = (html, url) => {
                const page = new DOMParser().parseFromString(html, 'text/html');
                const selectors = ['.stats-grid', '.status-filters', '[data-admin-report-list]', '.pager'];

                selectors.forEach((selector) => {
                    const current = document.querySelector(selector);
                    const next = page.querySelector(selector);

                    if (current && next) {
                        current.replaceWith(next);
                    } else if (current && !next) {
                        current.remove();
                    }
                });

                window.history.pushState({}, '', url);
            };

            const loadStatusTab = async (url) => {
                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('Unable to load status tab.');
                }

                replaceAdminContent(await response.text(), url);
            };

            document.addEventListener('submit', async (event) => {
                const form = event.target.closest('[data-admin-action]');

                if (!form || event.defaultPrevented) {
                    return;
                }

                event.preventDefault();

                const button = form.querySelector('button[type="submit"]');
                const originalDisabled = button?.disabled ?? false;

                if (button) {
                    button.disabled = true;
                }

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Action failed.');
                    }

                    const payload = await response.json();
                    const targetStatus = payload.target_status || form.dataset.targetStatus;
                    const targetUrl = payload.target_url || `${dashboardUrl}?status=${encodeURIComponent(targetStatus)}`;

                    form.closest('[data-admin-report-row]')?.remove();
                    await loadStatusTab(targetUrl);
                    showToast(payload.message || 'Report updated successfully.');
                } catch (error) {
                    form.submit();
                } finally {
                    if (button) {
                        button.disabled = originalDisabled;
                    }
                }
            });
        });
    </script>
@endsection
