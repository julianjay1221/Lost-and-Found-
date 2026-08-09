@extends('layouts.app')

@section('title', 'Lost & Found Board')

@section('content')
    <section class="page-head">
        <div>
            <p class="eyebrow">Public Homepage</p>
            <h1>Lost & Found Board</h1>
            <p class="muted">Approved reports are visible here for searching, matching, and return.</p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            @auth
                @if (auth()->user()->isStudent())
                    <a class="button" href="{{ route('reports.create', ['type' => 'lost']) }}">Report Lost</a>
                    <a class="ghost-button" href="{{ route('reports.create', ['type' => 'found']) }}">Report Found</a>
                @elseif (auth()->user()->isAdmin())
                    <a class="button" href="{{ route('admin.dashboard') }}">Admin Dashboard</a>
                @endif
            @else
                <a class="button" href="{{ route('login', ['side' => 'student']) }}">Login to Report</a>
            @endauth
        </div>
    </section>

    <section class="stats-grid">
        <article class="stat-card">
            <span>Lost Items</span>
            <strong>{{ $stats['lost'] }}</strong>
        </article>
        <article class="stat-card">
            <span>Found Items</span>
            <strong>{{ $stats['found'] }}</strong>
        </article>
        <article class="stat-card">
            <span>Claimed</span>
            <strong>{{ $stats['claimed'] }}</strong>
        </article>
    </section>

    <form class="toolbar" method="GET" action="{{ route('home') }}" id="public-board-filter">
        <input class="input" type="search" name="q" value="{{ request('q') }}" placeholder="Search item, place, or note">
        <select class="select" name="type" data-auto-submit>
            <option value="">All Types</option>
            <option value="lost" @selected(request('type') === 'lost')>Lost</option>
            <option value="found" @selected(request('type') === 'found')>Found</option>
        </select>
        <select class="select" name="category" data-auto-submit>
            <option value="">All Categories</option>
            @foreach ($categories as $category)
                <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
            @endforeach
        </select>
        <a class="ghost-button" href="{{ route('home') }}">Reset</a>
    </form>

    @if ($reports->count())
        <section class="report-grid">
            @foreach ($reports as $report)
            <article class="report-card">
                @if ($report->photoUrl())
                    <img class="report-photo" src="{{ $report->photoUrl() }}" alt="{{ $report->item_name }}">
                @else
                    <div class="photo-placeholder">{{ strtoupper(substr($report->type, 0, 1)) }}</div>
                @endif

                <div class="report-body">
                    <div class="report-meta">
                        <span class="badge badge-{{ $report->type }}">{{ $report->type }}</span>
                        <span class="badge badge-category">{{ $report->category }}</span>
                    </div>
                    <h3>{{ $report->item_name }}</h3>
                    <p class="muted">{{ $report->location }}</p>
                    <p>{{ \Illuminate\Support\Str::limit($report->description, 130) }}</p>
                    <p class="muted">
                        Contact:
                        {{ $report->contact_phone ?: $report->contact_email }}
                    </p>
                </div>
            </article>
            @endforeach
        </section>
    @endif

    @if ($reports->hasPages())
        <nav class="pager" aria-label="Report pages">
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
        document.querySelectorAll('#public-board-filter [data-auto-submit]').forEach((field) => {
            field.addEventListener('change', () => field.form.submit());
        });
    </script>
@endsection
