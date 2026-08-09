@extends('layouts.app')

@section('title', 'Student Dashboard')

@section('content')
    <section class="page-head">
        <div>
            <p class="eyebrow">Student Side</p>
            <h1>Student Dashboard</h1>
            <p class="muted">Submit lost or found items, track report status, and check public matches.</p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a class="button" href="{{ route('reports.create', ['type' => 'lost']) }}">Report Lost</a>
            <a class="ghost-button" href="{{ route('reports.create', ['type' => 'found']) }}">Report Found</a>
        </div>
    </section>

    <section class="stats-grid">
        <article class="stat-card">
            <span>My Reports</span>
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
    </section>

    @if ($matchAlerts->isNotEmpty() || $claimedAlerts->isNotEmpty())
        <section class="panel" style="margin-bottom: 18px;">
            <h2>Notifications</h2>

            <div class="admin-list">
                @foreach ($matchAlerts as $alert)
                    <article class="report-card" style="padding: 16px; box-shadow: none;">
                        <div class="report-meta">
                            <span class="badge badge-found">Found Match</span>
                            <span class="badge badge-category">{{ $alert['found']->category }}</span>
                        </div>
                        <h3>{{ $alert['found']->item_name }} may match your lost {{ $alert['lost']->item_name }}</h3>
                        <p class="muted">Pick Up Location: {{ $alert['found']->location }}</p>
                        <p>{{ \Illuminate\Support\Str::limit($alert['found']->description, 150) }}</p>
                        <p class="muted">
                            Finder: {{ $alert['found']->contact_name }}
                            @if ($alert['found']->contact_phone)
                                | Phone: {{ $alert['found']->contact_phone }}
                            @endif
                            @if ($alert['found']->contact_email)
                                | Email: {{ $alert['found']->contact_email }}
                            @endif
                        </p>
                        <a class="button" href="{{ route('reports.show', $alert['lost']) }}">View My Lost Report</a>
                    </article>
                @endforeach

                @foreach ($claimedAlerts as $alert)
                    <article class="report-card" style="padding: 16px; box-shadow: none;">
                        <div class="report-meta">
                            <span class="badge badge-claimed">Claimed Match</span>
                            <span class="badge badge-category">{{ $alert['found']->category }}</span>
                        </div>
                        <h3>Your found {{ $alert['found']->item_name }} may have been claimed by the owner of lost {{ $alert['lost']->item_name }}</h3>
                        <p class="muted">Marked claimed: {{ $alert['lost']->claimed_at?->format('M d, Y h:i A') ?? 'Recently' }}</p>
                        <p>{{ \Illuminate\Support\Str::limit($alert['lost']->description, 150) }}</p>
                        <a class="button" href="{{ route('reports.show', $alert['found']) }}">View My Found Report</a>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <section class="panel">
        <h2>Recent Reports</h2>

        <div class="admin-list">
            @forelse ($reports as $report)
                <article class="report-card" style="padding: 16px; box-shadow: none;">
                    <div class="report-meta">
                        <span class="badge badge-{{ $report->type }}">{{ $report->type }}</span>
                        <span class="badge badge-{{ $report->status }}">{{ $report->status }}</span>
                        <span class="badge badge-category">{{ $report->category }}</span>
                    </div>
                    <h3>{{ $report->item_name }}</h3>
                    <p class="muted">{{ $report->type === 'found' ? 'Pick Up Location' : 'Last Known Location' }}: {{ $report->location }}</p>
                    <p>{{ \Illuminate\Support\Str::limit($report->description, 160) }}</p>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <a class="ghost-button" href="{{ route('reports.show', $report) }}">View Details</a>

                        @if ($report->canBeDeletedByStudent())
                            <form method="POST" action="{{ route('reports.destroy', $report) }}" onsubmit="return confirm('Delete this report?');">
                                @csrf
                                @method('DELETE')
                                <button class="danger-button" type="submit">Delete Report</button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <div class="empty-state">
                    <h3>No reports yet</h3>
                    <p class="muted">Start by reporting a lost item or a found item.</p>
                </div>
            @endforelse
        </div>
    </section>
@endsection
