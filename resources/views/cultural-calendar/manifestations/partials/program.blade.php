{{--
  Program Manifestacije — PO-TS9-07D / BR-268.
  Jedinica: Održavanje; grupisano po datumu; sort već u query.
--}}
@if($programByDate->isEmpty())
    <p class="text-gray-500">Trenutno nema javno dostupnog programa.</p>
@else
    @foreach($programByDate as $dateKey => $rows)
        <div class="kk-mf-program-day">
            <h3 class="text-base font-semibold text-gray-900 mb-3">
                {{ \Carbon\Carbon::createFromFormat('Y-m-d', $dateKey)->format('d.m.Y') }}
            </h3>

            @foreach($rows as $row)
                @php
                    /** @var \App\Models\CulturalOccurrence $occurrence */
                    $occurrence = $row['occurrence'];
                    /** @var \App\Models\CulturalEventEntry $event */
                    $event = $row['event'];
                    $occLabel = $occurrence->publicDetailStatusLabel();
                    $occLocation = $occurrence->publicLocationDisplayName();
                    $eventCancelled = $event->status === \App\Models\CulturalEventEntry::STATUS_CANCELLED;
                    $eventHref = route('cultural-calendar.show', [
                        'event' => $event,
                        'back' => request()->getRequestUri(),
                    ]);
                @endphp
                <div class="kk-mf-program-item">
                    <div class="kk-show-meta">
                        <strong>Vrijeme:</strong>
                        @if($occurrence->cjelodnevno)
                            Cjelodnevno
                        @elseif(filled($occurrence->vrijeme_od))
                            {{ substr((string) $occurrence->vrijeme_od, 0, 5) }}
                            @if(filled($occurrence->vrijeme_do))
                                - {{ substr((string) $occurrence->vrijeme_do, 0, 5) }}
                            @endif
                        @else
                            Vrijeme nije definisano
                        @endif
                        @if($occLabel)
                            <span class="kk-show-occ-status">{{ $occLabel }}</span>
                        @endif
                        @if($eventCancelled)
                            <span class="kk-show-occ-status">Otkazano</span>
                        @endif
                    </div>

                    <div class="kk-show-meta">
                        <strong>Naziv:</strong>
                        <a href="{{ $eventHref }}" class="text-blue-700 underline">{{ $event->naslov }}</a>
                    </div>

                    @if($occLocation)
                        <div class="kk-show-meta">
                            <strong>Lokacija:</strong> {{ $occLocation }}
                        </div>
                    @endif

                    <div class="kk-show-meta">
                        <a href="{{ $eventHref }}" class="text-sm font-medium text-gray-800 underline">Detalji događaja</a>
                    </div>
                </div>
            @endforeach
        </div>
    @endforeach
@endif
