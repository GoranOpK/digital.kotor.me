@php
    $officialDecisionCopies = $competition->officialDecisionCopies()
        ->with('uploadedBy')
        ->orderBy('id')
        ->get();
    $canManageOfficialDecision = isset($isCompetitionAdmin)
        && $isCompetitionAdmin
        && in_array($competition->status, ['closed', 'completed'], true);
    $hasSignedCopyPublication = \App\Models\CompetitionOfficialDecisionCopy::competitionHasPublishedSignedCopy($competition->id);
    $activeSignedCopyNotices = \App\Models\CompetitionOfficialDecisionCopy::activeSignedCopyNotices($competition->id);
    $hasExactlyOneActivePublication = $activeSignedCopyNotices->count() === 1;
@endphp

<div class="info-card">
    <h2 style="font-size: 20px; margin-bottom: 16px;">Zvanična Odluka</h2>

    @if($officialDecisionCopies->isEmpty())
        <p style="color: #6b7280; margin: 0 0 16px;">Još nije evidentiran potpisani primjerak.</p>
    @else
        <ul style="margin: 0 0 16px; padding-left: 20px; color: #374151; font-size: 14px; line-height: 1.6;">
            @foreach($officialDecisionCopies as $copy)
                <li style="margin-bottom: 8px;">
                    Evidentiran {{ $copy->created_at?->format('d.m.Y H:i') }}
                    @if($copy->uploadedBy)
                        — postavio {{ $copy->uploadedBy->name }}
                    @endif
                    @if($copy->isCurrentlyPublished())
                        <span style="color: #065f46; font-weight: 600;"> — Objavljeno</span>
                    @elseif($canManageOfficialDecision && ! $hasSignedCopyPublication)
                        <form method="POST" action="{{ route('admin.competitions.official-decision.publish', [$competition, $copy]) }}" style="display: inline; margin-left: 8px;">
                            @csrf
                            <button type="submit" class="btn btn-success" style="margin-left: 0; padding: 6px 12px; font-size: 13px;">Objavi</button>
                        </form>
                    @elseif($canManageOfficialDecision && $hasExactlyOneActivePublication && ! $copy->hasBeenPublished())
                        <form method="POST" action="{{ route('admin.competitions.official-decision.correct', [$competition, $copy]) }}" style="display: inline; margin-left: 8px;" onsubmit="return confirm('Korigovati objavu? Pogrešni primjerak više neće biti javno dostupan.');">
                            @csrf
                            <button type="submit" class="btn btn-success" style="margin-left: 0; padding: 6px 12px; font-size: 13px;">Koriguj objavu</button>
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if($canManageOfficialDecision)
        <style>
            .official-decision-upload-form input[type="file"] {
                display: block;
                width: 100%;
                max-width: 480px;
                box-sizing: border-box;
                padding: 8px;
                border: 1px solid #d1d5db;
                border-radius: 8px;
                background: #fff;
                color: #111827;
                font-size: 14px;
                line-height: 1.45;
            }
            .official-decision-upload-form input[type="file"]::file-selector-button {
                margin-right: 12px;
                padding: 8px 16px;
                border: 0;
                border-radius: 6px;
                background: #0B3D91;
                color: #fff;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
            }
            .official-decision-upload-form input[type="file"]::file-selector-button:hover {
                background: #0A347B;
            }
        </style>
        <form class="official-decision-upload-form" method="POST" action="{{ route('admin.competitions.official-decision.store', $competition) }}" enctype="multipart/form-data">
            @csrf
            <div style="margin-bottom: 12px;">
                <label for="official_decision_copy" style="display: block; font-weight: 600; margin-bottom: 8px;">Potpisani primjerak</label>
                <input type="file" id="official_decision_copy" name="official_decision_copy" accept="application/pdf" required>
                <p style="margin: 8px 0 0; color: #6b7280; font-size: 13px;">Dozvoljen je PDF fajl do 2 MB.</p>
            </div>
            <button type="submit" class="btn btn-success" style="margin-left: 0;">Postavi primjerak</button>
        </form>
    @endif
</div>
