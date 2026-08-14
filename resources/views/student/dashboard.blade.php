@extends('layouts.app')

@section('title', 'Student Dashboard · ReLink')

@section('content')
    <section class="welcome">
        <h1>Welcome back! 👋</h1>
        <p>Here's what's happening with your lost and found items today.</p>
    </section>
    <section class="stats-grid">
        <article class="stat-card"><span class="stat-icon">♧</span><div><span>Lost Items</span><strong>{{ $reports->where('type', 'lost')->count() }}</strong><a href="#my-reports">View all lost items →</a></div></article>
        <article class="stat-card"><span class="stat-icon">♧</span><div><span>Found Items</span><strong>{{ $reports->where('type', 'found')->count() }}</strong><a href="#my-reports">View all found items →</a></div></article>
        <article class="stat-card"><span class="stat-icon">✪</span><div><span>Claimed Items</span><strong>{{ $stats['claimed'] }}</strong><a href="#my-reports">View claimed items →</a></div></article>
        <article class="stat-card"><span class="stat-icon">◷</span><div><span>Pending Reports</span><strong>{{ $stats['pending'] }}</strong><a href="#my-reports">View pending reports →</a></div></article>
    </section>
    @if ($matchAlerts->isNotEmpty() || $claimedAlerts->isNotEmpty())
        <section class="panel" style="margin-bottom:22px;padding:18px">
            <div class="panel-head" style="padding:0 0 10px"><h2>Notifications</h2></div>
            <div class="admin-list">
                @foreach ($matchAlerts as $alert)
                    <article class="report-card" style="padding:14px;box-shadow:none"><span class="badge badge-found">Found Match</span><h3 style="margin:8px 0 4px">{{ $alert['found']->item_name }} may match your lost {{ $alert['lost']->item_name }}</h3><p class="muted" style="margin:0">Pick Up Location: {{ $alert['found']->location }}</p><p class="muted" style="margin:4px 0 0">Finder: {{ $alert['found']->contact_name }} @if($alert['found']->contact_phone) | Phone: {{ $alert['found']->contact_phone }} @endif @if($alert['found']->contact_email) | Email: {{ $alert['found']->contact_email }} @endif</p><a class="ghost-button" style="margin-top:10px" href="{{ route('reports.show', $alert['lost']) }}">View My Lost Report</a></article>
                @endforeach
                @foreach ($claimedAlerts as $alert)
                    <article class="report-card" style="padding:14px;box-shadow:none"><span class="badge badge-claimed">Claimed Match</span><h3 style="margin:8px 0 4px">Your found {{ $alert['found']->item_name }} may have been claimed by the owner of lost {{ $alert['lost']->item_name }}</h3><a class="ghost-button" style="margin-top:10px" href="{{ route('reports.show', $alert['found']) }}">View My Found Report</a></article>
                @endforeach
            </div>
        </section>
    @endif
    <section class="dashboard-grid" id="my-reports">
        <section class="panel reports-panel">
            <div class="panel-head"><h2><span>♧</span> Recent Reports</h2><a class="view-all" href="#all-reports">View All Reports →</a></div>
            <div class="status-filters" aria-label="Recent report filters">
                <button class="active" type="button" data-filter-mode="all" aria-pressed="true">All</button>
                <button type="button" data-filter-mode="type" data-filter-value="lost" aria-pressed="false">Lost</button>
                <button type="button" data-filter-mode="type" data-filter-value="found" aria-pressed="false">Found</button>
                <button type="button" data-filter-mode="status" data-filter-value="claimed" aria-pressed="false">Claimed</button>
                <button type="button" data-filter-mode="status" data-filter-value="pending" aria-pressed="false">Pending</button>
            </div>
            <div id="all-reports">
                @forelse ($reports as $report)
                    <article class="report-row" data-type="{{ $report->type }}" data-status="{{ $report->status }}">
                        @if ($report->photoUrl())<img class="row-photo" src="{{ $report->photoUrl() }}" alt="{{ $report->item_name }}">@else<div class="row-placeholder">{{ $report->type === 'lost' ? '⌂' : '♧' }}</div>@endif
                        <div class="report-info"><h3>{{ $report->item_name ?: ucfirst($report->category) }} <span class="tag tag-{{ $report->type }}">{{ strtoupper($report->type) }}</span></h3><p>⌖ {{ $report->location ?: 'Location not specified' }}</p><p>{{ ($report->happened_at ?? $report->created_at)->format('M d, Y · h:i A') }}</p></div>
                        <p class="report-by"><small>{{ $report->type === 'found' ? 'Found by' : 'Reported by' }}</small>{{ $report->contact_name }}</p><div class="row-status"><span class="tag tag-{{ in_array($report->status, ['claimed','closed','archived']) ? 'claimed' : $report->status }}">{{ strtoupper($report->status) }}</span></div>
                    </article>
                @empty
                    <div class="empty-state"><h3>No reports yet</h3><p class="muted">Start by reporting a lost item or a found item.</p></div>
                @endforelse
            </div>
            @if ($reports->isNotEmpty())<div class="report-footer"><a class="outline-button" href="{{ route('home') }}">View All Reports</a></div>@endif
        </section>
        <aside class="side-column">
            <section class="panel quick-card"><h2>Quick Actions</h2><a class="quick-link" href="{{ route('reports.create',['type'=>'lost']) }}"><i>➤</i><span><strong>Report Lost Item</strong><small>Let us know what you lost.</small></span></a><a class="quick-link" href="{{ route('reports.create',['type'=>'found']) }}"><i>＋</i><span><strong>Report Found Item</strong><small>Found something? Report it here.</small></span></a><a class="quick-link" href="{{ route('home',['type'=>'found']) }}"><i>⌕</i><span><strong>Browse Found Items</strong><small>Find items that have been found.</small></span></a><a class="quick-link" href="#my-reports"><i>▤</i><span><strong>My Reports</strong><small>View and track your reports.</small></span></a></section>
            <section class="panel overview-card"><h2>Statistics Overview</h2><div class="overview-item"><i>♧</i><span>Lost Items</span><b>{{ $reports->where('type', 'lost')->count() }}</b></div><div class="overview-item"><i>♧</i><span>Found Items</span><b>{{ $reports->where('type', 'found')->count() }}</b></div><div class="overview-item"><i>✪</i><span>Claimed Items</span><b>{{ $stats['claimed'] }}</b></div><div class="overview-item"><i>◷</i><span>Pending Reports</span><b>{{ $stats['pending'] }}</b></div><div class="overview-total"><span>Total Reports</span><span>{{ $stats['total'] }}</span></div></section>
        </aside>
    </section>
    <script>
        const filterButtons = document.querySelectorAll('[data-filter-mode]');
        const reportRows = document.querySelectorAll('.report-row');

        filterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                filterButtons.forEach((item) => {
                    item.classList.remove('active');
                    item.setAttribute('aria-pressed', 'false');
                });

                button.classList.add('active');
                button.setAttribute('aria-pressed', 'true');

                const mode = button.dataset.filterMode;
                const value = button.dataset.filterValue;

                reportRows.forEach((row) => {
                    row.hidden = mode !== 'all' && row.dataset[mode] !== value;
                });
            });
        });
    </script>
@endsection
