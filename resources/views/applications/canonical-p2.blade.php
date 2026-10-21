@extends('layouts.app')

@section('content')
@include('partials.canonical-ui')
@php
    $lines = old('expense_lines', $plan?->expenseLines() ?? []);
    $lines = is_array($lines) ? array_values($lines) : [];
    $rowCount = $readOnly ? max(count($lines), 1) : max(count($lines) + 3, 5);
    $fieldStyle = 'width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px;';
@endphp
<div class="container mx-auto px-4 py-6 canonical-ui-page" style="max-width: 1000px;">
    <h1 style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">Plan ulaganja — Obrazac P2</h1>
    <p style="color: #4b5563; margin-bottom: 16px;">
        {{ $application->competition->title }} — {{ $application->business_plan_name ?? 'prijava #'.$application->id }}
    </p>

    @if(session('success'))
        <div style="background: #ecfdf5; border: 1px solid #10b981; padding: 12px; border-radius: 8px; margin-bottom: 16px;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div style="background: #fef2f2; border: 1px solid #ef4444; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif
    @if($readOnly)
        <div style="background: #eff6ff; border: 1px solid #3b82f6; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
            {{ $plan?->isFrozen() ? \App\Models\CanonicalInvestmentPlan::FROZEN_MESSAGE : 'Prijava je zaključana i plan ulaganja više nije moguće mijenjati.' }}
        </div>
    @endif

    <form method="POST" action="{{ route('applications.canonical-p2.update', $application) }}">
        @csrf
        @method('PUT')

        <fieldset @disabled($readOnly) style="border: 0; padding: 0; margin: 0;">
            <h2 style="font-size: 18px; font-weight: 600; margin: 16px 0 8px;">I. Osnovni podaci — vrsta subvencije za koju se aplicira</h2>
            <p style="color: #6b7280; font-size: 13px; margin-bottom: 8px;">
                Jedna prijava odnosi se na jednu vrstu subvencije. Na isti javni poziv možete podnijeti najviše dvije prijave, za različite vrste subvencija; podrška se može dobiti samo za jednu.
            </p>
            <select name="subsidy_type" style="{{ $fieldStyle }}">
                <option value="">— izaberite vrstu subvencije —</option>
                @foreach($subsidyTypes as $code => $label)
                    <option value="{{ $code }}" @selected(old('subsidy_type', $plan?->subsidy_type) === $code)>{{ $label }}</option>
                @endforeach
            </select>

            <h2 style="font-size: 18px; font-weight: 600; margin: 24px 0 8px;">III. Finansijski okvir ulaganja</h2>
            <p style="color: #6b7280; font-size: 13px; margin-bottom: 8px;">
                Zbir iznosa stavki troškova mora biti jednak ukupnoj vrijednosti ulaganja. Iznosi u EUR, najviše dvije decimale.
            </p>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
                <thead>
                    <tr>
                        <th style="text-align: left; padding: 6px;">Vrsta troška</th>
                        <th style="text-align: left; padding: 6px;">Opis</th>
                        <th style="text-align: left; padding: 6px; width: 160px;">Iznos (EUR)</th>
                    </tr>
                </thead>
                <tbody>
                    @for($i = 0; $i < $rowCount; $i++)
                        <tr>
                            <td style="padding: 4px;"><input type="text" name="expense_lines[{{ $i }}][cost_type]" value="{{ $lines[$i]['cost_type'] ?? '' }}" style="{{ $fieldStyle }}"></td>
                            <td style="padding: 4px;"><input type="text" name="expense_lines[{{ $i }}][description]" value="{{ $lines[$i]['description'] ?? '' }}" style="{{ $fieldStyle }}"></td>
                            <td style="padding: 4px;"><input type="text" inputmode="decimal" name="expense_lines[{{ $i }}][amount]" value="{{ $lines[$i]['amount'] ?? '' }}" style="{{ $fieldStyle }}"></td>
                        </tr>
                    @endfor
                </tbody>
            </table>

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                <label>Ukupna vrijednost ulaganja (EUR)
                    <input type="text" inputmode="decimal" name="total_investment" value="{{ old('total_investment', $plan?->total_investment) }}" style="{{ $fieldStyle }}">
                </label>
                <label>Sopstveno učešće (EUR)
                    <input type="text" inputmode="decimal" name="own_contribution" value="{{ old('own_contribution', $plan?->own_contribution) }}" style="{{ $fieldStyle }}">
                </label>
                <label>Očekivani iznos subvencije (EUR)
                    <input type="text" inputmode="decimal" name="expected_subsidy" value="{{ old('expected_subsidy', $plan?->expected_subsidy) }}" style="{{ $fieldStyle }}">
                </label>
            </div>

            @unless($readOnly)
                <button type="submit" class="btn btn-primary" style="margin-top: 20px;">Sačuvaj plan ulaganja</button>
            @endunless
        </fieldset>
    </form>

    <p style="margin-top: 20px;"><a href="{{ route('applications.show', $application) }}">← Nazad na prijavu</a></p>
</div>
@endsection
