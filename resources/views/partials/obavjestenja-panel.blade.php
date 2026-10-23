{{-- FT-004 Obavještenja panel — same content for guests and authenticated users --}}
<style>
    .obavjestenja-panel {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 18px 20px;
        box-shadow: 0 1px 2px rgba(0,0,0,.04);
    }
    .obavjestenja-panel .obavjestenja-head {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 4px;
    }
    .obavjestenja-panel .obavjestenja-title {
        margin: 0;
        font-size: 22px;
        font-weight: 700;
        color: #111827;
    }
    .obavjestenja-panel .obavjestenja-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
    }
    .obavjestenja-panel .obavjestenja-item {
        display: grid;
        grid-template-columns: auto 1fr;
        column-gap: 14px;
        align-items: center;
        padding: 14px 0;
        border-top: 1px solid #e5e7eb;
    }
    .obavjestenja-panel .obavjestenja-item:first-child {
        border-top: 0;
    }
    .obavjestenja-panel .obavjestenja-item > p {
        grid-column: 2;
        margin: 0 0 4px;
        color: #6b7280;
        font-size: 13px;
    }
    .obavjestenja-panel .obavjestenja-item > h3 {
        grid-column: 2;
        margin: 0 0 4px;
        font-size: 15px;
        font-weight: 700;
        line-height: 1.35;
        color: #0B3D91;
    }
    .obavjestenja-panel .obavjestenja-item > h3 a {
        color: #0B3D91;
        text-decoration: none;
        font-weight: 700;
    }
    .obavjestenja-panel .obavjestenja-item > h3 a:hover {
        text-decoration: underline;
    }
    .obavjestenja-panel .obavjestenja-item > p:has(> time) {
        grid-column: 1;
        grid-row: 1 / span 4;
        align-self: center;
        margin: 0;
        min-width: 78px;
        padding: 8px 10px;
        border-radius: 8px;
        background: #e8edf5;
        color: #4b5563;
        font-size: 12px;
        font-weight: 600;
        text-align: center;
        line-height: 1.25;
    }
    .obavjestenja-panel .obavjestenja-item:not(:has(> p > time)) {
        grid-template-columns: 1fr;
    }
    .obavjestenja-panel .obavjestenja-item:not(:has(> p > time)) > p,
    .obavjestenja-panel .obavjestenja-item:not(:has(> p > time)) > h3 {
        grid-column: 1;
    }
    .obavjestenja-panel .obavjestenja-empty {
        margin: 8px 0 0;
        color: #6b7280;
        font-size: 14px;
    }
</style>
<section class="obavjestenja-panel" aria-labelledby="obavjestenja-heading">
    <div class="obavjestenja-head">
        <h2 id="obavjestenja-heading" class="obavjestenja-title">Obavještenja</h2>
    </div>

    @if(($activeNotices ?? collect())->isEmpty())
        <p class="obavjestenja-empty">
            Trenutno nema aktivnih Obavještenja.
        </p>
    @else
        <ul class="obavjestenja-list">
            @foreach($activeNotices as $notice)
                @php
                    $publicContentUrl = route('notices.public-content', $notice);
                    $opensSignedOfficialDecision = $notice->content_delivery === 'competition_decision_signed_copy';
                    $competitionTitle = $opensSignedOfficialDecision
                        ? $notice->sourceObject?->competition?->title
                        : null;
                @endphp
                <li class="obavjestenja-item">
                    @if(filled($competitionTitle))
                        <p>
                            {{ $competitionTitle }}
                        </p>
                    @endif
                    <h3>
                        <a href="{{ $publicContentUrl }}" @if($opensSignedOfficialDecision) target="_blank" rel="noopener noreferrer" @endif>
                            {{ $notice->title }}
                        </a>
                    </h3>
                    @if($notice->public_display_date)
                        <p>
                            Datum objave: <time datetime="{{ $notice->public_display_date->toDateString() }}">{{ $notice->public_display_date->format('d.m.Y') }}</time>
                        </p>
                    @endif
                    @if(! $opensSignedOfficialDecision && filled($notice->short_description))
                        <p>
                            {{ $notice->short_description }}
                        </p>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</section>
