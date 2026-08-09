@extends('layouts.app')

@section('title', 'Report Status')

@section('content')
    <section class="page-head">
        <div>
            <p class="eyebrow">Report Status</p>
            <h1>{{ $itemReport->item_name }}</h1>
            <div class="report-meta">
                <span class="badge badge-{{ $itemReport->type }}">{{ $itemReport->type }}</span>
                <span class="badge badge-{{ $itemReport->status }}">{{ $itemReport->status }}</span>
                <span class="badge badge-category">{{ $itemReport->category }}</span>
            </div>
        </div>
        <a class="ghost-button" href="{{ route('home') }}">View Board</a>
    </section>

    <section class="panel" style="margin-bottom: 18px;">
        <dl class="detail-list">
            <div>
                <dt>{{ $itemReport->type === 'found' ? 'Pick Up Location' : 'Last Known Location' }}</dt>
                <dd>{{ $itemReport->location }}</dd>
            </div>
            <div>
                <dt>Date & Time</dt>
                <dd>{{ $itemReport->happened_at?->format('M d, Y h:i A') ?? 'Not set' }}</dd>
            </div>
            <div>
                <dt>Contact</dt>
                <dd>
                    {{ $itemReport->contact_name }}
                    @if ($itemReport->contact_phone)
                        | Phone: {{ $itemReport->contact_phone }}
                    @endif
                    @if ($itemReport->contact_email)
                        | Email: {{ $itemReport->contact_email }}
                    @endif
                </dd>
            </div>
            <div>
                <dt>Submitted</dt>
                <dd>{{ $itemReport->created_at->format('M d, Y h:i A') }}</dd>
            </div>
        </dl>

        <div style="margin-top: 16px;">
            <h2>Description</h2>
            <p>{{ $itemReport->description }}</p>
        </div>

        @if ($itemReport->admin_notes)
            <div style="margin-top: 16px;">
                <h2>Admin Notes</h2>
                <p>{{ $itemReport->admin_notes }}</p>
            </div>
        @endif

        @if (auth()->user()?->isStudent() && $itemReport->user_id === auth()->id() && $itemReport->canBeDeletedByStudent())
            <form method="POST" action="{{ route('reports.destroy', $itemReport) }}" style="margin-top: 16px;" onsubmit="return confirm('Delete this report?');">
                @csrf
                @method('DELETE')
                <button class="danger-button" type="submit">Delete Report</button>
            </form>
        @endif

    </section>

    @if ($canSeePotentialMatches)
        <section class="panel">
            <h2>Potential Matches</h2>
            <div class="report-grid">
                @forelse ($matches as $match)
                    <article class="report-card">
                        @if ($match->photoUrl())
                            <img class="report-photo" src="{{ $match->photoUrl() }}" alt="{{ $match->item_name }}">
                        @else
                            <div class="photo-placeholder">{{ strtoupper(substr($match->type, 0, 1)) }}</div>
                        @endif
                        <div class="report-body">
                            <div class="report-meta">
                                <span class="badge badge-{{ $match->type }}">{{ $match->type }}</span>
                                <span class="badge badge-category">{{ $match->category }}</span>
                            </div>
                            <h3>{{ $match->item_name }}</h3>
                            <p class="muted">{{ $match->type === 'found' ? 'Pick Up Location' : 'Last Known Location' }}: {{ $match->location }}</p>
                            <p>{{ \Illuminate\Support\Str::limit($match->description, 120) }}</p>
                            <form method="POST" action="{{ route('reports.claim', $itemReport) }}" style="margin-top: 12px;">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="matched_report_id" value="{{ $match->id }}">
                                <button class="button" type="submit">Claim Object</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="empty-state">
                        <p class="muted">No public match found yet.</p>
                    </div>
                @endforelse
            </div>
        </section>
    @endif
@endsection
