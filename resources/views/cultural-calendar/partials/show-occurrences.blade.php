{{--
  6A-08 — lista Održavanja na javnom detalju (TS-009 §7.3.3).
  @var \Illuminate\Support\Collection<int, \App\Models\CulturalOccurrence> $occurrences
--}}
@if($occurrences->isNotEmpty())
    <div class="kk-show-occurrences" role="list">
        @foreach($occurrences as $occurrence)
            @php
                $occLabel = $occurrence->publicDetailStatusLabel();
                $occLocation = $occurrence->publicLocationDisplayName();
            @endphp
            <div class="kk-show-occurrence" role="listitem">
                <div class="kk-show-meta">
                    <strong>Datum:</strong>
                    {{ optional($occurrence->datum)->format('d.m.Y') }}
                    @if($occLabel)
                        <span class="kk-show-occ-status">{{ $occLabel }}</span>
                    @endif
                </div>

                @if($occurrence->cjelodnevno)
                    <div class="kk-show-meta">
                        <strong>Vrijeme:</strong> Cjelodnevno
                    </div>
                @elseif($occurrence->vrijeme_od)
                    <div class="kk-show-meta">
                        <strong>Vrijeme:</strong>
                        {{ substr((string) $occurrence->vrijeme_od, 0, 5) }}
                        @if($occurrence->vrijeme_do)
                            - {{ substr((string) $occurrence->vrijeme_do, 0, 5) }}
                        @endif
                    </div>
                @endif

                @if($occLocation)
                    <div class="kk-show-meta">
                        <strong>Lokacija:</strong> {{ $occLocation }}
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endif
