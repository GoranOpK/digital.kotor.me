@extends('layouts.app')

@section('content')
@include('partials.canonical-ui')
<style>
/* Screen overrides scoped to canonical MMSP P2. Shared canonical-ui rules stay unchanged. */
.canonical-ui-page.mmsp-business-plan .mmsp-obrazac-top {
    margin-bottom: 0;
}
.canonical-ui-page.mmsp-business-plan #p2-save-button {
    padding: 12px 24px;
    border: 0;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    color: #fff;
}
.canonical-ui-page.mmsp-business-plan #p2-save-button.mmsp-p2-save-draft {
    background: #6b7280;
}
.canonical-ui-page.mmsp-business-plan #p2-save-button.mmsp-p2-save-draft:hover {
    background: #4b5563;
}
.canonical-ui-page.mmsp-business-plan #p2-save-button.mmsp-p2-save-ready {
    background: #0B3D91;
}
.canonical-ui-page.mmsp-business-plan #p2-save-button.mmsp-p2-save-ready:hover {
    background: #0A347B;
}
.canonical-ui-page.mmsp-business-plan .mmsp-p2-locked {
    background: #f3f4f6;
    color: #374151;
    cursor: not-allowed;
}
/* Screen rhythm from Obrazac M2 (.obrazac-form-page in obrazac-form-styles). */
.canonical-ui-page.mmsp-business-plan .mmsp-form-control {
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
}
.canonical-ui-page.mmsp-business-plan .mmsp-form-label {
    font-size: 14px;
    font-weight: 600;
}
.canonical-ui-page.mmsp-business-plan .mmsp-form-text {
    font-size: 12px;
}
.canonical-ui-page.mmsp-business-plan .mmsp-form-section h2 {
    font-size: 20px;
}
.canonical-ui-page.mmsp-business-plan .mmsp-obrazac-title {
    font-size: 22px;
}
.canonical-ui-page.mmsp-business-plan .mmsp-form-group {
    margin-bottom: 20px;
}
.canonical-ui-page.mmsp-business-plan .mmsp-form-row,
.canonical-ui-page.mmsp-business-plan .mmsp-form-row-3 {
    gap: 20px;
}
.canonical-ui-page.mmsp-business-plan .mmsp-form-section > .mmsp-form-row {
    margin-bottom: 20px;
}
.canonical-ui-page.mmsp-business-plan .mmsp-form-section > .mmsp-form-text {
    margin-top: 4px;
    margin-bottom: 20px;
}
.canonical-ui-page.mmsp-business-plan textarea.mmsp-form-control {
    min-height: 80px;
    resize: vertical;
    font-family: inherit;
}
.canonical-ui-page.mmsp-business-plan textarea.mmsp-form-control[rows="2"] {
    min-height: 60px;
}
.canonical-ui-page.mmsp-business-plan textarea.mmsp-form-control[rows="1"] {
    min-height: 40px;
}
.canonical-ui-page.mmsp-business-plan .mmsp-dynamic-table .mmsp-p2-expense-remove {
    width: 1%;
    white-space: nowrap;
    vertical-align: middle;
}
.canonical-ui-page.mmsp-business-plan .mmsp-p2-add-row,
.canonical-ui-page.mmsp-business-plan .mmsp-p2-remove-row {
    background: #6b7280;
    color: #fff;
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
}
.canonical-ui-page.mmsp-business-plan .mmsp-p2-add-row:hover,
.canonical-ui-page.mmsp-business-plan .mmsp-p2-remove-row:hover {
    background: #4b5563;
}
.canonical-ui-page.mmsp-business-plan .mmsp-dynamic-table input,
.canonical-ui-page.mmsp-business-plan .mmsp-dynamic-table textarea {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    box-sizing: border-box;
}
@media (max-width: 640px) {
    .canonical-ui-page.mmsp-business-plan .mmsp-obrazac-top {
        flex-direction: row;
        flex-wrap: nowrap;
        align-items: flex-start;
        max-width: 100%;
    }
    .canonical-ui-page.mmsp-business-plan .mmsp-obrazac-org,
    .canonical-ui-page.mmsp-business-plan .mmsp-obrazac-contact {
        flex: 1 1 0;
        min-width: 0;
        overflow-wrap: anywhere;
    }
    .canonical-ui-page.mmsp-business-plan .mmsp-obrazac-contact {
        text-align: right;
    }
}
/* Print-only, scoped to canonical MMSP P2. */
@media print {
    @page { size: A4 portrait; margin: 12mm 10mm; }
    body > header, body > nav, body > footer,
    .mmsp-business-plan .mmsp-alert,
    .mmsp-business-plan .mmsp-p2-screen-only,
    .mmsp-business-plan button,
    .mmsp-business-plan .mmsp-p2-actions,
    .mmsp-business-plan tr.mmsp-p2-expense-print-hide,
    .mmsp-business-plan .mmsp-p2-expense-remove { display:none !important; }
    .canonical-ui-page.mmsp-form-page.mmsp-business-plan {
        padding:0 !important; margin:0 !important; min-height:0 !important; background:#fff !important;
    }
    .mmsp-business-plan > .container {
        padding:0 !important; margin:0 !important; width:100% !important; max-width:none !important;
    }
    .mmsp-business-plan .mmsp-obrazac-zaglavlje {
        background:#fff !important; box-shadow:none !important;
        border:1px solid #ccc !important; border-radius:12px !important;
        padding:12px 16px !important; margin:0 0 12px !important;
        break-inside:avoid;
    }
    .mmsp-business-plan .mmsp-obrazac-grb img { height:2cm !important; width:auto !important; }
    .mmsp-business-plan .mmsp-obrazac-org,
    .mmsp-business-plan .mmsp-obrazac-contact { font-size:11.7px !important; }
    .mmsp-business-plan .mmsp-obrazac-number,
    .mmsp-business-plan .mmsp-obrazac-code { font-size:12.6px !important; }
    .mmsp-business-plan .mmsp-obrazac-title { font-size:19.8px !important; }
    .mmsp-business-plan form, .mmsp-business-plan fieldset { margin:0 !important; padding:0 !important; width:100% !important; }
    .mmsp-business-plan .mmsp-form-card {
        background:transparent !important; box-shadow:none !important; padding:0 !important;
        margin:0 0 10px !important; border:0 !important; border-radius:0 !important;
    }
    .mmsp-business-plan .mmsp-form-section { margin:0 0 14px !important; break-inside:auto; page-break-inside:auto; }
    .mmsp-business-plan .mmsp-form-section h2 {
        font-size:12pt !important; margin:0 0 8px !important; padding-bottom:4px !important;
        color:var(--canonical-primary) !important; border-bottom:1px solid var(--canonical-border) !important;
        break-after:avoid; page-break-after:avoid;
    }
    .mmsp-business-plan .mmsp-form-section > .mmsp-form-text {
        margin-bottom:6px !important;
        break-after:avoid; page-break-after:avoid;
    }
    .mmsp-business-plan .mmsp-section-number {
        background:transparent !important; color:var(--canonical-primary) !important;
        border:1px solid var(--canonical-primary) !important; border-radius:0 !important;
        width:auto !important; height:auto !important; line-height:1.2 !important;
        padding:0 5px !important; font-size:11pt !important;
    }
    .mmsp-business-plan .mmsp-form-group { margin-bottom:6px !important; break-inside:avoid; page-break-inside:avoid; }
    .mmsp-business-plan .mmsp-form-group:has(textarea) {
        break-inside:auto !important; page-break-inside:auto !important;
    }
    .mmsp-business-plan .mmsp-form-label {
        font-size:9.9pt !important; font-weight:600 !important; margin-bottom:2px !important;
        color:#374151 !important;
    }
    .mmsp-business-plan .mmsp-form-control {
        font-size:10pt !important; line-height:1.4 !important; box-sizing:border-box !important;
        width:100% !important; max-width:100% !important; min-width:0 !important;
        padding:1px 0 !important; border:none !important; border-radius:0 !important;
        box-shadow:none !important; background:transparent !important; color:#111 !important;
        min-height:0 !important;
    }
    .mmsp-business-plan textarea.mmsp-form-control {
        border:1px solid #d1d5db !important; background:#fff !important;
        padding:6px 8px !important; margin-top:2px !important;
        min-height:5em !important; height:auto !important; max-height:none !important;
        resize:none !important; overflow:visible !important; field-sizing:content;
        break-inside:auto; page-break-inside:auto; font-size:10pt !important;
    }
    .mmsp-business-plan .mmsp-form-section > .mmsp-form-row { margin-bottom:6px !important; }
    .mmsp-business-plan .mmsp-form-row { grid-template-columns:minmax(0,1fr) minmax(0,1fr) !important; gap:20px !important; }
    .mmsp-business-plan .mmsp-form-row-3 { grid-template-columns:repeat(3,minmax(0,1fr)) !important; gap:20px !important; }
    .mmsp-business-plan [style*="overflow-x:auto"] {
        overflow:visible !important; break-inside:auto; page-break-inside:auto;
    }
    .mmsp-business-plan .mmsp-dynamic-table {
        width:100% !important; border-collapse:collapse !important; border-spacing:0 !important;
        table-layout:fixed !important; font-size:9pt !important; margin:8px 0 !important;
        break-inside:auto; page-break-inside:auto;
    }
    .mmsp-business-plan .mmsp-dynamic-table th,
    .mmsp-business-plan .mmsp-dynamic-table td {
        border:1px solid var(--canonical-border) !important; padding:3px 5px !important; font-size:9pt !important;
        background:transparent !important;
    }
    .mmsp-business-plan .mmsp-dynamic-table th { font-weight:bold !important; color:#374151 !important; }
    .mmsp-business-plan .mmsp-dynamic-table .mmsp-form-control,
    .mmsp-business-plan .mmsp-dynamic-table textarea.mmsp-form-control {
        border:none !important; background:transparent !important; box-shadow:none !important;
        padding:1px 0 !important; min-height:0 !important; margin-top:0 !important;
        font-size:9pt !important; border-radius:0 !important;
    }
    .mmsp-business-plan .mmsp-dynamic-table tr { break-inside:avoid; page-break-inside:avoid; }
    .mmsp-business-plan .mmsp-required,
    .mmsp-business-plan .error-message { display:none !important; }
}
</style>
@php
    $competition = $application->competition;
    $lines = old('expense_lines', $plan?->expenseLines() ?? []);
    $lines = is_array($lines) ? array_values($lines) : [];
    if (! $readOnly && $lines === []) {
        $lines = [['cost_type' => '', 'description' => '', 'amount' => '']];
    }
    $upBroj = $competition?->upNumber?->number ?? '—';
    $redniBroj = $application->redni_broj ?? '—';
    $brojPrijave = $upBroj.'/'.$redniBroj;
    $p1 = $application->canonicalApplicantForm;
    $p1Entrepreneur = $p1?->isEntrepreneur() ?? false;
    $p1Code = $p1?->formCode() ?? 'P1a/P1b';
    $showBasic = function (mixed $value): string {
        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    };
    $basicFields = $p1Entrepreneur
        ? [
            ['Ime i prezime preduzetnika', $showBasic($p1?->person_name)],
            ['Adresa preduzetnika', $showBasic($p1?->address)],
            ['PIB preduzetnika', $showBasic($application->pib)],
            ['Šifra djelatnosti', $showBasic($p1?->activity_code)],
            ['Broj zaposlenih', $showBasic($p1?->employee_count)],
            ['Kontakt telefon', $showBasic($p1?->phone)],
            ['E-mail', $showBasic($p1?->email)],
        ]
        : [
            ['Naziv društva', $showBasic($p1?->company_name)],
            ['Ime i prezime odgovornog lica', $showBasic($p1?->person_name)],
            ['Sjedište društva', $showBasic($p1?->address)],
            ['PIB društva', $showBasic($application->pib)],
            ['Šifra djelatnosti', $showBasic($p1?->activity_code)],
            ['Broj zaposlenih', $showBasic($p1?->employee_count)],
            ['Kontakt telefon odgovornog lica', $showBasic($p1?->phone)],
            ['E-mail odgovornog lica', $showBasic($p1?->email)],
        ];
    $subsidyCode = is_string($p1?->subsidy_type) ? $p1->subsidy_type : '';
    $subsidyLabel = \App\Support\CanonicalSubsidyType::isValid($subsidyCode)
        ? \App\Support\CanonicalSubsidyType::label($subsidyCode)
        : '';
    $p1Complete = app(\App\Services\Competitions\CanonicalApplicantFormService::class)->finalBlockReason($application) === null;
    $p2Ready = app(\App\Services\Competitions\CanonicalInvestmentPlanService::class)->finalBlockReason($application) === null;
@endphp

<div class="canonical-ui-page mmsp-form-page mmsp-business-plan">
    <div class="container mx-auto px-4">
        @include('partials.mmsp-test-preview-notice', ['competition' => $application->competition])
        <div class="mmsp-obrazac-zaglavlje">
            <div class="mmsp-obrazac-top">
                <div class="mmsp-obrazac-grb">
                    <img src="{{ asset('images/srednji_grb.png') }}" alt="Grb Opštine Kotor" onerror="this.onerror=null; this.src='{{ asset('images/srednji_grb.svg') }}';">
                </div>
                <div class="mmsp-obrazac-org">
                    <p><strong>Crna Gora</strong></p>
                    <p>Opština Kotor</p>
                    <p>Sekretarijat za razvoj preduzetništva</p>
                    <p>komunalne poslove i saobraćaj</p>
                </div>
                <div class="mmsp-obrazac-contact">
                    <p>Stari grad 317</p>
                    <p>85330 Kotor, Crna Gora</p>
                    <p>tel. +382(0)32 325 865</p>
                    <p>privreda@kotor.me</p>
                    <p>www.kotor.me</p>
                </div>
            </div>
            <div class="mmsp-obrazac-meta">
                <div class="mmsp-obrazac-number">Broj prijave: {{ $brojPrijave }}</div>
                <div class="mmsp-obrazac-code">Obrazac P2</div>
            </div>
            <h1 class="mmsp-obrazac-title">FORMA ZA PLAN ULAGANJA</h1>
        </div>

        <p class="mmsp-p2-screen-only" style="margin:0 0 18px;">
            <a href="{{ route('applications.show', $application) }}" style="color:#0B3D91; font-weight:600; text-decoration:none;">
                ← Nazad na pregled prijave
            </a>
        </p>

        @unless($readOnly)
            <div class="mmsp-alert mmsp-alert-info">
                @if($p1Complete)
                    Obrazac {{ $application->canonicalApplicantForm?->formCode() ?? 'P1a/P1b' }} je kompletno popunjen. Sada popunite Plan ulaganja (Obrazac P2).
                @else
                    Nastavite sa popunjavanjem Plana ulaganja (Obrazac P2). Obrazac {{ $application->canonicalApplicantForm?->formCode() ?? 'P1a/P1b' }} još nije kompletan.
                @endif
            </div>
        @endunless

        @if(session('success'))
            <div class="mmsp-alert mmsp-alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="mmsp-alert mmsp-alert-error">
                <strong>Plan ulaganja nije sačuvan. Ispravite sljedeće greške:</strong>
                <ul style="margin:8px 0 0; padding-left:20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if($readOnly)
            <div class="mmsp-alert mmsp-alert-info">
                {{ $plan?->isFrozen() ? \App\Models\CanonicalInvestmentPlan::FROZEN_MESSAGE : 'Prijava je zaključana i plan ulaganja više nije moguće mijenjati.' }}
            </div>
        @endif

        <form method="POST" action="{{ route('applications.canonical-p2.update', $application) }}">
            @csrf
            @method('PUT')
            <fieldset @disabled($readOnly) style="border:0; padding:0; margin:0;">

                <div class="mmsp-form-card">
                    <div class="mmsp-form-section">
                        <h2><span class="mmsp-section-number">I</span> OSNOVNI PODACI</h2>
                        @foreach(array_chunk($basicFields, 2) as $row)
                            @if(count($row) === 1)
                                <div class="mmsp-form-group">
                                    <label class="mmsp-form-label">{{ $row[0][0] }}</label>
                                    <input class="mmsp-form-control mmsp-p2-locked" value="{{ $row[0][1] }}" readonly>
                                </div>
                            @else
                                <div class="mmsp-form-row">
                                    @foreach($row as $field)
                                        <div>
                                            <label class="mmsp-form-label">{{ $field[0] }}</label>
                                            <input class="mmsp-form-control mmsp-p2-locked" value="{{ $field[1] }}" readonly>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                        <p class="mmsp-form-text">Podaci su automatski preuzeti iz Obrasca {{ $p1Code }}.</p>
                        <div class="mmsp-form-group">
                            <label class="mmsp-form-label" for="p2-subsidy-display">Vrsta subvencije za koju se aplicira</label>
                            <input id="subsidy_type" type="hidden" name="subsidy_type" value="{{ $subsidyCode }}">
                            <input id="p2-subsidy-display" class="mmsp-form-control mmsp-p2-locked @error('subsidy_type') error @enderror" value="{{ $subsidyLabel }}" readonly>
                            @error('subsidy_type')<div style="color:#ef4444; font-size:12px; margin-top:4px;">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                @php $narratives = old('narrative_sections', $plan?->narrative_sections ?? []); @endphp

                <div class="mmsp-form-card">
                    <div class="mmsp-form-section">
                        <h2><span class="mmsp-section-number">II</span> PLAN ULAGANJA</h2>
                        @foreach(['investment_description' => 'Opis planiranog ulaganja', 'investment_goals' => 'Ciljevi ulaganja', 'planned_activities' => 'Planirane aktivnosti'] as $key => $label)
                            <div class="mmsp-form-group">
                                <label class="mmsp-form-label" for="{{ $key }}">{{ $label }} <span class="mmsp-required">*</span></label>
                                <textarea class="mmsp-form-control @error('narrative_sections.'.$key) error @enderror" rows="3" id="{{ $key }}" name="narrative_sections[{{ $key }}]" maxlength="5000">{{ $narratives[$key] ?? '' }}</textarea>
                                @error('narrative_sections.'.$key)<div class="error-message">{{ $message }}</div>@enderror
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mmsp-form-card">
                    <div class="mmsp-form-section">
                        <h2><span class="mmsp-section-number">III</span> FINANSIJSKI OKVIR ULAGANJA</h2>
                        <p class="mmsp-form-text">
                            Unesite planirane troškove i sopstveno učešće. Ukupna vrijednost ulaganja i očekivani iznos subvencije računaju se automatski. Iznosi se unose u EUR, sa najviše dvije decimale.
                        </p>

                        <div style="overflow-x:auto;">
                            <table class="mmsp-dynamic-table" data-mmsp-expense-table>
                                <thead>
                                    <tr>
                                        <th>Vrsta troška</th>
                                        <th>Opis</th>
                                        <th style="width:180px;">Iznos (EUR)</th>
                                        @unless($readOnly)
                                            <th class="mmsp-p2-expense-remove"></th>
                                        @endunless
                                    </tr>
                                </thead>
                                <tbody id="mmsp-p2-expense-body">
                                    @foreach($lines as $i => $line)
                                        <tr>
                                            <td><input class="mmsp-form-control" type="text" name="expense_lines[{{ $i }}][cost_type]" value="{{ $line['cost_type'] ?? '' }}"></td>
                                            <td><input class="mmsp-form-control" type="text" name="expense_lines[{{ $i }}][description]" value="{{ $line['description'] ?? '' }}"></td>
                                            <td><input class="mmsp-form-control" type="text" inputmode="decimal" data-mmsp-expense-amount name="expense_lines[{{ $i }}][amount]" value="{{ $line['amount'] ?? '' }}"></td>
                                            @unless($readOnly)
                                                <td class="mmsp-p2-expense-remove"><button type="button" class="mmsp-p2-remove-row" data-mmsp-remove-expense-row>Ukloni</button></td>
                                            @endunless
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @unless($readOnly)
                            <div class="no-print mmsp-p2-screen-only" style="margin-top: 12px;">
                                <button type="button"
                                        class="mmsp-p2-add-row"
                                        data-mmsp-add-expense-row
                                        data-mmsp-max-expense-lines="{{ \App\Services\Competitions\CanonicalInvestmentPlanService::MAX_EXPENSE_LINES }}">+ Dodaj red</button>
                            </div>
                        @endunless

                        <div class="mmsp-form-row-3">
                            <div class="mmsp-form-group">
                                <label class="mmsp-form-label" for="total_investment">Ukupna vrijednost ulaganja (EUR) <span class="mmsp-required">*</span></label>
                                <input id="total_investment" class="mmsp-form-control @error('total_investment') error @enderror" type="text" inputmode="decimal" name="total_investment" value="{{ old('total_investment', $plan?->total_investment) }}" readonly>
                                @error('total_investment')<div style="color:#ef4444; font-size:12px; margin-top:4px;">{{ $message }}</div>@enderror
                            </div>
                            <div class="mmsp-form-group">
                                <label class="mmsp-form-label" for="own_contribution">Sopstveno učešće (EUR) <span class="mmsp-required">*</span></label>
                                <input id="own_contribution" class="mmsp-form-control @error('own_contribution') error @enderror" type="text" inputmode="decimal" name="own_contribution" value="{{ old('own_contribution', $plan?->own_contribution) }}">
                                @error('own_contribution')<div style="color:#ef4444; font-size:12px; margin-top:4px;">{{ $message }}</div>@enderror
                            </div>
                            <div class="mmsp-form-group">
                                <label class="mmsp-form-label" for="expected_subsidy">Očekivani iznos subvencije (EUR) <span class="mmsp-required">*</span></label>
                                <input id="expected_subsidy" class="mmsp-form-control @error('expected_subsidy') error @enderror" type="text" inputmode="decimal" name="expected_subsidy" value="{{ old('expected_subsidy', $plan?->expected_subsidy) }}" readonly>
                                @error('expected_subsidy')<div style="color:#ef4444; font-size:12px; margin-top:4px;">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                @foreach([
                    'IV' => ['AUTENTIČNOST / INOVATIVNOST / DIGITALIZACIJA', 'innovation_digitalization', 'Opišite doprinos odabranoj vrsti subvencije'],
                    'V' => ['OČEKIVANI REZULTATI', 'expected_results', 'Očekivani konkretni rezultati i pokazatelji'],
                    'VI' => ['UTICAJ NA LOKALNU EKONOMIJU', 'local_economy_impact', 'Zapošljavanje, lokalni dobavljači, lokalno tržište'],
                    'VII' => ['ODRŽIVOST I DUGOROČNI EFEKTI', 'sustainability', 'Način održavanja rezultata nakon podrške'],
                ] as $roman => [$heading, $key, $hint])
                    <div class="mmsp-form-card">
                        <div class="mmsp-form-section">
                            <h2><span class="mmsp-section-number">{{ $roman }}</span> {{ $heading }}</h2>
                            <div class="mmsp-form-group">
                                <label class="mmsp-form-label" for="{{ $key }}">{{ $hint }} <span class="mmsp-required">*</span></label>
                                <textarea class="mmsp-form-control @error('narrative_sections.'.$key) error @enderror" rows="4" maxlength="5000" name="narrative_sections[{{ $key }}]" id="{{ $key }}">{{ $narratives[$key] ?? '' }}</textarea>
                                @error('narrative_sections.'.$key)<div class="error-message">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                @endforeach

                @unless($readOnly)
                    <div class="mmsp-form-card mmsp-p2-actions" style="text-align:center;">
                        <div style="display:flex; justify-content:center; flex-wrap:wrap;">
                            <button type="submit"
                                    id="p2-save-button"
                                    class="{{ $p2Ready ? 'mmsp-p2-save-ready' : 'mmsp-p2-save-draft' }}"
                                    data-label-draft="Sačuvaj nacrt plana"
                                    data-label-plan="Sačuvaj plan ulaganja"
                                    data-subsidy-codes='@json(array_values(\App\Support\CanonicalSubsidyType::codes()))'
                                    data-narrative-fields='@json(array_keys(\App\Services\Competitions\CanonicalInvestmentPlanService::NARRATIVE_FIELDS))'>
                                {{ $p2Ready ? 'Sačuvaj plan ulaganja' : 'Sačuvaj nacrt plana' }}
                            </button>
                        </div>
                    </div>
                @endunless
            </fieldset>
        </form>

        <p class="mmsp-p2-screen-only" style="margin:0 0 18px;">
            <a href="{{ route('applications.show', $application) }}" style="color:#0B3D91; font-weight:600; text-decoration:none;">
                ← Nazad na pregled prijave
            </a>
        </p>

        @unless($readOnly)
            <script src="{{ asset('js/mmsp-p2-financials.js') }}"></script>
            <script src="{{ asset('js/mmsp-p2-save-label.js') }}"></script>
        @endunless

        <div class="mmsp-form-card mmsp-p2-actions" style="text-align:center; margin-top:8px; margin-bottom:24px;">
            <button type="button"
                    onclick="window.print();"
                    style="background:#0B3D91; color:#fff; padding:10px 24px; min-width:180px; border:0; border-radius:8px; font-weight:600; font-size:14px; cursor:pointer;">
                Štampaj
            </button>
        </div>
    </div>
</div>
@endsection
