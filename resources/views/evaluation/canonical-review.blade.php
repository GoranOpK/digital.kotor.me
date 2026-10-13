@extends('layouts.app')

@section('content')
@php
    $box = 'border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 16px; background: #fff;';
    $warn = 'background: #fffbeb; border: 1px solid #f59e0b; padding: 10px; border-radius: 8px; margin-bottom: 8px;';
    $bad = 'background: #fef2f2; border: 1px solid #ef4444; padding: 10px; border-radius: 8px; margin-bottom: 8px;';
    $ok = 'background: #ecfdf5; border: 1px solid #10b981; padding: 10px; border-radius: 8px; margin-bottom: 8px;';
    $fmt = fn ($at) => $at ? \Carbon\CarbonImmutable::parse($at)->timezone($timezone)->format('d.m.Y H:i:s') : '—';
    $labels = \App\Services\Competitions\CanonicalReviewState::LABELS;
    $A = \App\Models\ApplicationCompletenessAssessment::class;
    $C = \App\Models\ApplicationComplaint::class;
    $identity = $snapshot['application'] ?? [];
    $identityLabels = [
        'redni_broj' => 'Redni broj',
        'submitted_at' => 'Podnesena',
        'applicant_type' => 'Vrsta podnosioca',
        'business_stage' => 'Faza poslovanja',
        'business_area' => 'Oblast',
        'business_plan_name' => 'Naziv biznis plana',
        'founder_name' => 'Osnivač / podnosilac',
        'physical_person_name' => 'Fizičko lice',
        'preduzetnik_name' => 'Preduzetnik',
        'doo_name' => 'Društvo',
        'company_seat' => 'Sjedište',
        'pib' => 'PIB',
    ];
    $p2 = $snapshot['canonical_p2'] ?? null;
    $p1 = $snapshot['canonical_p1'] ?? null;
    $p1Catalog = \App\Support\CanonicalDocumentCatalog::forLegalForm($p1['legal_form'] ?? null);
    $p1Entrepreneur = ($p1['legal_form'] ?? null) === \App\Support\CanonicalLegalForm::PREDUZETNIK;
    $p1Labels = [
        'company_name' => 'Naziv društva',
        'person_name' => $p1Entrepreneur ? 'Ime i prezime preduzetnika' : 'Ime i prezime odgovornog lica',
        'address' => $p1Entrepreneur ? 'Adresa' : 'Sjedište',
        'activity_code' => 'Šifra djelatnosti',
        'employee_count' => 'Broj zaposlenih',
        'bank_account' => 'Broj žiro računa',
        'phone' => $p1Entrepreneur ? 'Kontakt telefon' : 'Kontakt telefon odgovornog lica',
        'email' => $p1Entrepreneur ? 'E-mail' : 'E-mail odgovornog lica',
        'website' => 'Website',
        'registration_date' => $p1Entrepreneur ? 'Datum registracije u CRPS' : 'Datum osnivanja',
    ];
    $canRecord = ! $readOnly && $recordBlock === null;
    $canDecide = ! $readOnly && $complaint && $decideBlock === null;
@endphp
<div class="container mx-auto px-4 py-6" style="max-width: 1100px;">
    <h1 style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">Provjera potpunosti — prijava {{ $identity['redni_broj'] ?? ('#'.$application->id) }}</h1>
    <p style="color: #4b5563; margin-bottom: 16px;">
        {{ $competition->title }}. Stanje: <strong data-review-state="{{ $state }}">{{ $labels[$state] ?? '—' }}</strong>
    </p>

    @if($readOnly)
        <div style="{{ $warn }}">Pregled za administratora: samo čitanje. Potpunost evidentira i o prigovoru odlučuje isključivo predsjednik kanonske Komisije.</div>
    @endif
    @if($closed)
        <div style="{{ $warn }}">Poziv je zatvoren. Evidencija je dostupna samo za čitanje.</div>
    @elseif($stageLocked)
        <div style="{{ $warn }}">{{ \App\Services\Competitions\CompletenessAssessmentService::STAGE_LOCKED_MESSAGE }}</div>
    @endif

    @if(session('success'))
        <div style="{{ $ok }}">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
        <div style="{{ $warn }}">{{ session('warning') }}</div>
    @endif
    @if($errors->any())
        <div style="{{ $bad }}">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div style="{{ $box }}" data-section="canonical-review-basis">
        <h2 style="font-size: 18px; font-weight: 600;">Zamrznuti dosije (osnov provjere)</h2>
        <p style="color: #4b5563;">Provjera se vrši isključivo nad dosijeom zamrznutim pri konačnom podnošenju. Podnosilac ga ne može mijenjati.</p>
        <table style="width: 100%; border-collapse: collapse; margin: 8px 0;">
            <tbody>
                @foreach($identityLabels as $key => $label)
                    @if(array_key_exists($key, $identity) && $identity[$key] !== null && $identity[$key] !== '')
                        <tr>
                            <td style="width: 35%;">{{ $label }}</td>
                            <td>{{ $key === 'submitted_at' ? $fmt($identity[$key]) : $identity[$key] }}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>

        <h3 style="font-size: 16px; font-weight: 600;">Obrazac {{ $p1['form'] ?? 'P1a/P1b' }} — podaci o podnosiocu</h3>
        @if($p1)
            <ul data-section="canonical-review-p1">
                <li>Pravna forma: {{ \App\Support\CanonicalLegalForm::label($p1['legal_form']) }}</li>
                <li>Vrsta subvencije: {{ $p1['subsidy_type'] ?? '—' }}</li>
                @foreach($p1Labels as $key => $label)
                    @if(isset($p1[$key]) && $p1[$key] !== '')
                        <li>{{ $label }}: {{ $p1[$key] }}</li>
                    @endif
                @endforeach
                <li>Izjava o tačnosti podataka potvrđena: {{ $fmt($p1['accuracy_confirmed_at'] ?? null) }}</li>
                <li>Zamrznut: {{ $fmt($p1['frozen_at'] ?? null) }}</li>
            </ul>
        @else
            <p>Zamrznuti Obrazac P1a/P1b nije evidentiran.</p>
        @endif

        <h3 style="font-size: 16px; font-weight: 600;">Obrazac P2 — finansijski okvir</h3>
        @if($p2)
            <ul data-section="canonical-review-p2">
                <li>Vrsta subvencije: {{ $p2['subsidy_type'] ?? '—' }}</li>
                <li>Ukupna investicija: {{ $p2['total_investment'] ?? '—' }}</li>
                <li>Sopstveno učešće: {{ $p2['own_contribution'] ?? '—' }}</li>
                <li>Očekivana subvencija: {{ $p2['expected_subsidy'] ?? '—' }}</li>
                <li>Zamrznut: {{ $fmt($p2['frozen_at'] ?? null) }}</li>
            </ul>
            @if(! empty($p2['expense_lines']))
                <table style="width: 100%; border-collapse: collapse; margin: 8px 0;">
                    <tbody>
                        @foreach($p2['expense_lines'] as $line)
                            <tr>
                                @foreach((array) $line as $value)
                                    <td>{{ is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @else
            <p>Zamrznuti Obrazac P2 nije evidentiran.</p>
        @endif

        <h3 style="font-size: 16px; font-weight: 600;">Dokumenti</h3>
        @if(empty($snapshot['documents']))
            <p>U zamrznutom dosijeu nema priloženih dokumenata.</p>
        @else
            <ul data-section="canonical-review-documents">
                @foreach($snapshot['documents'] as $document)
                    <li>
                        @if(! $readOnly)
                            <a href="{{ route('applications.document.view', [$application, $document['id']]) }}" target="_blank" rel="noopener">{{ $document['name'] }}</a>
                        @else
                            {{ $document['name'] }}
                        @endif
                        ({{ $p1Catalog->allowsUploadType($document['document_type'] ?? null) ? $p1Catalog->label($document['document_type']) : ($document['document_type'] ?? '—') }}{{ $document['is_required'] ? ', obavezan' : '' }})
                        @if(! empty($document['variant']))
                            — {{ $p1Catalog->variantsFor($document['document_type'])[$document['variant']] ?? $document['variant'] }}
                        @endif
                        @if(! empty($document['issued_on']))
                            — izdat {{ \Carbon\CarbonImmutable::parse($document['issued_on'])->format('d.m.Y.') }}
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif

        <p style="font-size: 12px; color: #6b7280;">Otisak dosijea (SHA-256): <code data-fingerprint>{{ $fingerprint }}</code></p>
        @if(! $basisMatches)
            <div style="{{ $bad }}">Trenutni dosije se ne poklapa sa otiskom evidentiranim pri provjeri. Prijavite nepravilnost administratoru.</div>
        @endif
    </div>

    <div style="{{ $box }}" data-section="canonical-review-history">
        <h2 style="font-size: 18px; font-weight: 600;">Istorija provjere potpunosti</h2>
        @if($history->isEmpty())
            <p>Potpunost još nije evidentirana.</p>
        @else
            <table style="width: 100%; border-collapse: collapse; margin: 8px 0;">
                <thead><tr><th align="left">Evidentirano</th><th align="left">Nalaz</th><th align="left">Obrazloženje</th><th align="left">Evidentirao</th><th align="left">Obavještenje poslato</th></tr></thead>
                <tbody>
                    @foreach($history as $row)
                        <tr data-assessment-id="{{ $row->id }}">
                            <td>{{ $fmt($row->recorded_at) }}</td>
                            <td>{{ $row->outcome === $A::COMPLETE ? 'Potpuna' : 'Nepotpuna' }}</td>
                            <td>{{ $row->notes ?? '—' }}</td>
                            <td>{{ $row->recordedBy?->name ?? '—' }}</td>
                            <td>{{ $fmt($row->notice_sent_at) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p style="font-size: 12px; color: #6b7280;">Zapisi su nepromjenjivi. Prihvaćen prigovor dodaje novi zapis „Potpuna”; izvorni nalaz ostaje sačuvan.</p>
        @endif
        @if($complaintDeadline)
            <p>Rok za prigovor (3 dana od slanja obavještenja, tačno vrijeme): {{ $fmt($complaintDeadline) }}</p>
        @endif

        @if($canRecord)
            <form method="POST" action="{{ route('evaluation.completeness', $application) }}" data-form="completeness">
                @csrf
                <fieldset>
                    <legend>Nalaz predsjednika Komisije</legend>
                    <label><input type="radio" name="outcome" value="{{ $A::COMPLETE }}" required> Dokumentacija je potpuna</label><br>
                    <label><input type="radio" name="outcome" value="{{ $A::INCOMPLETE }}"> Dokumentacija je nepotpuna</label>
                </fieldset>
                <label>Obrazloženje (obavezno za nepotpunu dokumentaciju; dostavlja se podnosiocu)
                    <textarea name="notes" rows="3" maxlength="5000" style="width: 100%;">{{ old('notes') }}</textarea>
                </label>
                <button type="submit" class="btn btn-primary">Evidentiraj nalaz</button>
            </form>
        @elseif(! $readOnly && $history->isEmpty() && $recordBlock)
            <div style="{{ $warn }}">{{ $recordBlock }}</div>
        @endif
    </div>

    <div style="{{ $box }}" data-section="canonical-review-complaint">
        <h2 style="font-size: 18px; font-weight: 600;">Prigovor</h2>
        @if(! $complaint)
            <p>Prigovor nije podnesen.</p>
        @else
            <p>Podnesen: {{ $fmt($complaint->submitted_at) }} · rok za odluku (7 dana, tačno vrijeme): {{ $fmt($decisionDueAt) }}</p>
            @unless($readOnly)
                <div style="border-left: 3px solid #e5e7eb; padding-left: 8px; margin: 8px 0; white-space: pre-line;" data-complaint-body>{{ $complaint->body }}</div>
            @endunless
            @if($complaint->decision)
                <div style="{{ $ok }}" data-complaint-decision="{{ $complaint->decision }}">
                    Odluka: prigovor je {{ $complaint->decision === $C::ACCEPTED ? 'prihvaćen' : 'odbijen' }} · {{ $fmt($complaint->decided_at) }}
                    @if($complaint->decidedBy) · {{ $complaint->decidedBy->name }}@endif
                    @if($complaint->decision_notes)
                        <div>Obrazloženje: {{ $complaint->decision_notes }}</div>
                    @endif
                </div>
                @if($complaint->decision_late)
                    <div style="{{ $warn }}" data-late-warning>{{ $complaint->compliance_warning }}</div>
                @endif
            @else
                @if($decisionOverdueNow)
                    <div style="{{ $warn }}" data-overdue-warning>Rok od 7 dana za odluku je istekao. Odluka se i dalje evidentira, uz trajno upozorenje o kašnjenju.</div>
                @endif
                @if($canDecide)
                    <form method="POST" action="{{ route('evaluation.complaint-decision', [$application, $complaint]) }}" data-form="complaint-decision">
                        @csrf
                        <label><input type="radio" name="decision" value="{{ $C::ACCEPTED }}" required> Prigovor se prihvata — prijava se smatra potpunom</label><br>
                        <label><input type="radio" name="decision" value="{{ $C::REJECTED }}"> Prigovor se odbija — nalaz o nepotpunosti ostaje</label>
                        <label>Obrazloženje odluke
                            <textarea name="decision_notes" rows="3" maxlength="5000" style="width: 100%;"></textarea>
                        </label>
                        <button type="submit" class="btn btn-primary">Evidentiraj odluku</button>
                    </form>
                @elseif(! $readOnly && $decideBlock)
                    <div style="{{ $warn }}">{{ $decideBlock }}</div>
                @endif
            @endif
        @endif
    </div>

    <p>
        <a href="{{ $readOnly ? route('admin.competitions.canonical-review.index', $competition) : route('evaluation.canonical-review.index', $competition) }}">Nazad na listu prijava</a>
    </p>
</div>
@endsection
