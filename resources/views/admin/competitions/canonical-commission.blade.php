@extends('layouts.app')

@section('content')
@php
    $box = 'border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 16px; background: #fff;';
    $warn = 'background: #fffbeb; border: 1px solid #f59e0b; padding: 10px; border-radius: 8px; margin-bottom: 8px;';
    $bad = 'background: #fef2f2; border: 1px solid #ef4444; padding: 10px; border-radius: 8px; margin-bottom: 8px;';
    $ok = 'background: #ecfdf5; border: 1px solid #10b981; padding: 10px; border-radius: 8px; margin-bottom: 8px;';
    $reasonLabels = ['resigned' => 'Ostavka', 'dismissed' => 'Razrješenje', 'inactive' => 'Neaktivnost'];
    $stateLabels = ['active' => 'Aktivan', 'vacant' => 'Upražnjeno — čeka zamjenu', 'empty' => 'Nepopunjeno'];
    $today = now('Europe/Belgrade')->toDateString();
    $G = \App\Services\Competitions\Commission\CanonicalCommissionGovernance::class;
@endphp
<div class="container mx-auto px-4 py-6" style="max-width: 1100px;">
    <p><a href="{{ route('admin.competitions.show', $competition) }}">← {{ $competition->title }}</a></p>
    <h1 style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">Kanonska Komisija Poziva</h1>
    <p style="color: #4b5563; margin-bottom: 16px;">
        Evidencija imenovanja koje vrši nadležni organ van platforme. Komisija ima tačno {{ $commissionSize }} mjesta;
        mandat traje {{ $mandateYears }} godinu; upražnjeno mjesto popunjava se u roku od {{ $replacementDueDays }} dana.
    </p>

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

    @if($foreignCommission)
        <div style="{{ $bad }}">{{ $G::NOT_CANONICAL_MESSAGE }}</div>
    @elseif($commission === null)
        <div style="{{ $box }}">
            <h2 style="font-size: 18px; font-weight: 600;">Uspostavljanje Komisije</h2>
            @if($establishBlock)
                <div style="{{ $warn }}">{{ $establishBlock }}</div>
            @else
                <form method="POST" action="{{ route('admin.competitions.canonical-commission.establish', $competition) }}">
                    @csrf
                    <label>Naziv <input type="text" name="name" required maxlength="255"
                        value="{{ old('name', \App\Mail\CommissionAppointmentMailCopy::CANONICAL_COMMISSION_TITLE) }}" style="width: 100%;"></label>
                    <label>Akt o obrazovanju Komisije <input type="text" name="appointment_reference" required value="{{ old('appointment_reference') }}"></label>
                    <label>Datum akta <input type="date" name="appointed_on" required max="{{ $today }}" value="{{ old('appointed_on') }}"></label>
                    <label>Početak mandata <input type="date" name="mandate_starts_on" required value="{{ old('mandate_starts_on') }}"></label>
                    <button type="submit" class="btn btn-primary">Uspostavi Komisiju i dodijeli Pozivu</button>
                </form>
            @endif
        </div>
    @else
        <div style="{{ $box }}">
            <h2 style="font-size: 18px; font-weight: 600;">{{ $commission->name }}</h2>
            <p>
                Akt: {{ $commission->appointment_reference ?? '—' }}
                ({{ $commission->appointed_at?->timezone('Europe/Belgrade')->format('d.m.Y') ?? '—' }})<br>
                Mandat: {{ $commission->mandate_starts_at?->timezone('Europe/Belgrade')->format('d.m.Y') ?? '—' }}
                – {{ $commission->mandate_ends_at?->timezone('Europe/Belgrade')->format('d.m.Y') ?? '—' }}
                — <strong>{{ $mandateValid ? 'važeći' : 'nije važeći' }}</strong>
            </p>
            @if($commission->appointment_reference === null
                && $commission->appointed_at === null
                && $commission->mandate_starts_at === null
                && $commission->mandate_ends_at === null)
                @unless($callBlock)
                    <form method="POST" action="{{ route('admin.competitions.canonical-commission.establish', $competition) }}" style="margin-bottom: 12px;">
                        @csrf
                        <input type="hidden" name="name" value="{{ $commission->name }}">
                        <strong>Evidentiraj akt o obrazovanju i mandat postojeće Komisije</strong><br>
                        <label>Akt o obrazovanju Komisije <input type="text" name="appointment_reference" required></label>
                        <label>Datum akta <input type="date" name="appointed_on" required max="{{ $today }}"></label>
                        <label>Početak mandata <input type="date" name="mandate_starts_on" required value="{{ optional($commission->start_date)->format('Y-m-d') }}"></label>
                        <button type="submit" class="btn btn-primary">Evidentiraj akt i mandat</button>
                    </form>
                @endunless
            @endif
            @if($structuralBlock)
                <div style="{{ $warn }}">Komisija još nije spremna za rad: {{ $structuralBlock }}</div>
            @else
                <div style="{{ $ok }}">Komisija je strukturno potpuna.</div>
            @endif
            @if($callBlock)
                <div style="{{ $warn }}">{{ $callBlock }}</div>
            @endif
        </div>

        @foreach($seats as $seatNo => $seat)
            @php $member = $seat['member']; @endphp
            <div style="{{ $box }}">
                <h2 style="font-size: 18px; font-weight: 600;">Mjesto {{ $seatNo }} — {{ $stateLabels[$seat['state']] }}</h2>

                @if($member)
                    <p>
                        <strong>{{ $member->name }}</strong> — {{ $G::roleLabel($member->canonical_seat) }}
                        @if($member->external_category) ({{ $G::externalCategoryLabel($member->external_category) }}) @endif<br>
                        Akt o imenovanju: {{ $member->appointment_reference ?? '—' }}, {{ $member->appointed_at?->timezone('Europe/Belgrade')->format('d.m.Y') ?? '—' }}<br>
                        PIB društva koje predstavlja: {{ $seat['maskedPib'] ?? 'nije naveden' }}<br>
                        Izjave: {{ $seat['declarationsSigned'] ? 'potpisane '.$member->declarations_signed_at->timezone('Europe/Belgrade')->format('d.m.Y') : 'nijesu evidentirane' }}<br>
                        Sukob interesa (PIB): <strong>{{ $seat['pibConflict'] ? 'DA — članstvo blokirano' : 'nema' }}</strong>
                    </p>
                    @if($seat['actionBlock'])
                        <div style="{{ $warn }}">Član ne može obavljati radnje Komisije: {{ $seat['actionBlock'] }}</div>
                    @endif

                    @if($member->canonical_seat === null)
                        @if(!$mandateValid)
                            <div style="{{ $warn }}">Prvo evidentirajte akt o obrazovanju i važeći mandat Komisije.</div>
                        @elseif(!$callBlock)
                            <form method="POST" action="{{ route('admin.competitions.canonical-commission.appoint', $competition) }}" style="margin-bottom: 12px;">
                                @csrf
                                <input type="hidden" name="email" value="{{ $member->user?->email }}">
                                <input type="hidden" name="seat_no" value="{{ $seatNo }}">
                                <strong>Evidentiraj imenovanje postojećeg člana</strong><br>
                                <label>Uloga
                                    <select name="role" required>
                                        @foreach($roles as $value => $label)
                                            @if(($member->position === 'predsjednik' && $value === $G::ROLE_PREDSJEDNIK)
                                                || ($member->position !== 'predsjednik' && $value !== $G::ROLE_PREDSJEDNIK && !in_array($value, $usedRoles, true)))
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </label>
                                <label>Kategorija (samo spoljašnji član)
                                    <select name="external_category">
                                        <option value="">—</option>
                                        @foreach($externalCategories as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label>Akt o imenovanju <input type="text" name="appointment_reference" required></label>
                                <label>Datum akta <input type="date" name="appointed_on" required max="{{ $today }}"></label>
                                <label>PIB društva koje član predstavlja (opciono) <input type="text" name="represented_company_pib" maxlength="32"></label>
                                <button type="submit" class="btn btn-primary">Evidentiraj imenovanje</button>
                            </form>
                        @endif
                    @endif

                    @if(!$callBlock && $member->canonical_seat !== null)
                        @unless($seat['declarationsSigned'])
                            <form method="POST" action="{{ route('admin.competitions.canonical-commission.declarations', [$competition, $member]) }}" style="margin-bottom: 8px;">
                                @csrf
                                <strong>Evidentiraj potpisane izjave (jednokratno, bez naknadne izmjene)</strong><br>
                                <label><input type="checkbox" name="confidentiality_signed" value="1" required> Izjava o povjerljivosti potpisana</label>
                                <label><input type="checkbox" name="conflict_of_interest_signed" value="1" required> Izjava o nepostojanju sukoba interesa potpisana</label>
                                <label>Datum potpisivanja <input type="date" name="signed_on" required max="{{ $today }}"></label>
                                <button type="submit" class="btn btn-primary">Evidentiraj izjave</button>
                            </form>
                        @endunless
                        <form method="POST" action="{{ route('admin.competitions.canonical-commission.vacate', [$competition, $member]) }}">
                            @csrf
                            <strong>Evidentiraj upražnjenost mjesta</strong><br>
                            <select name="reason" required>
                                @foreach($vacancyReasons as $reason)
                                    <option value="{{ $reason }}">{{ $reasonLabels[$reason] ?? $reason }}</option>
                                @endforeach
                            </select>
                            <label>Nastanak upražnjenosti <input type="datetime-local" name="triggered_at" required></label>
                            <button type="submit" class="btn btn-danger">Evidentiraj upražnjenost</button>
                        </form>
                    @endif
                @elseif($seat['state'] === 'vacant')
                    <p>
                        Prethodni član: {{ $seat['vacatedMember']?->name ?? '—' }} ({{ $G::roleLabel($seat['vacancy']->canonical_seat) }}),
                        razlog: {{ $reasonLabels[$seat['vacancy']->vacancy_reason] ?? $seat['vacancy']->vacancy_reason }}.<br>
                        Upražnjenost od {{ $seat['vacancy']->vacancy_triggered_at->timezone('Europe/Belgrade')->format('d.m.Y H:i') }};
                        rok za zamjenu {{ $seat['vacancy']->due_at->timezone('Europe/Belgrade')->format('d.m.Y H:i') }}.
                    </p>
                    @unless($callBlock)
                        <form method="POST" action="{{ route('admin.competitions.canonical-commission.replace', [$competition, $seat['vacatedMember']]) }}">
                            @csrf
                            <strong>Evidentiraj zamjenu (preuzima mjesto {{ $seatNo }} i ulogu)</strong><br>
                            <label>E-mail naloga člana <input type="email" name="email" required></label>
                            <label>Akt o imenovanju <input type="text" name="appointment_reference" required></label>
                            <label>Datum akta <input type="date" name="appointed_on" required max="{{ $today }}"></label>
                            @if($seat['vacancy']->canonical_seat === $G::ROLE_SPOLJASNJI)
                                <select name="external_category">
                                    @foreach($externalCategories as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            @endif
                            <label>PIB društva koje član predstavlja (opciono) <input type="text" name="represented_company_pib" maxlength="32"></label>
                            <button type="submit" class="btn btn-primary">Evidentiraj zamjenu</button>
                        </form>
                    @endunless
                @else
                    @unless($callBlock)
                        <form method="POST" action="{{ route('admin.competitions.canonical-commission.appoint', $competition) }}">
                            @csrf
                            <input type="hidden" name="seat_no" value="{{ $seatNo }}">
                            <strong>Evidentiraj imenovanje</strong><br>
                            <label>E-mail naloga člana <input type="email" name="email" required></label>
                            <label>Uloga
                                <select name="role" required>
                                    @foreach($roles as $value => $label)
                                        @unless(in_array($value, $usedRoles, true))
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endunless
                                    @endforeach
                                </select>
                            </label>
                            <label>Kategorija (samo spoljašnji član)
                                <select name="external_category">
                                    <option value="">—</option>
                                    @foreach($externalCategories as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>Akt o imenovanju <input type="text" name="appointment_reference" required></label>
                            <label>Datum akta <input type="date" name="appointed_on" required max="{{ $today }}"></label>
                            <label>PIB društva koje član predstavlja (opciono) <input type="text" name="represented_company_pib" maxlength="32"></label>
                            <button type="submit" class="btn btn-primary">Evidentiraj imenovanje</button>
                        </form>
                    @endunless
                @endif
            </div>
        @endforeach

        <div style="{{ $box }}">
            <h2 style="font-size: 18px; font-weight: 600;">Sukobi interesa (PIB)</h2>
            <p style="color: #4b5563;">PIB društva koje član predstavlja upoređuje se sa normalizovanim PIB-om podnosilaca prijava na Pozive ove Komisije. Prikazane su samo posljednje 3 cifre.</p>
            @unless($callBlock)
                <form method="POST" action="{{ route('admin.competitions.canonical-commission.conflicts', $competition) }}" style="margin-bottom: 8px;">
                    @csrf
                    <button type="submit" class="btn btn-secondary">Ponovo provjeri PIB aktivnih članova</button>
                </form>
            @endunless
            <ul>
                @forelse($conflicts as $conflict)
                    <li>
                        {{ $conflict['detected_at']?->timezone('Europe/Belgrade')->format('d.m.Y H:i') }} —
                        {{ $conflict['blocked_appointment'] ? 'Blokirano imenovanje' : 'Aktivni član' }}: {{ $conflict['member_name'] ?? '—' }};
                        PIB {{ $conflict['masked_pib'] }} poklapa se sa Prijavom #{{ $conflict['application_id'] ?? '—' }}.
                        {{ $conflict['reason'] }}
                    </li>
                @empty
                    <li>Nema evidentiranih sukoba.</li>
                @endforelse
            </ul>
        </div>

        <div style="{{ $box }}">
            <h2 style="font-size: 18px; font-weight: 600;">Istorija članstva i zamjena</h2>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr><th align="left">Član</th><th align="left">Mjesto / uloga</th><th align="left">Imenovan</th><th align="left">Status</th><th align="left">Izjave</th></tr>
                </thead>
                <tbody>
                    @foreach($history as $row)
                        <tr>
                            <td>{{ $row->name }}</td>
                            <td>{{ $row->canonical_seat_no }} — {{ $G::roleLabel($row->canonical_seat) }}</td>
                            <td>{{ $row->appointed_at?->timezone('Europe/Belgrade')->format('d.m.Y') ?? '—' }}</td>
                            <td>
                                @if($row->replaced_at)
                                    Zamijenjen {{ $row->replaced_at->timezone('Europe/Belgrade')->format('d.m.Y') }}
                                @elseif($row->status === 'active')
                                    Aktivan
                                @else
                                    {{ $reasonLabels[$row->status] ?? $row->status }}
                                @endif
                            </td>
                            <td>{{ $row->hasSignedDeclarations() ? $row->declarations_signed_at->timezone('Europe/Belgrade')->format('d.m.Y') : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <ul style="margin-top: 12px;">
                @foreach($replacements as $replacement)
                    <li>
                        Mjesto {{ $replacement->seat_no }}: upražnjeno {{ $replacement->vacancy_triggered_at->timezone('Europe/Belgrade')->format('d.m.Y H:i') }},
                        rok {{ $replacement->due_at->timezone('Europe/Belgrade')->format('d.m.Y H:i') }},
                        @if($replacement->replaced_at)
                            popunjeno {{ $replacement->replaced_at->timezone('Europe/Belgrade')->format('d.m.Y H:i') }}{{ $replacement->late ? ' (nakon roka)' : '' }}.
                        @else
                            čeka zamjenu.
                        @endif
                        @if($replacement->compliance_warning) <em>{{ $replacement->compliance_warning }}</em> @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $eventLabels = [
            'commission_established' => 'Uspostavljena Komisija',
            'commission_appointment_recorded' => 'Evidentirano imenovanje',
            'commission_appointment_blocked' => 'Imenovanje blokirano (sukob interesa)',
            'commission_declarations_recorded' => 'Evidentirane izjave člana',
            'commission_member_vacated' => 'Mjesto upražnjeno',
            'commission_member_replaced' => 'Evidentirana zamjena',
            'commission_conflicts_checked' => 'Provjera PIB sukoba',
            'commission_session_attendance_updated' => 'Izmijenjeno prisustvo na nepotvrđenoj sjednici',
            'commission_session_confirmed' => 'Potvrđena sjednica Komisije',
        ];
    @endphp
    <div style="{{ $box }}">
        <h2 style="font-size: 18px; font-weight: 600;">Revizijski trag Komisije</h2>
        <ul>
            @forelse($events as $event)
                <li>
                    {{ $event->created_at?->timezone('Europe/Belgrade')->format('d.m.Y H:i') }} —
                    {{ $eventLabels[$event->action] ?? $event->action }}
                    @if(isset($event->payload['seat_no'])) (mjesto {{ $event->payload['seat_no'] }}) @endif
                    — evidentirao: {{ $eventActors[$event->actor_user_id] ?? ('#'.$event->actor_user_id) }}
                </li>
            @empty
                <li>Nema evidentiranih radnji.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
