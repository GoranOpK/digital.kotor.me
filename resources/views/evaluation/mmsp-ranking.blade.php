@extends('layouts.app')

@section('content')
@php
    $competition->loadMissing('upNumber');
@endphp
<style>
    .mmsp-ranking-print-root {
        --mmsp-ranking-primary: #0B3D91;
        margin-bottom: 24px;
    }
    .mmsp-ranking-print-root.is-blocked,
    .mmsp-ranking-list-card {
        background: #fff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .mmsp-ranking-print-root.is-blocked {
        border-left: 4px solid #8b5cf6;
    }
    .mmsp-ranking-print-root .obrazac-zaglavlje {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }
    .mmsp-ranking-print-root .obrazac-zaglavlje-top {
        display: flex;
        align-items: flex-start;
        gap: 20px;
    }
    .mmsp-ranking-print-root .obrazac-grb {
        flex-shrink: 0;
        line-height: 0;
    }
    .mmsp-ranking-print-root .obrazac-grb img {
        height: 2cm;
        width: auto;
        display: block;
    }
    .mmsp-ranking-print-root .obrazac-org {
        flex: 1;
        font-size: 13px;
        line-height: 1.5;
        color: #111;
    }
    .mmsp-ranking-print-root .obrazac-org p { margin: 0 0 2px 0; }
    .mmsp-ranking-print-root .obrazac-contact {
        text-align: right;
        font-size: 13px;
        line-height: 1.5;
        color: #111;
    }
    .mmsp-ranking-print-root .obrazac-contact p { margin: 0 0 2px 0; }
    .mmsp-ranking-print-root .obrazac-broj-i-naslov {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #e5e7eb;
    }
    .mmsp-ranking-print-root .obrazac-broj-prijave {
        font-size: 13px;
        color: #374151;
        font-weight: 500;
    }
    .mmsp-ranking-print-root .obrazac-naslov-prijava {
        width: 100%;
        text-align: center;
        font-size: 22px;
        font-weight: 700;
        letter-spacing: 0.02em;
        color: #111;
        margin: 8px 0 0 0;
        text-transform: uppercase;
    }
    .mmsp-ranking-print-root .obrazac-podnaslov {
        text-align: center;
        font-size: 13px;
        line-height: 1.5;
        color: #374151;
        margin: 4px 0 0 0;
    }
    .mmsp-ranking-list-card > h2 {
        font-size: 20px;
        font-weight: 700;
        color: var(--mmsp-ranking-primary);
        margin: 0 0 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e5e7eb;
    }
    .mmsp-ranking-print-root .btn-primary {
        background: var(--mmsp-ranking-primary);
        color: #fff;
        padding: 10px 16px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
    }
    .mmsp-ranking-table-wrap {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .mmsp-ranking-print-root table.mmsp-ranking-table {
        width: 100%;
        min-width: 640px;
        border-collapse: collapse;
        margin-bottom: 0;
    }
    .mmsp-ranking-print-root table.mmsp-ranking-table th,
    .mmsp-ranking-print-root table.mmsp-ranking-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
        vertical-align: top;
    }
    .mmsp-ranking-print-root table.mmsp-ranking-table th {
        font-weight: 600;
        color: #374151;
        font-size: 12px;
        text-transform: uppercase;
        background: #f9fafb;
    }
    .mmsp-ranking-print-root .ranking-badge {
        display: inline-block;
        width: 32px;
        height: 32px;
        background: var(--mmsp-ranking-primary);
        color: #fff;
        border-radius: 50%;
        text-align: center;
        line-height: 32px;
        font-weight: 700;
        font-size: 14px;
    }
    .mmsp-ranking-print-root .score-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 9999px;
        font-size: 14px;
        font-weight: 600;
        white-space: nowrap;
    }
    .mmsp-ranking-print-root .score-high {
        background: #d1fae5;
        color: #065f46;
    }
    .mmsp-ranking-print-root .score-medium {
        background: #fef3c7;
        color: #92400e;
    }
    .mmsp-ranking-print-root .score-low {
        background: #fee2e2;
        color: #991b1b;
    }
    .mmsp-ranking-print-root .mmsp-ranking-print-btn {
        background: #6b7280;
        color: #fff;
        border: none;
        cursor: pointer;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
    }
    .mmsp-ranking-print-root .mmsp-ranking-decision-block {
        background: #f9fafb;
        padding: 24px;
        border-radius: 12px;
        margin-bottom: 24px;
        border: 1px solid #e5e7eb;
    }
    .mmsp-ranking-print-root .mmsp-ranking-decision-form input[type="text"],
    .mmsp-ranking-print-root .mmsp-ranking-decision-form select,
    .mmsp-ranking-print-root .mmsp-ranking-decision-form textarea {
        background: #ffffff;
        color: #111827;
        border: 1px solid #9ca3af;
        border-radius: 6px;
        padding: 8px 10px;
        font-size: 14px;
        line-height: 1.4;
        box-sizing: border-box;
        width: 100%;
    }
    .mmsp-ranking-print-root .mmsp-ranking-decision-form input[type="text"]:focus,
    .mmsp-ranking-print-root .mmsp-ranking-decision-form select:focus,
    .mmsp-ranking-print-root .mmsp-ranking-decision-form textarea:focus {
        outline: 2px solid #2563eb;
        outline-offset: 1px;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.25);
    }
    .mmsp-ranking-note { margin-top: 6px; font-size: 12px; color: #6b7280; }
    .mmsp-ranking-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: 8px; padding: 12px; margin-bottom: 16px; }
    @media (max-width: 768px) {
        .mmsp-ranking-print-root .obrazac-zaglavlje-top { flex-wrap: wrap; }
        .mmsp-ranking-print-root .obrazac-contact { text-align: left; }
        .mmsp-ranking-print-root .obrazac-naslov-prijava { font-size: 18px; }
        .mmsp-ranking-print-root table.mmsp-ranking-table th,
        .mmsp-ranking-print-root table.mmsp-ranking-table td {
            padding: 8px;
            font-size: 12px;
        }
        .mmsp-ranking-print-root .ranking-badge {
            width: 28px;
            height: 28px;
            line-height: 28px;
            font-size: 12px;
        }
    }
    @media print {
        nav, header, footer,
        .mmsp-ranking-no-print,
        .no-print,
        .mmsp-ranking-decision-section,
        .mmsp-ranking-decision-form,
        form, button, select, textarea, input {
            display: none !important;
        }
        body.mmsp-ranking-print .container > *:not(.mmsp-ranking-print-root) {
            display: none !important;
        }
        body.mmsp-ranking-print .mmsp-ranking-print-root {
            position: static;
            box-shadow: none;
            border: none;
            border-radius: 0;
            padding: 0;
            margin: 0;
            background: #fff;
        }
        .mmsp-ranking-print-root .obrazac-zaglavlje,
        .mmsp-ranking-list-card {
            box-shadow: none;
            border: none !important;
            border-radius: 0;
            padding: 0 0 8px 0;
            margin-bottom: 8px;
        }
        .mmsp-ranking-print-root table.mmsp-ranking-table {
            min-width: 0;
            font-size: 8pt;
        }
        .mmsp-ranking-print-root table.mmsp-ranking-table th,
        .mmsp-ranking-print-root table.mmsp-ranking-table td {
            border: 1px solid #000 !important;
            padding: 3px 4px !important;
            font-size: 8pt !important;
            background: transparent !important;
            color: #000 !important;
        }
        .mmsp-ranking-print-root .ranking-badge {
            background: transparent !important;
            color: #000 !important;
            border: 1px solid #000;
            width: 22px;
            height: 22px;
            line-height: 22px;
            font-size: 8pt;
        }
        .mmsp-ranking-print-root .score-badge {
            background: transparent !important;
            color: #000 !important;
            border: none;
            padding: 0;
            font-size: 8pt;
        }
        .mmsp-ranking-print-root .below-minimum-heading {
            background: transparent !important;
            color: #000 !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 0 6px 0 !important;
            font-size: 9pt !important;
        }
        .mmsp-ranking-table-wrap { overflow: visible; }
    }
</style>
<script>
    window.printMmspRanking = function (button) {
        var root = button && typeof button.closest === 'function'
            ? button.closest('.mmsp-ranking-print-root')
            : null;
        if (!root) {
            return;
        }
        var cleaned = false;
        var done = function () {
            if (cleaned) {
                return;
            }
            cleaned = true;
            document.body.classList.remove('mmsp-ranking-print');
            root.classList.remove('mmsp-ranking-print-target');
            window.removeEventListener('afterprint', done);
        };
        window.addEventListener('afterprint', done);
        document.body.classList.add('mmsp-ranking-print');
        root.classList.add('mmsp-ranking-print-target');
        window.print();
    };
</script>

<div class="admin-page" style="background: #f9fafb; min-height: 100vh; padding: 24px 0;">
<div class="container mx-auto px-4">
    @if(session('success'))
        <div class="alert no-print" role="status" style="background: #ecfdf5; border: 1px solid #10b981; color: #065f46; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="mmsp-ranking-error no-print" data-testid="mmsp-ranking-error">{{ $errors->first() }}</div>
    @endif

    @if(! ($rankingView['visible'] ?? false))
        <div class="mmsp-ranking-print-root is-blocked" data-testid="mmsp-ranking-page">
            <div data-testid="mmsp-ranking-blocked">
                <strong>{{ $competition->title }}</strong>
                <p style="margin: 8px 0 0; color: #92400e; font-weight: 600;">{{ $rankingView['block_reason'] ?? \App\Services\CanonicalIndividualScoringService::CANONICAL_CYCLE_INCOMPLETE_MESSAGE }}</p>
            </div>
        </div>
    @else
        @php
            $supportThreshold = \App\Services\CanonicalIndividualScoringService::CANONICAL_SUPPORT_THRESHOLD;
            $above = [];
            $below = [];
            foreach ($outcomeRows as $row) {
                if (bccomp((string) $row->display, $supportThreshold, 2) >= 0) {
                    $above[] = $row;
                } else {
                    $below[] = $row;
                }
            }
            $mmspApplicantName = static function ($row): string {
                $applicantForm = $row->application->canonicalApplicantForm;
                $applicantName = $applicantForm?->legal_form === \App\Support\CanonicalLegalForm::DRUSTVO
                    ? trim((string) $applicantForm->company_name)
                    : trim((string) ($applicantForm->person_name ?? ''));
                if ($applicantName === '') {
                    $applicantName = $row->application->founder_name ?? '—';
                }

                return $applicantName;
            };
            $mmspScoreText = static fn ($row): string => number_format((float) $row->display, 2).' / 50';
            $mmspScoreClass = static function ($row): string {
                $score = (float) $row->display;
                if ($score >= 40) {
                    return 'score-high';
                }

                return $score >= 30 ? 'score-medium' : 'score-low';
            };
        @endphp

        <div class="mmsp-ranking-print-root" data-testid="mmsp-ranking-page">
            <div class="obrazac-zaglavlje">
                <div class="obrazac-zaglavlje-top">
                    <div class="obrazac-grb">
                        <img src="{{ asset('images/srednji_grb.png') }}" alt="Grb Opštine Kotor" onerror="this.onerror=null; this.src='{{ asset('images/srednji_grb.svg') }}';">
                    </div>
                    <div class="obrazac-org">
                        <p><strong>Crna Gora</strong></p>
                        <p>Opština Kotor</p>
                        <p>Sekretarijat za razvoj preduzetništva</p>
                        <p>komunalne poslove i saobraćaj</p>
                    </div>
                    <div class="obrazac-contact">
                        <p>Stari grad 317</p>
                        <p>85330 Kotor, Crna Gora</p>
                        <p>tel. +382(0)32 325 865</p>
                        <p>privreda@kotor.me</p>
                        <p>www.kotor.me</p>
                    </div>
                </div>
                <div class="obrazac-broj-i-naslov">
                    <div class="obrazac-broj-prijave">
                        Konkurs: {{ $competition->upNumber?->number ?? '—' }} / {{ $competition->year ?? '' }}
                    </div>
                </div>
                <h1 class="obrazac-naslov-prijava">RANG LISTA PLANOVA ULAGANJA</h1>
                <p class="obrazac-podnaslov">{{ $competition->title }}</p>
            </div>

            <div class="mmsp-ranking-list-card">
                <h2 data-testid="mmsp-ranking-title">{{ $canonicalRankingIsFinal ? 'Konačna rang-lista' : 'Preliminarna rang-lista' }}</h2>
                <p style="color: #4b5563; margin-bottom: 16px;">
                    Planovi ispod 30 bodova ostaju na listi i ne mogu biti podržani.
                    Isti rang znači stvarno jednak konačni rezultat; redoslijed unutar istog ranga je tehnički i ne daje prednost.
                </p>

                @if(count($above) > 0)
                <div class="mmsp-ranking-table-wrap">
                    <table class="mmsp-ranking-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">Poz.</th>
                                <th>Vrsta subvencije</th>
                                <th>Podnosilac</th>
                                <th style="text-align: center;">Ocjena</th>
                                <th style="text-align: right;">Potrebna sredstva</th>
                                <th style="text-align: right;">Odobrena sredstva</th>
                                <th style="text-align: center;">Odluka</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($above as $row)
                                @php $outcome = $outcomeRecords->get($row->id()); @endphp
                                <tr data-testid="mmsp-ranking-row">
                                    <td><span class="ranking-badge">{{ $row->rank }}</span></td>
                                    <td>{{ $row->subsidyType && \App\Support\CanonicalSubsidyType::isValid($row->subsidyType) ? \App\Support\CanonicalSubsidyType::label($row->subsidyType) : '—' }}</td>
                                    <td>{{ $mmspApplicantName($row) }}</td>
                                    <td style="text-align: center;">
                                        <span class="score-badge {{ $mmspScoreClass($row) }}">{{ $mmspScoreText($row) }}</span>
                                    </td>
                                    <td style="text-align: right;">{{ $row->totalInvestment !== null ? number_format((float) $row->totalInvestment, 2, ',', '.') : '—' }}</td>
                                    <td style="text-align: right;">
                                        @if($outcome?->isSupport())
                                            <strong>{{ number_format((float) $outcome->approved_amount, 2, ',', '.') }}</strong>
                                        @else
                                            <span style="color: #6b7280;">—</span>
                                        @endif
                                    </td>
                                    <td style="text-align: center;">
                                        @if($outcome)
                                            <span class="score-badge {{ $outcome->isSupport() ? 'score-high' : 'score-low' }}">{{ $outcome->isSupport() ? 'Podržava' : 'Odbija' }}</span>
                                            @if(filled($outcome->justification))<div style="white-space: pre-wrap; text-align: left; margin-top: 6px;">{{ $outcome->justification }}</div>@endif
                                            @if(filled($outcome->remarks))<div style="white-space: pre-wrap; text-align: left; margin-top: 6px;">{{ $outcome->remarks }}</div>@endif
                                        @else
                                            <span style="color: #6b7280;">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                @if(count($below) > 0)
                <div class="below-minimum-section" style="margin-top: 48px; padding-top: 32px; border-top: 4px solid #374151; box-shadow: 0 -2px 8px rgba(0,0,0,0.08);">
                    <div class="below-minimum-heading" style="background: #374151; color: #fff; padding: 14px 24px; margin-top: -32px; margin-bottom: 24px; border-radius: 8px; font-weight: 700; font-size: 16px; text-align: center; display: inline-block; width: 100%; box-sizing: border-box;">
                        ——— Ispod praga od 30 bodova ———
                    </div>
                    <h3 style="font-size: 16px; font-weight: 600; color: #6b7280; margin-bottom: 16px;">Planovi sa manje od 30 bodova</h3>
                    <div class="mmsp-ranking-table-wrap">
                        <table class="mmsp-ranking-table">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">Poz.</th>
                                    <th>Vrsta subvencije</th>
                                    <th>Podnosilac</th>
                                    <th style="text-align: center;">Ocjena</th>
                                    <th style="text-align: right;">Potrebna sredstva</th>
                                    <th style="text-align: right;">Odobrena sredstva</th>
                                    <th style="text-align: center;">Odluka</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($below as $row)
                                    @php $outcome = $outcomeRecords->get($row->id()); @endphp
                                    <tr data-testid="mmsp-ranking-row" style="background: #f9fafb;">
                                        <td><span class="ranking-badge">{{ $row->rank }}</span></td>
                                        <td>{{ $row->subsidyType && \App\Support\CanonicalSubsidyType::isValid($row->subsidyType) ? \App\Support\CanonicalSubsidyType::label($row->subsidyType) : '—' }}</td>
                                        <td>{{ $mmspApplicantName($row) }}</td>
                                        <td style="text-align: center;">
                                            <span class="score-badge score-low">{{ $mmspScoreText($row) }}</span>
                                        </td>
                                        <td style="text-align: right;">{{ $row->totalInvestment !== null ? number_format((float) $row->totalInvestment, 2, ',', '.') : '—' }}</td>
                                        <td style="text-align: right;">
                                            @if($outcome?->isSupport())
                                                <strong>{{ number_format((float) $outcome->approved_amount, 2, ',', '.') }}</strong>
                                            @else
                                                <span style="color: #6b7280;">—</span>
                                            @endif
                                        </td>
                                        <td style="text-align: center;">
                                            <div class="mmsp-ranking-note">Ispod praga od 30 bodova. Ne može se podržati.</div>
                                            @if($outcome)
                                                <span class="score-badge score-low">{{ $outcome->isSupport() ? 'Podržava' : 'Odbija' }}</span>
                                                @if(filled($outcome->justification))<div style="white-space: pre-wrap; text-align: left; margin-top: 6px;">{{ $outcome->justification }}</div>@endif
                                                @if(filled($outcome->remarks))<div style="white-space: pre-wrap; text-align: left; margin-top: 6px;">{{ $outcome->remarks }}</div>@endif
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                @if($canonicalChair && ! $canonicalRankingIsFinal && count($outcomeRows) > 0)
                <div class="mmsp-ranking-decision-section no-print" style="background: transparent; box-shadow: none; padding: 24px 0 0; margin-bottom: 0;">
                    <h2>Odluka</h2>
                    @foreach($outcomeRows as $row)
                        @php
                            $outcome = $outcomeRecords->get($row->id());
                            $belowThreshold = bccomp((string) $row->display, '30', 2) < 0;
                        @endphp
                        <div class="mmsp-ranking-decision-block">
                            <div style="margin-bottom: 16px; padding-bottom: 16px; border-bottom: 2px solid #e5e7eb;">
                                <h3 style="font-size: 18px; font-weight: 700; color: #0B3D91; margin: 0 0 4px 0;">{{ $mmspApplicantName($row) }}</h3>
                                <p style="font-size: 14px; color: #6b7280; margin: 0;">
                                    Vrsta subvencije: {{ $row->subsidyType && \App\Support\CanonicalSubsidyType::isValid($row->subsidyType) ? \App\Support\CanonicalSubsidyType::label($row->subsidyType) : '—' }} |
                                    Pozicija: <strong>#{{ $row->rank }}</strong> |
                                    Ocjena: <strong>{{ $mmspScoreText($row) }}</strong>
                                </p>
                            </div>
                            @if($belowThreshold)
                                <p class="mmsp-ranking-note" style="margin: 0 0 12px;">Ispod praga od 30 bodova. Ne može se podržati.</p>
                            @endif
                            <form method="POST" action="{{ route('evaluation.canonical-outcome.draft', [$competition, $row->application]) }}" class="mmsp-ranking-decision-form no-print">
                                @csrf
                                <label style="display: block; font-size: 15px; font-weight: 600; color: #374151; margin-bottom: 8px;">Odluka</label>
                                <select name="decision" required style="margin-bottom: 16px;">
                                    <option value="support" @selected($outcome?->decision === 'support')>Podržava</option>
                                    <option value="reject" @selected($outcome?->decision === 'reject')>Odbija</option>
                                </select>
                                <label style="display: block; font-size: 15px; font-weight: 600; color: #374151; margin-bottom: 8px;">Iznos €</label>
                                <input type="text" name="amount" inputmode="decimal" placeholder="Iznos €" value="{{ $outcome?->approved_amount }}" style="margin-bottom: 16px;">
                                <label style="display: block; font-size: 15px; font-weight: 600; color: #374151; margin-bottom: 8px;">Razlog odbijanja</label>
                                <select name="reason" style="margin-bottom: 16px;">
                                    <option value="">— razlog odbijanja —</option>
                                    @foreach($reasonLabels as $code => $label)
                                        <option value="{{ $code }}" @selected($outcome?->rejection_reason === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <label style="display: block; font-size: 15px; font-weight: 600; color: #374151; margin-bottom: 8px;">Obrazloženje</label>
                                <textarea name="justification" rows="3" placeholder="Obrazloženje" style="margin-bottom: 16px;">{{ $outcome?->justification }}</textarea>
                                <label style="display: block; font-size: 15px; font-weight: 600; color: #374151; margin-bottom: 8px;">Napomene i mišljenja</label>
                                <textarea name="remarks" rows="2" placeholder="Napomene i mišljenja" style="margin-bottom: 16px;">{{ $outcome?->remarks }}</textarea>
                                <div style="text-align: right;">
                                    <button type="submit" class="btn-primary">Evidentiraj</button>
                                </div>
                            </form>
                        </div>
                    @endforeach
                </div>
                @endif
            </div>

            @if($canonicalRankingIsFinal)
                <div class="info-card" id="signature-block" data-testid="mmsp-ranking-signatures" style="padding: 16px 24px;">
                    <h2 style="margin-bottom: 12px; font-size: 18px;">Potpis komisije</h2>
                    <div style="margin-top: 16px; font-size: 13px; line-height: 1.5;">
                        <p style="margin-bottom: 14px;">Kotor, _______________ god.</p>
                        @php $clanNum = 0; @endphp
                        @foreach($signatories as $member)
                            <div style="margin-bottom: 6px;">
                                <div style="font-weight: 600; color: #374151;">{{ $member->position === 'predsjednik' ? 'Predsjednik Komisije' : 'Član '.(++$clanNum) }}</div>
                                <div style="margin: 2px 0 0 0;">{{ $member->name }}</div>
                                <div style="width: 200px; border-bottom: 1px solid #111; min-height: 1px; margin-top: 8px;"></div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="mmsp-ranking-no-print no-print" style="margin: 24px 0 8px 0; text-align: center;">
                    <button type="button" onclick="printMmspRanking(this);" class="mmsp-ranking-print-btn" data-testid="mmsp-ranking-print">Štampaj rang-listu</button>
                </div>
                @if($canonicalChair)
                    <div class="no-print" style="margin: 24px 0 8px 0; text-align: center;">
                        <a href="{{ route('evaluation.mmsp-decision', $competition) }}" data-testid="mmsp-decision-open" class="btn btn-primary" style="display: inline-block; background: #0B3D91; color: #fff; border: 0; border-radius: 8px; padding: 10px 20px; font-weight: 600; text-decoration: none;">Generiši Word odluku</a>
                    </div>
                @endif
            @endif
        </div>
    @endif

    <div class="no-print" style="margin: 24px 0 8px 0; text-align: center;">
        <a href="{{ route('evaluation.index') }}" class="btn btn-primary" style="display: inline-block; margin: 0 8px; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; background: #0B3D91; color: #fff;">Nazad</a>
    </div>
</div>
</div>
@endsection
