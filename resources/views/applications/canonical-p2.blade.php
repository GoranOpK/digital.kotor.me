@extends('layouts.app')

@section('content')
@include('partials.canonical-ui')
<style>
/* Screen overrides scoped to canonical MMSP P2. Shared canonical-ui rules stay unchanged. */
.canonical-ui-page.mmsp-business-plan .mmsp-obrazac-top {
    margin-bottom: 0;
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
    .mmsp-business-plan .mmsp-p2-actions { display:none !important; }
    .canonical-ui-page.mmsp-form-page.mmsp-business-plan {
        padding:0 !important; margin:0 !important; min-height:0 !important; background:#fff !important;
    }
    .mmsp-business-plan > .container {
        padding:0 !important; margin:0 !important; width:100% !important; max-width:none !important;
    }
    .mmsp-business-plan .mmsp-obrazac-zaglavlje {
        background:#fff !important; box-shadow:none !important; border:none !important;
        border-radius:0 !important; padding:0 0 8px !important; margin:0 0 8px !important;
        break-inside:avoid;
    }
    .mmsp-business-plan .mmsp-obrazac-grb img { height:2cm !important; width:auto !important; }
    .mmsp-business-plan .mmsp-obrazac-org,
    .mmsp-business-plan .mmsp-obrazac-contact { font-size:11.7px !important; }
    .mmsp-business-plan .mmsp-obrazac-number,
    .mmsp-business-plan .mmsp-obrazac-code { font-size:12.6px !important; }
    .mmsp-business-plan .mmsp-obrazac-title { font-size:22px !important; }
    .mmsp-business-plan form, .mmsp-business-plan fieldset { margin:0 !important; padding:0 !important; width:100% !important; }
    .mmsp-business-plan .mmsp-form-card {
        background:#fff !important; box-shadow:none !important; padding:0 !important;
        margin:0 0 12px !important; border:0 !important; border-radius:0 !important;
    }
    .mmsp-business-plan .mmsp-form-section { margin:0 !important; break-inside:auto; }
    .mmsp-business-plan .mmsp-form-section h2 {
        font-size:12pt !important; margin:7px 0 10px !important; padding-bottom:5px !important;
        color:#000 !important; border-bottom:1px solid #000 !important;
    }
    .mmsp-business-plan .mmsp-section-number {
        font-size:11px !important; width:23px !important; height:23px !important; line-height:23px !important;
    }
    .mmsp-business-plan .mmsp-form-group { margin-bottom:9px !important; break-inside:avoid; }
    .mmsp-business-plan .mmsp-form-label {
        font-size:9.9pt !important; font-weight:600 !important; margin-bottom:2px !important;
    }
    .mmsp-business-plan .mmsp-form-control {
        font-size:9pt !important; line-height:1.4 !important; box-sizing:border-box !important;
        width:100% !important; max-width:100% !important; min-width:0 !important;
        padding:3px 5px !important; border:1px solid #d1d5db !important; border-radius:0 !important;
        box-shadow:none !important; background:#fff !important; color:#111 !important;
    }
    .mmsp-business-plan textarea.mmsp-form-control { min-height:55px !important; resize:none !important; }
    .mmsp-business-plan .mmsp-form-row { grid-template-columns:minmax(0,1fr) minmax(0,1fr) !important; gap:6px 16px !important; }
    .mmsp-business-plan .mmsp-form-row-3 { grid-template-columns:repeat(3,minmax(0,1fr)) !important; gap:6px 12px !important; }
    .mmsp-business-plan .mmsp-form-text { font-size:9pt !important; }
    .mmsp-business-plan .mmsp-dynamic-table { width:100% !important; border-collapse:collapse !important; table-layout:fixed !important; }
    .mmsp-business-plan .mmsp-dynamic-table th,
    .mmsp-business-plan .mmsp-dynamic-table td { border:1px solid #ddd !important; padding:5px !important; font-size:9pt !important; }
    .mmsp-business-plan .mmsp-dynamic-table tr { break-inside:avoid; }
    .mmsp-business-plan .mmsp-required { color:#111 !important; }
}
</style>
@php
    $competition = $application->competition;
    $lines = old('expense_lines', $plan?->expenseLines() ?? []);
    $lines = is_array($lines) ? array_values($lines) : [];
    $rowCount = $readOnly ? max(count($lines), 1) : max(count($lines) + 3, 5);
    $upBroj = $competition?->upNumber?->number ?? '—';
    $redniBroj = $application->redni_broj ?? '—';
    $brojPrijave = $upBroj.'/'.$redniBroj;
    $p1 = $application->canonicalApplicantForm;
    $p1Complete = app(\App\Services\Competitions\CanonicalApplicantFormService::class)->finalBlockReason($application) === null;
@endphp

<div class="canonical-ui-page mmsp-form-page mmsp-business-plan">
    <div class="container mx-auto px-4">
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
                        <div class="mmsp-form-group">
                            <label class="mmsp-form-label" for="subsidy_type">Vrsta subvencije za koju se aplicira <span class="mmsp-required">*</span></label>
                            <select id="subsidy_type" name="subsidy_type" class="mmsp-form-control @error('subsidy_type') error @enderror">
                                <option value="">— izaberite vrstu subvencije —</option>
                                @foreach($subsidyTypes as $code => $label)
                                    <option value="{{ $code }}" @selected(old('subsidy_type', $plan?->subsidy_type ?? $p1?->subsidy_type) === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="mmsp-form-text">
                                Jedna prijava odnosi se na jednu vrstu subvencije. Izbor mora biti isti kao u Obrascu {{ $application->canonicalApplicantForm?->formCode() ?? 'P1a/P1b' }}.
                            </div>
                            @error('subsidy_type')<div style="color:#ef4444; font-size:12px; margin-top:4px;">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="mmsp-form-card">
                    <div class="mmsp-form-section">
                        <div class="mmsp-form-group">
                            <label class="mmsp-form-label">Osnovni podaci o preduzetniku / društvu (iz Obrasca {{ $p1?->formCode() ?? 'P1' }})</label>
                            <div class="mmsp-form-row" style="gap:12px;">
                                <div>
                                    <label class="mmsp-form-label">Podnosilac / naziv društva</label>
                                    <input class="mmsp-form-control" value="{{ $p1?->company_name ?: $p1?->person_name }}" readonly>
                                </div>
                                <div>
                                    <label class="mmsp-form-label">Odgovorno lice / preduzetnik</label>
                                    <input class="mmsp-form-control" value="{{ $p1?->person_name }}" readonly>
                                </div>
                                <div>
                                    <label class="mmsp-form-label">Adresa / sjedište</label>
                                    <input class="mmsp-form-control" value="{{ $p1?->address }}" readonly>
                                </div>
                                <div>
                                    <label class="mmsp-form-label">PIB</label>
                                    <input class="mmsp-form-control" value="{{ $application->pib }}" readonly>
                                </div>
                                <div>
                                    <label class="mmsp-form-label">Šifra djelatnosti / broj zaposlenih</label>
                                    <input class="mmsp-form-control" value="{{ trim(($p1?->activity_code ?? '').' / '.($p1?->employee_count ?? ''), ' /') }}" readonly>
                                </div>
                                <div>
                                    <label class="mmsp-form-label">Kontakt i e-mail</label>
                                    <input class="mmsp-form-control" value="{{ trim(($p1?->phone ?? '').' / '.($p1?->email ?? ''), ' /') }}" readonly>
                                </div>
                            </div>
                            <p class="mmsp-form-text">Ovi podaci se uređuju u Obrascu {{ $p1?->formCode() ?? 'P1a/P1b' }}, ne ponavljaju se u P2.</p>
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
                        <p class="mmsp-form-text" style="margin-bottom:16px;">
                            Unesite planirane troškove. Zbir stavki mora biti jednak ukupnoj vrijednosti ulaganja. Iznosi se unose u EUR, sa najviše dvije decimale.
                        </p>

                        <div style="overflow-x:auto;">
                            <table class="mmsp-dynamic-table">
                                <thead>
                                    <tr>
                                        <th>Vrsta troška</th>
                                        <th>Opis</th>
                                        <th style="width:180px;">Iznos (EUR)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @for($i = 0; $i < $rowCount; $i++)
                                        <tr>
                                            <td><input class="mmsp-form-control" type="text" name="expense_lines[{{ $i }}][cost_type]" value="{{ $lines[$i]['cost_type'] ?? '' }}"></td>
                                            <td><input class="mmsp-form-control" type="text" name="expense_lines[{{ $i }}][description]" value="{{ $lines[$i]['description'] ?? '' }}"></td>
                                            <td><input class="mmsp-form-control" type="text" inputmode="decimal" name="expense_lines[{{ $i }}][amount]" value="{{ $lines[$i]['amount'] ?? '' }}"></td>
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>

                        <div class="mmsp-form-row-3">
                            <div class="mmsp-form-group">
                                <label class="mmsp-form-label" for="total_investment">Ukupna vrijednost ulaganja (EUR) <span class="mmsp-required">*</span></label>
                                <input id="total_investment" class="mmsp-form-control @error('total_investment') error @enderror" type="text" inputmode="decimal" name="total_investment" value="{{ old('total_investment', $plan?->total_investment) }}">
                                @error('total_investment')<div style="color:#ef4444; font-size:12px; margin-top:4px;">{{ $message }}</div>@enderror
                            </div>
                            <div class="mmsp-form-group">
                                <label class="mmsp-form-label" for="own_contribution">Sopstveno učešće (EUR) <span class="mmsp-required">*</span></label>
                                <input id="own_contribution" class="mmsp-form-control @error('own_contribution') error @enderror" type="text" inputmode="decimal" name="own_contribution" value="{{ old('own_contribution', $plan?->own_contribution) }}">
                                @error('own_contribution')<div style="color:#ef4444; font-size:12px; margin-top:4px;">{{ $message }}</div>@enderror
                            </div>
                            <div class="mmsp-form-group">
                                <label class="mmsp-form-label" for="expected_subsidy">Očekivani iznos subvencije (EUR) <span class="mmsp-required">*</span></label>
                                <input id="expected_subsidy" class="mmsp-form-control @error('expected_subsidy') error @enderror" type="text" inputmode="decimal" name="expected_subsidy" value="{{ old('expected_subsidy', $plan?->expected_subsidy) }}">
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
                                    style="background:#0B3D91; color:#fff; padding:12px 24px; border:0; border-radius:8px; font-weight:600; font-size:14px; cursor:pointer;">
                                Sačuvaj Nacrt plana
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
