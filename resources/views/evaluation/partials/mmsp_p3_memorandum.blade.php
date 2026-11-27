{{-- Memorandum Obrasca P3. Isti vizuelni koncept kao M3, izolovana MMSP varijanta. --}}
@php
    $application->loadMissing('competition.upNumber');
    $mmspP3UpNumber = $application->competition?->upNumber?->number ?? '—';
    $mmspP3Serial = $application->redni_broj ?? '—';
    $mmspP3ApplicationNumber = $mmspP3UpNumber.'/'.$mmspP3Serial;
@endphp
<style>
    .mmsp-p3-memorandum .obrazac-zaglavlje {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }
    .mmsp-p3-memorandum .obrazac-zaglavlje-top {
        display: flex;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 16px;
    }
    .mmsp-p3-memorandum .obrazac-grb {
        flex-shrink: 0;
        line-height: 0;
    }
    .mmsp-p3-memorandum .obrazac-grb img {
        height: 2cm;
        width: auto;
        display: block;
    }
    .mmsp-p3-memorandum .obrazac-org {
        flex: 1;
        font-size: 13px;
        line-height: 1.5;
        color: #111;
    }
    .mmsp-p3-memorandum .obrazac-org p { margin: 0 0 2px 0; }
    .mmsp-p3-memorandum .obrazac-contact {
        text-align: right;
        font-size: 13px;
        line-height: 1.5;
        color: #111;
    }
    .mmsp-p3-memorandum .obrazac-contact p { margin: 0 0 2px 0; }
    .mmsp-p3-memorandum .obrazac-broj-i-naslov {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #e5e7eb;
    }
    .mmsp-p3-memorandum .obrazac-broj-prijave {
        font-size: 14px;
        font-weight: 600;
        color: #111;
    }
    .mmsp-p3-memorandum .obrazac-1a-1b {
        font-size: 14px;
        font-weight: 700;
        color: #374151;
    }
    @media print {
        @page {
            size: A4;
            margin: 12mm 10mm;
        }
        .mmsp-p3-memorandum .obrazac-zaglavlje {
            box-shadow: none;
            border: 1px solid #ccc;
            margin-bottom: 12px;
            padding: 12px 16px;
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .mmsp-p3-memorandum .obrazac-grb img { height: 2cm; }
        .mmsp-p3-memorandum .obrazac-org,
        .mmsp-p3-memorandum .obrazac-contact {
            font-size: 11.7px;
        }
        .mmsp-p3-memorandum .obrazac-broj-prijave,
        .mmsp-p3-memorandum .obrazac-1a-1b {
            font-size: 12.6px;
        }
    }
</style>
<div class="mmsp-p3-memorandum">
    <div class="obrazac-zaglavlje">
        <div class="obrazac-zaglavlje-top">
            <div class="obrazac-grb">
                <img src="{{ asset('images/srednji_grb.png') }}" alt="Grb Opštine Kotor" class="obrazac-grb-img" onerror="this.onerror=null; this.src='{{ asset('images/srednji_grb.svg') }}';" style="height: 2cm; width: auto; display: block;">
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
                Broj prijave: {{ $mmspP3ApplicationNumber }}
            </div>
            <div class="obrazac-1a-1b">Obrazac P3</div>
        </div>
    </div>
</div>
