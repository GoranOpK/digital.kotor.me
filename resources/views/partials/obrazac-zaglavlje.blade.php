{{--
  Shared official obrazac/report header (M1b / Obrazac 2 standard).
  Required for Obrazac 4, 4a, Rang lista, and all future reports/forms with an official header.
  Styles: .obrazac-form-page .obrazac-zaglavlje* in partials.obrazac-form-styles / obrazac-form.css

  Layout of .obrazac-broj-i-naslov: left meta first (Broj prijave / Konkurs), form label on the right.

  @param string $obrazacLabel  Short form code, e.g. "Obrazac 4"
  @param string $obrazacTitle  Main centered title (h1.obrazac-naslov-prijava)
  @param string|null $brojPrijave  Optional application number value → renders "Broj prijave: …" on the left
  @param string|\Illuminate\Support\HtmlString|null $leftMeta  Optional full left meta line (e.g. "Konkurs: UP-…"); overrides brojPrijave when set
  @param string|\Illuminate\Support\HtmlString|null $podnaslov  Optional subtitle under the title
--}}
@php
    $obrazacLabel = $obrazacLabel ?? '';
    $obrazacTitle = $obrazacTitle ?? '';
    $brojPrijave = $brojPrijave ?? null;
    $leftMeta = $leftMeta ?? null;
    $podnaslov = $podnaslov ?? null;
@endphp
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
        @if(filled($leftMeta))
            <div class="obrazac-broj-prijave">{!! $leftMeta !!}</div>
        @elseif(filled($brojPrijave))
            <div class="obrazac-broj-prijave">
                Broj prijave: {{ $brojPrijave }}
            </div>
        @endif
        @if(filled($obrazacLabel))
            <div class="obrazac-1a-1b">{{ $obrazacLabel }}</div>
        @endif
    </div>
    @if(filled($obrazacTitle))
        <h1 class="obrazac-naslov-prijava">{{ $obrazacTitle }}</h1>
    @endif
    @if(filled($podnaslov))
        <p class="obrazac-podnaslov">{!! $podnaslov !!}</p>
    @endif
</div>
