{{-- FT-004 Obavještenja panel — same content for guests and authenticated users --}}
<section class="obavjestenja-panel" aria-labelledby="obavjestenja-heading">
    <div class="hero-card" style="margin-top: 0;">
        <h2 id="obavjestenja-heading" class="hero-title" style="font-size: 24px;">Obavještenja</h2>

        @if(($activeNotices ?? collect())->isEmpty())
            <p style="margin: 0; color: #6b7280; font-size: 14px;">
                Trenutno nema aktivnih Obavještenja.
            </p>
        @else
            <ul style="list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 12px;">
                @foreach($activeNotices as $notice)
                    @php
                        $publicContentUrl = route('notices.public-content', $notice);
                        $opensSignedOfficialDecision = $notice->content_delivery === 'competition_decision_signed_copy';
                        $competitionTitle = $opensSignedOfficialDecision
                            ? $notice->sourceObject?->competition?->title
                            : null;
                    @endphp
                    <li style="border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px; background: #ffffff;">
                        @if(filled($competitionTitle))
                            <p style="margin: 0 0 6px; color: #4b5563; font-size: 13px;">
                                {{ $competitionTitle }}
                            </p>
                        @endif
                        <h3 style="margin: 0 0 6px; font-size: 16px; color: #111827;">
                            <a href="{{ $publicContentUrl }}" @if($opensSignedOfficialDecision) target="_blank" rel="noopener noreferrer" @endif style="color: #0B3D91; text-decoration: none; font-weight: 700;">
                                {{ $notice->title }}
                            </a>
                        </h3>
                        @if($notice->public_display_date)
                            <p style="margin: 0 0 8px; color: #6b7280; font-size: 13px;">
                                Datum objave: <time datetime="{{ $notice->public_display_date->toDateString() }}">{{ $notice->public_display_date->format('d.m.Y') }}</time>
                            </p>
                        @endif
                        @if(! $opensSignedOfficialDecision && filled($notice->short_description))
                            <p style="margin: 0 0 8px; color: #4b5563; font-size: 14px;">
                                {{ $notice->short_description }}
                            </p>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>
