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
        <div style="display: grid; gap: 14px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 18px;">
            @if ($itemReport->type !== 'found')
                <div style="padding: 12px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;">
                    <div style="font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; margin-bottom: 6px;">Location</div>
                    <div style="font-size: 0.95rem; color: #0f172a; line-height: 1.5;">{{ $itemReport->location }}</div>
                </div>
            @endif
            <div style="padding: 12px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;">
                <div style="font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; margin-bottom: 6px;">Date & Time</div>
                <div style="font-size: 0.95rem; color: #0f172a; line-height: 1.5;">{{ $itemReport->happened_at?->format('M d, Y h:i A') ?? 'Not set' }}</div>
            </div>
            <div style="padding: 12px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;">
                <div style="font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; margin-bottom: 6px;">Contact</div>
                <div style="font-size: 0.95rem; color: #0f172a; line-height: 1.5;">
                    {{ $itemReport->contact_name }}
                    @if ($itemReport->contact_phone)
                        <br><span style="color: #475569;">Phone: {{ $itemReport->contact_phone }}</span>
                    @endif
                    @if ($itemReport->contact_email)
                        <br><span style="color: #475569;">Email: {{ $itemReport->contact_email }}</span>
                    @endif
                </div>
            </div>
            <div style="padding: 12px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;">
                <div style="font-size: 0.78rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; margin-bottom: 6px;">Submitted</div>
                <div style="font-size: 0.95rem; color: #0f172a; line-height: 1.5;">{{ $itemReport->created_at->format('M d, Y h:i A') }}</div>
            </div>
        </div>

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

    @if ($claimableLostReport)
        <section class="panel" style="margin-bottom: 18px;">
            <h2>Matched Lost Report</h2>
            <p class="muted">This found item may match your lost report: {{ $claimableLostReport->item_name }}.</p>
            <form method="POST" action="{{ route('reports.claim', $claimableLostReport) }}" style="margin-top: 12px; display: flex; justify-content: flex-start;">
                @csrf
                @method('PATCH')
                <input type="hidden" name="matched_report_id" value="{{ $itemReport->id }}">
                <button class="button" type="submit">Claim Object</button>
            </form>
        </section>
    @endif

    @if ($canSeePotentialMatches)
        <section class="panel">
            <h2>Potential Matches</h2>
            <div class="report-grid">
                @forelse ($matches as $match)
                    <article class="report-card" style="display: flex; flex-direction: column;">
                        @if ($match->photoUrl())
                            <img class="report-photo" src="{{ $match->photoUrl() }}" alt="{{ $match->item_name }}">
                        @else
                            <div class="photo-placeholder">{{ strtoupper(substr($match->type, 0, 1)) }}</div>
                        @endif
                        <div class="report-body" style="display: flex; flex-direction: column; flex: 1;">
                            <div class="report-meta">
                                <span class="badge badge-{{ $match->type }}">{{ $match->type }}</span>
                                <span class="badge badge-category">{{ $match->category }}</span>
                            </div>
                            <h3>{{ $match->item_name }}</h3>
                            <p>{{ \Illuminate\Support\Str::limit($match->description, 120) }}</p>
                            <form method="POST" action="{{ route('reports.claim', $itemReport) }}" style="margin-top: auto; display: flex; justify-content: flex-start;">
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
