@extends('layouts.app')

@section('content')
@php
    $debugPrint = request()->has('debug');
@endphp
<style>
    :root {
        --primary: #0B3D91;
        --primary-dark: #0A347B;
    }
    .admin-page {
        background: #f9fafb;
        min-height: 100vh;
        padding: 24px 0;
    }
    .page-header {
        background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        color: #fff;
        padding: 24px;
        border-radius: 16px;
        margin-bottom: 24px;
    }
    .page-header h1 {
        color: #fff;
        font-size: 28px;
        font-weight: 700;
        margin: 0;
    }
    .decision-document {
        background: #fff;
        padding: 40px 50px;
        max-width: 210mm;
        margin: 0 auto;
        font-family: Arial, sans-serif;
        font-size: 12pt;
        line-height: 1.5;
        color: #111;
    }
    .decision-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 1px solid #e5e7eb;
    }
    .decision-header-left {
        display: flex;
        align-items: flex-start;
        gap: 16px;
    }
    .decision-logo {
        width: auto;
        height: 6em; /* 4 reda teksta (4 × 1.5 line-height) */
        object-fit: contain;
    }
    .decision-org {
        font-weight: 600;
        line-height: 1.4;
    }
    .decision-org div:first-child { font-size: 13pt; }
    .decision-org div:nth-child(2) { font-size: 12pt; }
    .decision-org div:nth-child(3) { font-size: 11pt; color: #374151; max-width: 280px; }
    .decision-number-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        font-size: 12pt;
    }
    .decision-preamble {
        text-align: justify;
        margin-bottom: 24px;
        font-size: 12pt;
    }
    .decision-title-main {
        text-align: center;
        font-size: 14pt;
        font-weight: 700;
        margin: 8px 0 4px;
    }
    .decision-title-sub {
        text-align: center;
        font-size: 13pt;
        font-weight: 700;
        margin-bottom: 24px;
    }
    .decision-article {
        margin-bottom: 20px;
    }
    .decision-article-title {
        font-weight: 700;
        font-size: 12pt;
        margin-bottom: 12px;
        text-align: center;
        page-break-after: avoid;
    }
    .decision-article-intro {
        text-align: justify;
        margin-bottom: 12px;
    }
    .decision-applicant-list {
        margin: 0 0 0 20px;
        padding: 0;
        list-style: none;
    }
    .decision-applicant-item {
        margin-bottom: 12px;
        page-break-inside: avoid;
    }
    .decision-applicant-head {
        font-weight: 600;
        margin-bottom: 6px;
    }
    .decision-applicant-head strong {
        font-weight: 700;
    }
    .decision-applicant-details {
        margin-left: 20px;
        margin-top: 6px;
    }
    .decision-applicant-details div {
        margin-bottom: 2px;
    }
    .decision-footer {
        page-break-before: avoid;
        page-break-inside: avoid;
        margin-top: 40px;
        padding-top: 0;
    }
    .decision-footer-row {
        margin-bottom: 24px;
    }
    .decision-footer-signature-row {
        text-align: right;
    }
    .decision-footer-distribution-row {
        text-align: left;
    }
    .decision-signature {
        text-align: right;
        display: inline-block;
    }
    .decision-signature-title {
        font-size: 12pt;
        margin-bottom: 4px;
    }
    .decision-signature-line {
        border-bottom: 1px solid #111;
        width: 200px;
        margin: 0 0 4px auto;
        height: 24px;
    }
    .decision-signature-name {
        font-size: 12pt;
    }
    .decision-distribution {
        margin-top: 0;
        font-size: 12pt;
    }
    .decision-distribution ul {
        margin: 8px 0 0 20px;
        padding: 0;
    }
    .decision-distribution li {
        margin-bottom: 4px;
    }
    .decision-obrazlozenje-last-block {
        page-break-inside: avoid;
    }
    .decision-obrazlozenje-last-block .decision-article-intro {
        margin-bottom: 8px;
    }
    .decision-obrazlozenje-last-block .decision-article-intro:last-child {
        margin-bottom: 0;
    }
    @media print {
        .no-print { display: none !important; }
        nav, .navigation, header.bg-white { display: none !important; }
        html, body {
            width: 210mm;
            min-height: 297mm;
            margin: 0 !important;
            padding: 0 !important;
        }
        .admin-page {
            background: #fff;
            padding: 0 !important;
            margin: 0 !important;
            width: 210mm;
            min-height: 297mm;
        }
        .decision-document {
            width: 210mm;
            min-height: 297mm;
            padding: 20mm;
            max-width: none;
            box-shadow: none;
            margin: 0;
        }
        .decision-obrazlozenje {
            margin-top: 20mm;
            padding-top: 0;
        }
        .decision-obrazlozenje-last-block {
            page-break-inside: avoid;
        }
        .decision-footer {
            margin-top: 40px;
        }
        .page-header { display: none; }
        .container {
            max-width: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        @page {
            size: A4;
            margin-top: 15mm;
            margin-bottom: 20mm;
            margin-left: 20mm;
            margin-right: 20mm;
        }
    }
    /* DEBUG – pri štampi prikazuje granice sekcija (dodaj ?debug u URL) */
    @media print {
        .debug-print .decision-document { outline: 2px dashed red; }
        .debug-print .decision-header { outline: 2px dashed blue; }
        .debug-print .decision-number-row { outline: 2px dashed green; }
        .debug-print .decision-preamble { outline: 2px dashed orange; }
        .debug-print .decision-title-main { outline: 2px dashed purple; }
        .debug-print .decision-title-sub { outline: 2px dashed purple; }
        .debug-print .decision-article { outline: 2px dashed #8B4513; }
        .debug-print .decision-footer { outline: 3px solid magenta; }
    }
</style>

<div class="admin-page {{ $debugPrint ? 'debug-print' : '' }}">
    <div class="container mx-auto px-4">
        <div class="page-header no-print">
            <h1>Odluka o dodjeli sredstava</h1>
        </div>

        <div class="decision-document">
            {{-- Zaglavlje --}}
            <div class="decision-header">
                <div class="decision-header-left">
                    @if(file_exists(public_path('img/grb-kotor.png')))
                        <img src="{{ asset('img/grb-kotor.png') }}" alt="Grb" class="decision-logo">
                    @elseif(file_exists(public_path('img/logo.png')))
                        <img src="{{ asset('img/logo.png') }}" alt="Logo" class="decision-logo">
                    @endif
                    <div class="decision-org">
                        <div>Crna Gora</div>
                        <div>Opština Kotor</div>
                        <div>Komisija za raspodjelu bespovratnih sredstava namijenjenih za podršku ženskom preduzetništvu</div>
                    </div>
                </div>
            </div>

            <div class="decision-number-row">
                <span>Broj:</span>
                <span>Kotor, {{ now()->format('d.m.Y') }}. godine</span>
            </div>

            {{-- Preambula --}}
            <div class="decision-preamble">
                Na osnovu članova 20, 21, 22 i 23 Odluke o kriterijumima, načinu i postupku raspodjele sredstava za podršku ženskom preduzetništvu ("Službeni list Crne Gore - opštinski propisi", br. 011/24), Komisija za raspodjelu sredstava za podršku ženskom preduzetništvu donosi:
            </div>

            {{-- Naslov odluke --}}
            <div class="decision-title-main">ODLUKU</div>
            <div class="decision-title-sub">
                o raspodjeli sredstava za podršku ženskom preduzetništvu za {{ $competition->year ?? date('Y') }}. godinu
            </div>

            {{-- Član 1 --}}
            <div class="decision-article">
                <div class="decision-article-title">Član 1</div>
                <div class="decision-article-intro">
                    Na osnovu utvrđenih kriterijuma vrednovanja i ocjene biznis planova, odlučeno je da se <strong>finansiraju</strong> biznis planovi sledećih podnosioca prijava na ime podrške ženskom preduzetništvu i to:
                </div>

                @if($winners->count() > 0)
                    <ol class="decision-applicant-list" start="1">
                        @foreach($winners as $winner)
                            <li class="decision-applicant-item">
                                <div class="decision-applicant-head">
                                    Podnosilac prijave: <strong>{{ $winner->getApplicantDisplayForDecision() }}</strong>
                                </div>
                                <div class="decision-applicant-details">
                                    <div>Naziv biznis plana: "{{ $winner->business_plan_name }}";</div>
                                    <div>Ukupno ostvareno: <strong>{{ number_format($winner->getDisplayScore(), 1, ',', '.') }}</strong> bodova;</div>
                                    <div>Iznos odobrenih sredstava: {{ number_format($winner->approved_amount ?? 0, 2, ',', '.') }} €;</div>
                                    <div>Potraživani iznos sredstava: {{ number_format($winner->requested_amount ?? 0, 2, ',', '.') }} €;</div>
                                    <div>Ukupna vrijednost biznis plana: {{ number_format($winner->total_budget_needed ?? 0, 2, ',', '.') }} €.</div>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p style="text-align: center; color: #6b7280; font-style: italic;">Nema odabranih dobitnika sredstava.</p>
                @endif
            </div>

            {{-- Član 2 --}}
            <div class="decision-article">
                <div class="decision-article-title">Član 2</div>
                <p class="decision-article-intro" style="margin-bottom: 0;">
                    Međusobna prava i obaveze utvrdiće se posebnim aktom – Ugovorom koji Sekretar Sekretarijata za razvoj preduzetništva, komunalne poslove i saobraćaj zaključuje sa preduzetnicom odnosno nosiocem biznisa u društvu kojem su dodijeljena sredstva u roku od dvadeset dana od dana donošenja ove Odluke.
                </p>
            </div>

            {{-- Član 3 --}}
            <div class="decision-article">
                <div class="decision-article-title">Član 3</div>
                <p class="decision-article-intro" style="margin-bottom: 0;">
                    Dodjeljena sredstva utvrđena članom 1 ove Odluke uplaćuju se na žiro račun preduzetnice/društva u roku od deset dana od dana potpisivanja Ugovora.
                </p>
            </div>

            {{-- Član 4 --}}
            <div class="decision-article">
                <div class="decision-article-title">Član 4</div>
                <p class="decision-article-intro" style="margin-bottom: 0;">
                    Ova Odluka stupa na snagu danom donošenja.
                </p>
            </div>

            {{-- Član 5 --}}
            <div class="decision-article">
                <div class="decision-article-title">Član 5</div>
                <p class="decision-article-intro" style="margin-bottom: 0;">
                    Odluka se dostavlja svim učesnicama Javnog konkursa, objavljuje na vebsajtu Opštine Kotor i lokalnom javnom emiteru "Radio Kotor".
                </p>
            </div>

            {{-- Obrazloženje – druga stranica počinje ovdje --}}
            <div class="decision-article decision-obrazlozenje" style="margin-top: 32px;">
                <div class="decision-article-title">Obrazloženje</div>
                <p class="decision-article-intro">
                    Shodno Odluci o kriterijumima, načinu i postupku raspodjele sredstava za podršku ženskom preduzetništvu ("Službeni list Crne Gore - opštinski propisi", br. 011/24), Komisija je raspisala Javni konkurs za dodjelu bespovratnih sredstava za podršku ženskom preduzetništvu {{ $competition->year ?? date('Y') }}. godine. Konkurs je objavljen u trajanju od 20 dana, od {{ $pubStart ? $pubStart->format('d.m.Y') : '___' }}. do {{ $pubEnd ? $pubEnd->format('d.m.Y') : '___' }}. godine, na vebsajtu Opštine Kotor i lokalnom javnom emiteru "Radio Kotor".
                </p>
                <p class="decision-article-intro">
                    Nakon isteka roka za podnošenje prijava ({{ $deadlineDay ? $deadlineDay->format('d.m.Y') : '___' }}.), primljeno je {{ $totalApplications }} prijava u roku. Komisija je pristupila otvaranju zapečaćenih koverti i sprovela administrativnu provjeru podnesene dokumentacije radi utvrđivanja potpunosti i valjanosti u skladu sa uslovima konkursa.
                </p>
                <p class="decision-article-intro">
                    Uvidom u podnesene prijave konstatovano je da {{ $incompleteCount }} prijava nije potpuna u pogledu dostavljene dokumentacije te nisu uzeti u dalji postupak.
                </p>
                <p class="decision-article-intro">
                    Komisija je konstatovala da {{ $eligibleCount }} prijave mogu uzeti u dalji postupak razmatranja i vrednovanja prema kriterijumima utvrđenim Odlukom i Konkursom.
                </p>
                <p class="decision-article-intro">
                    Radi potpunijeg uvida u sadržaj i izvodljivost biznis planova te lične kompetencije njihovih predlagača, Komisija je organizovala usmene prezentacije biznis planova dana {{ $oralDate ? $oralDate->format('d.m.Y') : '___' }}. u Palati Bizanti. Svrha prezentacija bila je da podnosioci prijava pred Komisijom kroz direktnu komunikaciju predstave svoje poslovne ideje, potencijal, motivaciju i spremnost za realizaciju.
                </p>
                <div class="decision-obrazlozenje-last-block">
                    <p class="decision-article-intro">
                        Na osnovu pojedinačnih evaluacionih formulara i rezultirajuće rang liste sa prosječnom ocjenom biznis planova, Komisija je uspostavila Rang listu dana {{ $rankingDate ? $rankingDate->format('d.m.Y') : now()->format('d.m.Y') }}.
                    </p>
                    <p class="decision-article-intro">
                        Na osnovu utvrđene Rang liste, Komisija je donijela Odluku o raspodjeli sredstava za podršku ženskom preduzetništvu za {{ $competition->year ?? date('Y') }}. godinu, dodjeljujući sredstva <strong>{{ $winners->count() }}</strong> biznis planova u ukupnom iznosu od <strong>{{ number_format($winners->sum('approved_amount'), 2, ',', '.') }} eura</strong>.
                    </p>
                </div>
            </div>

            {{-- Potpis (iznad) i Dostaviti (ispod) – u istom redu vertikalno --}}
            <div class="decision-footer">
                <div class="decision-footer-row decision-footer-signature-row">
                    <div class="decision-signature">
                        <div class="decision-signature-title">Predsjednica Komisije</div>
                        <div class="decision-signature-line"></div>
                        <div class="decision-signature-name">{{ $chairmanName ?? '_________________________' }}</div>
                    </div>
                </div>
                <div class="decision-footer-row decision-footer-distribution-row">
                    <div class="decision-distribution">
                        <strong>Dostaviti:</strong>
                        <ul>
                            <li>- Podnosiocima prijave (x{{ $winners->count() }})</li>
                            <li>- Članovima Komisije (x{{ $commissionMembersCount ?: 5 }})</li>
                            <li>- Sekretarijatu 16 (x2)</li>
                            <li>- Arhivi</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div style="text-align: center; margin-top: 24px;" class="no-print">
            @if($debugPrint)
            <div style="background: #fef3c7; border: 2px solid #f59e0b; padding: 12px 20px; border-radius: 8px; margin-bottom: 16px; text-align: left; font-size: 12px;">
                <strong>DEBUG MODE</strong> – Pri štampi će se prikazati obojene granice sekcija:<br>
                <span style="color:red;">●</span> document | <span style="color:blue;">●</span> header | <span style="color:green;">●</span> Broj+datum | <span style="color:orange;">●</span> preambula | <span style="color:purple;">●</span> naslov | <span style="color:brown;">●</span> članovi | <span style="color:magenta;">●</span> footer<br>
                <em>Ukloni ?debug iz URL-a za normalan prikaz.</em>
            </div>
            @endif
            @if((isset($isSuperAdmin) && $isSuperAdmin) || (isset($isChairman) && $isChairman))
            <button onclick="window.print()" class="btn" style="background: var(--primary); color: #fff; padding: 12px 24px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600;">Štampaj Odluku</button>
            <a href="{{ $debugPrint ? request()->url() : request()->fullUrlWithQuery(['debug' => '1']) }}" class="btn" style="background: {{ $debugPrint ? '#10b981' : '#f59e0b' }}; color: #fff; padding: 12px 24px; border-radius: 8px; text-decoration: none; margin-left: 8px; display: inline-block;">
                {{ $debugPrint ? 'Isključi debug' : 'Debug štampa' }}
            </a>
            @endif
            <a href="{{ route('admin.competitions.ranking', $competition) }}" class="btn" style="background: #6b7280; color: #fff; padding: 12px 24px; border-radius: 8px; text-decoration: none; margin-left: 8px; display: inline-block;">Nazad na rang listu</a>
            @if(((isset($isSuperAdmin) && $isSuperAdmin) || (isset($isChairman) && $isChairman)) && !in_array($competition->status, ['closed', 'completed']) && $competition->hasChairmanCompletedDecisions())
                <form method="POST" action="{{ route('admin.competitions.close', $competition) }}" style="display: inline; margin-left: 8px;">
                    @csrf
                    <button type="submit" class="btn" style="background: #dc2626; color: #fff; padding: 12px 24px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600;">Zatvori konkurs</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
