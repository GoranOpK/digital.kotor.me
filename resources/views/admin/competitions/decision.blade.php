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
    .decision-org div:first-child { font-size: 12pt; }
    .decision-org div:nth-child(2) { font-size: 12pt; }
    .decision-org div:nth-child(3) { font-size: 12pt; color: #374151; max-width: 280px; }
    .decision-number-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        font-size: 12pt;
    }
    /* Broj odluke se ne popunjava iz sistema — samo natpis za ručni upis pri štampi */
    .decision-number-label {
        flex-shrink: 0;
    }
    .decision-number-date {
        text-align: right;
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
        font-size: 12pt;
        font-weight: 700;
        margin-bottom: 24px;
    }
    .decision-article {
        margin-bottom: 20px;
    }
    .decision-obrazlozenje {
        margin-top: 32px;
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
        text-align: center;
        display: inline-block;
    }
    .decision-signature-title {
        font-size: 12pt;
        margin-bottom: 4px;
    }
    .decision-signature-line {
        border-bottom: 1px solid #111;
        width: 200px;
        margin: 0 auto 4px;
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
</style>

<div class="admin-page">
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

            @php
                $fmtDate = fn ($date) => $date ? \Carbon\Carbon::parse($date)->format('d.m.Y') : '___';
                $decisionPlaceDate = isset($decisionDate) ? \Carbon\Carbon::parse($decisionDate) : (isset($rankingDate) && $rankingDate ? \Carbon\Carbon::parse($rankingDate) : now());
                $year = $competitionYear ?? ($competition->year ?? date('Y'));
                $approvedWinnersCount = $winnersCount ?? $winners->count();
                $approvedTotalEur = $totalApprovedAmount ?? (float) $winners->sum('approved_amount');
            @endphp
            <div class="decision-number-row">
                <span class="decision-number-label">Broj:</span>
                <span class="decision-number-date">Kotor, {{ $decisionPlaceDate->format('d.m.') }} {{ $year }}. godine</span>
            </div>

            <div class="decision-preamble">
                Na osnovu članova 22, 23 i 24 Odluke o podršci ženskom preduzetništvu ("Službeni list Crne Gore - opštinski propisi", br. ), Komisija za raspodjelu sredstava za podršku ženskom preduzetništvu donosi:
            </div>

            <div class="decision-title-main">ODLUKU</div>
            <div class="decision-title-sub">
                o raspodjeli sredstava za podršku ženskom preduzetništvu za {{ $year }}. godinu
            </div>

            {{-- Član 1 --}}
            <div class="decision-article">
                <div class="decision-article-title">Član 1</div>
                <div class="decision-article-intro">
                    Na osnovu utvrđenih kriterijuma vrednovanja i ocjene biznis planova, odlučeno je da se finansiraju biznis planovi sledećih podnositeljki prijava na ime podrške ženskom preduzetništvu i to:
                </div>

                @if($winners->count() > 0)
                    <ol class="decision-applicant-list" start="1">
                        @foreach($winners as $winner)
                            @php
                                $planTotal = $winner->businessPlan?->required_amount
                                    ?? $winner->total_budget_needed
                                    ?? 0;
                            @endphp
                            <li class="decision-applicant-item">
                                <div class="decision-applicant-head">
                                    Podnositeljka prijave: <strong>{{ $winner->getApplicantDisplayForDecision() }}</strong>
                                </div>
                                <div class="decision-applicant-details">
                                    <div>Naziv biznis plana: "{{ $winner->business_plan_name }}"</div>
                                    <div>Ukupno bodova: <strong>{{ number_format($winner->getDisplayScore(), 1, ',', '.') }}</strong></div>
                                    <div>Iznos odobrenih sredstava: {{ number_format($winner->approved_amount ?? 0, 2, ',', '.') }} €</div>
                                    <div>Ukupna vrijednost biznis plana: {{ number_format((float) $planTotal, 2, ',', '.') }} €</div>
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
                    Međusobna prava i obaveze utvrdiće se posebnim aktom – Ugovorom koji sekretar Sekretarijata za razvoj preduzetništva, komunalne poslove i saobraćaj zaključuje sa preduzetnicom odnosno nositeljkom biznisa u društvu kojem su dodijeljena sredstva u roku od 10 dana od dana izvršnosti ove Odluke.
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
            <div class="decision-article decision-obrazlozenje">
                <div class="decision-article-title">Obrazloženje</div>
                <p class="decision-article-intro">
                    Shodno Odluci o podršci ženskom preduzetništvu ("Službeni list Crne Gore - opštinski propisi", br. ) (u daljem tekstu: Odluka), Komisija za raspodjelu sredstava za podršku ženskom preduzetništvu raspisala je Javni konkurs za raspodjelu bespovratnih sredstava namijenjenih za podršku ženskom preduzetništvu u {{ $year }}. godini (u daljem tekstu: Konkurs). Konkurs je objavljen {{ $pubStart ? $fmtDate($pubStart) : '___' }}. godine i isti je trajao 20 dana, zaključno sa {{ $pubEnd ? $fmtDate($pubEnd) : '___' }}. godine, te je bio objavljen na vebsajtu Opštine Kotor, kao i putem lokalnog javnog emitera "Radio Kotor".
                </p>
                <p class="decision-article-intro">
                    Podnošenje prijava odvijalo se isključivo elektronski putem digitalnog servisa Opštine Kotor (digital.kotor.me).
                </p>
                <p class="decision-article-intro">
                    Nakon isteka roka za podnošenje prijava na Konkurs, Komisija je na prvoj sjednici održanoj dana {{ $firstSessionDate ? $fmtDate($firstSessionDate) : '___' }}. godine utvrdila da je pristiglo ukupno {{ $totalApplications }} blagovremenih prijava. Komisija je pregledala elektronski zaprimljene prijave, nakon čega je konstatovala da su {{ $incompleteCount }} prijave nepotpune u smislu priložene dokumentacije, te iste nije dalje razmatrala. Podnositeljke nepotpunih prijava imale su pravo prigovora Komisiji, nakon čega je Komisija donijela odluku o prihvatanju ili odbijanju prigovora. Komisija je konstatovala da {{ $eligibleCount }} prijave mogu biti uzete u dalje razmatranje i vrednovanje po kriterijumima utvrđenim Odlukom i Konkursom.
                </p>
                <p class="decision-article-intro">
                    Radi potpunijeg uvida u sadržaj i izvodljivost biznis planova, kao i sagledavanja ličnih kompetencija njihovih podnositeljki za realizaciju istih, Komisija je na drugoj sjednici Komisije organizovala usmeno obrazloženje biznis planova koji su obavljeni dana {{ $oralDate ? $fmtDate($oralDate) : '___' }}. godine. Nakon sprovedenih usmenih obrazloženja biznis planova, svaki član Komisije elektronski putem digitalnog servisa Opštine Kotor dodjeljuje bodove za svaki od pozitivnih kriterijuma.
                </p>
                <div class="decision-obrazlozenje-last-block">
                    <p class="decision-article-intro">
                        Na osnovu pojedinačnih ocjena, prosječne ocjene i dodatnih bodova, te izvedene preliminarne rang liste sa konačnim ocjenama biznis planova, Komisija je dana na trećoj sjednici održanoj dana {{ $rankingDate ? $fmtDate($rankingDate) : '___' }}. godine konstatovala za svaki biznis plan na preliminarnoj rang listi da li se podržava ili odbija i iznos sredstava koji se dodjeljuje i na taj način utvrdila konačnu rang listu.
                    </p>
                    <p class="decision-article-intro">
                        Na osnovu utvrđene rang liste, Komisija je donijela Odluku o raspodjeli sredstava za podršku ženskom preduzetništvu za {{ $year }} godinu, kojom se dodijeljuju sredstva za <strong>{{ $approvedWinnersCount }}</strong> biznis planova u ukupnom iznosu <strong>{{ number_format($approvedTotalEur, 2, ',', '.') }} eura</strong>.
                    </p>
                    <p class="decision-article-intro">
                        Dinamiku realizacije podržanih biznis planova i kontrolu utroška i namjenskog korišćenja sredstava vršiće nadležni Sekretarijat, u skladu sa Odlukom.
                    </p>
                    <p class="decision-article-intro" style="margin-bottom: 0;">
                        Na osnovu gore navedenog odlučeno je kao u dispozitivu ove Odluke.
                    </p>
                </div>
            </div>

            {{-- Potpis (iznad) i Dostaviti (ispod) – u istom redu vertikalno --}}
            <div class="decision-footer">
                <div class="decision-footer-row decision-footer-signature-row">
                    <div class="decision-signature">
                        <div class="decision-signature-title">Predsjednik/ca Komisije</div>
                        <div class="decision-signature-line"></div>
                        <div class="decision-signature-name">{{ $chairmanName ?? '_________________________' }}</div>
                    </div>
                </div>
                <div class="decision-footer-row decision-footer-distribution-row">
                    <div class="decision-distribution">
                        <strong>Dostaviti:</strong>
                        <ul>
                            <li>- Podnosiocima prijave (x{{ $approvedWinnersCount }})</li>
                            <li>- Članovima Komisije (x{{ $commissionMembersCount ?: 5 }})</li>
                            <li>- Sekretarijatu 16 (x2)</li>
                            <li>- Arhivi</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div style="text-align: center; margin-top: 24px;" class="no-print">
            @if((isset($isSuperAdmin) && $isSuperAdmin) || (isset($isChairman) && $isChairman))
            <button onclick="window.print()" class="btn" style="background: var(--primary); color: #fff; padding: 12px 24px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600;">Štampaj Odluku</button>
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
