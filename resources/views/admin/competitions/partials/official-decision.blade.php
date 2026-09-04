@php
    $officialDecisionCopies = $competition->officialDecisionCopies()
        ->orderBy('id')
        ->get();
    $canManageOfficialDecision = isset($isCompetitionAdmin)
        && $isCompetitionAdmin
        && in_array($competition->status, ['closed', 'completed'], true);

    $liveCopies = $officialDecisionCopies
        ->filter(function ($copy) {
            return $copy->permanently_deleted_at === null
                && $copy->permanent_delete_pending_at === null;
        })
        ->values();

    $publishedCopy = $liveCopies->first(function ($copy) {
        return $copy->isCurrentlyPublished();
    });

    $withdrawnCopy = $liveCopies
        ->filter(function ($copy) {
            return $copy->hasBeenPublished() && ! $copy->isCurrentlyPublished();
        })
        ->sortByDesc('id')
        ->first();

    $neverPublishedLiveCopies = $liveCopies
        ->filter(function ($copy) {
            return ! $copy->hasBeenPublished();
        })
        ->sortByDesc('id')
        ->values();

    $currentNeverPublishedCopy = $neverPublishedLiveCopies->first();
    $pendingCopy = $officialDecisionCopies
        ->filter(function ($copy) {
            return $copy->permanently_deleted_at === null
                && $copy->permanent_delete_pending_at !== null;
        })
        ->sortByDesc('id')
        ->first();
    $hasNonTombstonedSignedCopyPublication = \App\Models\CompetitionOfficialDecisionCopy::competitionHasNonTombstonedSignedCopyPublication($competition->id);

    $officialDecisionState = 'empty';
    $currentOfficialDecisionCopy = null;
    $replacementCandidateCopy = null;
    $currentPublicNotice = null;
    $canFirstPublishCurrentCopy = false;

    if ($publishedCopy !== null) {
        $officialDecisionState = 'published';
        $currentOfficialDecisionCopy = $publishedCopy;
        $replacementCandidateCopy = $currentNeverPublishedCopy;
        $currentPublicNotice = $publishedCopy->currentPublicSignedCopyNotices()->first();
    } elseif ($withdrawnCopy !== null) {
        $officialDecisionState = 'withdrawn';
        $currentOfficialDecisionCopy = $withdrawnCopy;
    } elseif ($currentNeverPublishedCopy !== null) {
        $officialDecisionState = 'unpublished';
        $currentOfficialDecisionCopy = $currentNeverPublishedCopy;
        $canFirstPublishCurrentCopy = ! $hasNonTombstonedSignedCopyPublication;
    } elseif ($pendingCopy !== null) {
        $officialDecisionState = 'pending';
        $currentOfficialDecisionCopy = $pendingCopy;
    }
@endphp

<div class="info-card official-decision-card">
    <style>
        .official-decision-card .official-decision-field {
            margin-bottom: 12px;
        }
        .official-decision-card .official-decision-field label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .official-decision-card .official-decision-field input[type="text"],
        .official-decision-card .official-decision-field input[type="date"],
        .official-decision-card .official-decision-field input[type="file"] {
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
        .official-decision-card input[type="file"]::file-selector-button {
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
        .official-decision-card input[type="file"]::file-selector-button:hover {
            background: #0A347B;
        }
        .official-decision-card .official-decision-status {
            margin: 0 0 12px;
            color: #374151;
            font-size: 14px;
        }
        .official-decision-card .official-decision-help {
            margin: 8px 0 0;
            color: #6b7280;
            font-size: 13px;
        }
        .official-decision-card .official-decision-primary {
            margin-left: 0;
        }
        .official-decision-card .official-decision-manage {
            margin-top: 16px;
        }
        .official-decision-card .official-decision-manage > summary,
        .official-decision-card .official-decision-confirm > summary {
            cursor: pointer;
            font-weight: 600;
            color: #0B3D91;
            list-style: none;
        }
        .official-decision-card .official-decision-manage > summary::-webkit-details-marker,
        .official-decision-card .official-decision-confirm > summary::-webkit-details-marker {
            display: none;
        }
        .official-decision-card .official-decision-panel {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
        }
        .official-decision-card .official-decision-destructive {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #fecaca;
        }
        .official-decision-card .btn-danger {
            margin-left: 0;
            padding: 6px 12px;
            font-size: 13px;
        }
    </style>

    <h2 style="font-size: 20px; margin-bottom: 16px;">Zvanična Odluka</h2>

    @if($officialDecisionState === 'empty')
        @if($canManageOfficialDecision)
            <form class="official-decision-upload-form" method="POST" action="{{ route('admin.competitions.official-decision.store', $competition) }}" enctype="multipart/form-data">
                @csrf
                <div class="official-decision-field">
                    <label for="business_title">Naziv dokumenta</label>
                    <input type="text" id="business_title" name="business_title" value="{{ old('business_title') }}" maxlength="255" required>
                </div>
                <div class="official-decision-field">
                    <label for="official_decision_copy">Potpisani PDF</label>
                    <input type="file" id="official_decision_copy" name="official_decision_copy" accept="application/pdf" required>
                    <p class="official-decision-help">PDF do 2 MB.</p>
                </div>
                <button type="submit" class="btn btn-success official-decision-primary">Učitaj Odluku</button>
            </form>
        @endif
    @elseif($officialDecisionState === 'unpublished')
        <p class="official-decision-status"><strong>Naziv:</strong> {{ $currentOfficialDecisionCopy->business_title }}</p>
        <p class="official-decision-status"><strong>Status:</strong> Nije objavljena</p>
        <p class="official-decision-status"><strong>PDF:</strong> Učitan</p>

        @if($canManageOfficialDecision && $canFirstPublishCurrentCopy)
            <form class="official-decision-publish-form" method="POST" action="{{ route('admin.competitions.official-decision.publish', [$competition, $currentOfficialDecisionCopy]) }}">
                @csrf
                <div class="official-decision-field">
                    <label for="business_published_on_{{ $currentOfficialDecisionCopy->id }}">Datum objave</label>
                    <input type="date" id="business_published_on_{{ $currentOfficialDecisionCopy->id }}" name="business_published_on" value="{{ old('business_published_on') }}" max="{{ now()->toDateString() }}" required>
                </div>
                <button type="submit" class="btn btn-success official-decision-primary">Objavi Odluku</button>
            </form>
        @endif

        @if($canManageOfficialDecision)
            <div class="official-decision-destructive">
                <details class="official-decision-confirm">
                    <summary style="color: #991b1b;">Odustani i obriši Odluku</summary>
                    <form class="official-decision-permanent-delete-form" method="POST" action="{{ route('admin.competitions.official-decision.permanent-delete', [$competition, $currentOfficialDecisionCopy]) }}" style="margin-top: 12px;">
                        @csrf
                        <p class="official-decision-status">Obrisati učitanu Odluku?</p>
                        <p class="official-decision-help">Ovo je učitani PDF koji još nije objavljen. Trajno će biti fizički obrisan i ne može se vratiti kroz Platformu.</p>
                        <button type="button" class="btn btn-success" style="margin-left: 0; padding: 6px 12px; font-size: 13px; background: #e5e7eb; color: #111827;" onclick="this.closest('details').removeAttribute('open')">Odustani</button>
                        <button type="submit" class="btn btn-danger">Odustani i obriši Odluku</button>
                    </form>
                </details>
            </div>
        @endif
    @elseif($officialDecisionState === 'published')
        <p class="official-decision-status" style="font-size: 16px; font-weight: 600; color: #111827;">{{ $currentOfficialDecisionCopy->business_title }}</p>
        @if($currentOfficialDecisionCopy->business_published_on)
            <p class="official-decision-status">
                Objavljena:
                <time datetime="{{ $currentOfficialDecisionCopy->business_published_on->toDateString() }}">
                    {{ $currentOfficialDecisionCopy->business_published_on->format('d.m.Y') }}
                </time>.
            </p>
        @endif
        @if($currentPublicNotice)
            <p class="official-decision-status">
                <a href="{{ route('notices.public-content', $currentPublicNotice) }}" target="_blank" rel="noopener noreferrer">Otvori PDF</a>
            </p>
        @endif

        @if($canManageOfficialDecision)
            <details class="official-decision-manage">
                <summary>Upravljaj objavom</summary>
                <div class="official-decision-panel">
                    <details class="official-decision-confirm">
                        <summary>Ispravi podatke objave</summary>
                        <form class="official-decision-metadata-form" method="POST" action="{{ route('admin.competitions.official-decision.update-metadata', [$competition, $currentOfficialDecisionCopy]) }}" style="margin-top: 12px;">
                            @csrf
                            <div class="official-decision-field">
                                <label for="business_title_metadata_{{ $currentOfficialDecisionCopy->id }}">Naziv dokumenta</label>
                                <input type="text" id="business_title_metadata_{{ $currentOfficialDecisionCopy->id }}" name="business_title" value="{{ old('business_title', $currentOfficialDecisionCopy->business_title) }}" maxlength="255">
                            </div>
                            <div class="official-decision-field">
                                <label for="business_published_on_metadata_{{ $currentOfficialDecisionCopy->id }}">Datum objave</label>
                                <input type="date" id="business_published_on_metadata_{{ $currentOfficialDecisionCopy->id }}" name="business_published_on" value="{{ old('business_published_on', optional($currentOfficialDecisionCopy->business_published_on)?->toDateString()) }}" max="{{ now()->toDateString() }}">
                            </div>
                            <button type="submit" class="btn btn-success official-decision-primary" style="padding: 6px 12px; font-size: 13px;">Sačuvaj izmjene</button>
                        </form>
                    </details>

                    <details class="official-decision-confirm" style="margin-top: 16px;">
                        <summary>Zamijeni Odluku</summary>
                        <div style="margin-top: 12px;">
                            @if($replacementCandidateCopy)
                                <p class="official-decision-status"><strong>Naziv zamjenske Odluke:</strong> {{ $replacementCandidateCopy->business_title }}</p>
                                <p class="official-decision-status"><strong>Status:</strong> Nije objavljena</p>
                                <form class="official-decision-correct-form" method="POST" action="{{ route('admin.competitions.official-decision.correct', [$competition, $replacementCandidateCopy]) }}">
                                    @csrf
                                    <div class="official-decision-field">
                                        <label for="business_published_on_correct_{{ $replacementCandidateCopy->id }}">Datum objave</label>
                                        <input type="date" id="business_published_on_correct_{{ $replacementCandidateCopy->id }}" name="business_published_on" value="{{ old('business_published_on') }}" max="{{ now()->toDateString() }}" required>
                                    </div>
                                    <button type="submit" class="btn btn-success official-decision-primary" style="padding: 6px 12px; font-size: 13px;">Objavi zamjenu</button>
                                </form>
                            @else
                                <form class="official-decision-upload-form" method="POST" action="{{ route('admin.competitions.official-decision.store', $competition) }}" enctype="multipart/form-data">
                                    @csrf
                                    <div class="official-decision-field">
                                        <label for="replacement_business_title">Naziv dokumenta</label>
                                        <input type="text" id="replacement_business_title" name="business_title" value="{{ old('business_title') }}" maxlength="255" required>
                                    </div>
                                    <div class="official-decision-field">
                                        <label for="replacement_official_decision_copy">Novi potpisani PDF</label>
                                        <input type="file" id="replacement_official_decision_copy" name="official_decision_copy" accept="application/pdf" required>
                                        <p class="official-decision-help">PDF do 2 MB. Trenutna javna Odluka ostaje važeća dok zamjenu ne objavite.</p>
                                    </div>
                                    <button type="submit" class="btn btn-success official-decision-primary" style="padding: 6px 12px; font-size: 13px;">Učitaj zamjensku Odluku</button>
                                </form>
                            @endif
                        </div>
                    </details>

                    <details class="official-decision-confirm" style="margin-top: 16px;">
                        <summary>Povuci objavu</summary>
                        <form method="POST" action="{{ route('admin.competitions.official-decision.unpublish', [$competition, $currentOfficialDecisionCopy]) }}" style="margin-top: 12px;">
                            @csrf
                            <p class="official-decision-status">Da li želite da povučete ovu Odluku iz javne objave?</p>
                            <p class="official-decision-help">Odluka više neće biti javno dostupna, ali neće biti obrisana i moći ćete je ponovo objaviti.</p>
                            <button type="button" class="btn btn-success" style="margin-left: 0; padding: 6px 12px; font-size: 13px; background: #e5e7eb; color: #111827;" onclick="this.closest('details').removeAttribute('open')">Odustani</button>
                            <button type="submit" class="btn btn-success" style="padding: 6px 12px; font-size: 13px;">Povuci objavu</button>
                        </form>
                    </details>
                </div>
            </details>

            <div class="official-decision-destructive">
                <details class="official-decision-confirm">
                    <summary style="color: #991b1b;">Trajno obriši</summary>
                    <form class="official-decision-permanent-delete-form" method="POST" action="{{ route('admin.competitions.official-decision.permanent-delete', [$competition, $currentOfficialDecisionCopy]) }}" style="margin-top: 12px;">
                        @csrf
                        <p class="official-decision-status">Trajno obrisati Odluku?</p>
                        <p class="official-decision-help">Ova radnja trajno briše dokument i ne može se poništiti.</p>
                        <button type="button" class="btn btn-success" style="margin-left: 0; padding: 6px 12px; font-size: 13px; background: #e5e7eb; color: #111827;" onclick="this.closest('details').removeAttribute('open')">Odustani</button>
                        <button type="submit" class="btn btn-danger">Trajno obriši</button>
                    </form>
                </details>
            </div>
        @endif
    @elseif($officialDecisionState === 'withdrawn')
        <p class="official-decision-status" style="font-size: 16px; font-weight: 600; color: #111827;">{{ $currentOfficialDecisionCopy->business_title }}</p>
        <p class="official-decision-status"><strong>Status:</strong> Objava povučena</p>
        <p class="official-decision-status"><strong>PDF:</strong> Učitan</p>

        @if($canManageOfficialDecision)
            <form class="official-decision-republish-form" method="POST" action="{{ route('admin.competitions.official-decision.republish', [$competition, $currentOfficialDecisionCopy]) }}">
                @csrf
                <div class="official-decision-field">
                    <label for="business_title_republish_{{ $currentOfficialDecisionCopy->id }}">Naziv dokumenta</label>
                    <input type="text" id="business_title_republish_{{ $currentOfficialDecisionCopy->id }}" name="business_title" value="{{ old('business_title', $currentOfficialDecisionCopy->business_title) }}" maxlength="255" required>
                </div>
                <div class="official-decision-field">
                    <label for="business_published_on_republish_{{ $currentOfficialDecisionCopy->id }}">Datum objave</label>
                    <input type="date" id="business_published_on_republish_{{ $currentOfficialDecisionCopy->id }}" name="business_published_on" value="{{ old('business_published_on', optional($currentOfficialDecisionCopy->business_published_on)?->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                </div>
                <button type="submit" class="btn btn-success official-decision-primary">Ponovo objavi</button>
            </form>

            <div class="official-decision-destructive">
                <details class="official-decision-confirm">
                    <summary style="color: #991b1b;">Trajno obriši</summary>
                    <form class="official-decision-permanent-delete-form" method="POST" action="{{ route('admin.competitions.official-decision.permanent-delete', [$competition, $currentOfficialDecisionCopy]) }}" style="margin-top: 12px;">
                        @csrf
                        <p class="official-decision-status">Trajno obrisati Odluku?</p>
                        <p class="official-decision-help">Ova radnja trajno briše dokument i ne može se poništiti.</p>
                        <button type="button" class="btn btn-success" style="margin-left: 0; padding: 6px 12px; font-size: 13px; background: #e5e7eb; color: #111827;" onclick="this.closest('details').removeAttribute('open')">Odustani</button>
                        <button type="submit" class="btn btn-danger">Trajno obriši</button>
                    </form>
                </details>
            </div>
        @endif
    @endif

    @if($pendingCopy && $canManageOfficialDecision)
        <div class="official-decision-destructive">
            <p class="official-decision-status"><strong>Trajno brisanje Odluke nije završeno.</strong></p>
            <p class="official-decision-help">Potpisani PDF još nije fizički uklonjen. Ova Odluka nije aktivna objava.</p>
            <form class="official-decision-permanent-delete-form" method="POST" action="{{ route('admin.competitions.official-decision.permanent-delete', [$competition, $pendingCopy]) }}" style="margin-top: 12px;">
                @csrf
                <button type="submit" class="btn btn-danger">Ponovi trajno brisanje</button>
            </form>
        </div>
    @endif
</div>
