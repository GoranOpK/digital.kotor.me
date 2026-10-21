@extends('layouts.app')

@section('content')
@include('partials.canonical-ui')
@php
    $competition = $application->competition;
    $lines = old('expense_lines', $plan?->expenseLines() ?? []);
    $lines = is_array($lines) ? array_values($lines) : [];
    $rowCount = $readOnly ? max(count($lines), 1) : max(count($lines) + 3, 5);
    $upBroj = $competition?->upNumber?->number ?? '—';
    $redniBroj = $application->redni_broj ?? '—';
    $brojPrijave = $upBroj.'/'.$redniBroj;
@endphp

<div class="canonical-ui-page mmsp-form-page mmsp-business-plan">
    <div class="container mx-auto px-4" style="max-width: 1200px;">
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
            <h1 class="mmsp-obrazac-title">PLAN ULAGANJA</h1>
            <p class="mmsp-obrazac-subtitle">
                na javni konkurs za raspodjelu bespovratnih sredstava<br>
                namijenjenih za podršku mikro, malim i srednjim preduzećima<br>
                @if($competition?->title)<strong>{{ $competition->title }}</strong>@endif
            </p>
        </div>

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
                                    <option value="{{ $code }}" @selected(old('subsidy_type', $plan?->subsidy_type) === $code)>{{ $label }}</option>
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

                <div class="mmsp-actions">
                    @unless($readOnly)
                        <button type="submit" class="btn-primary">Sačuvaj plan ulaganja</button>
                    @endunless
                    <a class="mmsp-btn-secondary" href="{{ route('applications.show', $application) }}">← Nazad na prijavu</a>
                </div>
            </fieldset>
        </form>
    </div>
</div>
@endsection
