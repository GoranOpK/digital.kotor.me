@php
    $officialDecisionCopies = $competition->officialDecisionCopies()
        ->with('uploadedBy')
        ->orderBy('id')
        ->get();
    $canUploadOfficialDecision = isset($isCompetitionAdmin)
        && $isCompetitionAdmin
        && in_array($competition->status, ['closed', 'completed'], true);
@endphp

<div class="info-card">
    <h2 style="font-size: 20px; margin-bottom: 16px;">Zvanična Odluka</h2>

    @if($officialDecisionCopies->isEmpty())
        <p style="color: #6b7280; margin: 0 0 16px;">Još nije evidentiran potpisani primjerak.</p>
    @else
        <ul style="margin: 0 0 16px; padding-left: 20px; color: #374151; font-size: 14px; line-height: 1.6;">
            @foreach($officialDecisionCopies as $copy)
                <li>
                    Evidentiran {{ $copy->created_at?->format('d.m.Y H:i') }}
                    @if($copy->uploadedBy)
                        — postavio {{ $copy->uploadedBy->name }}
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    @if($canUploadOfficialDecision)
        <form method="POST" action="{{ route('admin.competitions.official-decision.store', $competition) }}" enctype="multipart/form-data">
            @csrf
            <div style="margin-bottom: 12px;">
                <label for="official_decision_copy" style="display: block; font-weight: 600; margin-bottom: 8px;">Potpisani primjerak</label>
                <input type="file" id="official_decision_copy" name="official_decision_copy" accept="application/pdf">
            </div>
            <button type="submit" class="btn btn-success" style="margin-left: 0;">Postavi primjerak</button>
        </form>
    @endif
</div>
