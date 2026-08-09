@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
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
            <span>Archived</span>
            <strong>{{ $stats['archived'] }}</strong>
        </article>
        <article class="stat-card">
            <span>Rejected</span>
            <strong>{{ $stats['rejected'] }}</strong>
        </article>
        <article class="stat-card">
            <span>Blocked IPs</span>
            <strong>{{ $stats['blocked_ips'] }}</strong>
        </article>
        <article class="stat-card">
            <span>Spam Attempts</span>
            <strong>{{ $stats['spam'] }}</strong>
        </article>
        <article class="stat-card">
            <span>Submissions 24H</span>
            <strong>{{ $stats['submissions_24h'] }}</strong>
        </article>
    </section>

    @php
        $statuses = ['pending', 'approved', 'claimed', 'closed', 'archived', 'rejected', 'blocked'];
    @endphp

    @if ($claimAlerts->isNotEmpty())
        <section class="panel" style="margin-bottom: 18px;">
            <h2>Notifications</h2>

            <div class="admin-list">
                @foreach ($claimAlerts as $alert)
                    <article class="report-card" style="padding: 16px; box-shadow: none;">
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
                            <form method="POST" action="{{ route('admin.reports.close', $alert) }}">
                                @csrf
                                @method('PATCH')
                                <button class="button" type="submit">Close History</button>
                            </form>
                            <form method="POST" action="{{ route('admin.reports.archive', $alert) }}" onsubmit="return confirm('Move this claimed report to archive?');">
                                @csrf
                                @method('PATCH')
                                <button class="danger-button" type="submit">Move to Archive</button>
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
        @foreach ($statuses as $status)
            <a class="{{ request('status') === $status ? 'button' : 'ghost-button' }}" href="{{ route('admin.dashboard', ['status' => $status]) }}">
                {{ ucfirst($status) }}
            </a>
        @endforeach
    </nav>

    <section class="admin-list">
        @forelse ($reports as $report)
            <article class="panel admin-row">
                <div>
                    <div class="report-meta">
                        <span class="badge badge-{{ $report->type }}">{{ $report->type }}</span>
                        <span class="badge badge-{{ $report->status }}">{{ $report->status }}</span>
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
                                <dt>Archived</dt>
                                <dd>{{ $report->archived_at->format('M d, Y h:i A') }}</dd>
                            </div>
                        @endif
                    </dl>

                    @if ($report->admin_notes)
                        <p style="margin-top: 12px;"><strong>Admin Notes:</strong> {{ $report->admin_notes }}</p>
                    @endif
                </div>

                <div class="admin-actions">
                    @if (! in_array($report->status, ['approved', 'archived'], true))
                        <form method="POST" action="{{ route('admin.reports.approve', $report) }}">
                            @csrf
                            @method('PATCH')
                            <button class="button" type="submit">Approve</button>
                        </form>
                    @endif

                    @if (! in_array($report->status, ['rejected', 'claimed', 'closed', 'archived'], true))
                        <form method="POST" action="{{ route('admin.reports.reject', $report) }}">
                            @csrf
                            @method('PATCH')
                            <button class="ghost-button" type="submit">Reject</button>
                        </form>
                    @endif

                    @if (! in_array($report->status, ['blocked', 'archived'], true))
                        <form method="POST" action="{{ route('admin.reports.block', $report) }}" onsubmit="return confirm('Remove this spam report from admin history?');">
                            @csrf
                            @method('PATCH')
                            <button class="danger-button" type="submit">Remove Spam</button>
                        </form>
                    @endif

                    @if ($report->status === 'claimed')
                        <form method="POST" action="{{ route('admin.reports.close', $report) }}">
                            @csrf
                            @method('PATCH')
                            <button class="button" type="submit">Close History</button>
                        </form>
                    @endif

                    @if (in_array($report->status, ['claimed', 'closed'], true))
                        <form method="POST" action="{{ route('admin.reports.archive', $report) }}" onsubmit="return confirm('Move this claimed report to archive?');">
                            @csrf
                            @method('PATCH')
                            <button class="danger-button" type="submit">Move to Archive</button>
                        </form>
                    @endif

                    @if ($report->photoUrl())
                        <a class="ghost-button" href="{{ $report->photoUrl() }}" target="_blank" rel="noreferrer">View Photo</a>
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
@endsection
