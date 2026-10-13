<p>Obavještenje o odluci Komisije o prigovoru na nalaz o nepotpunosti dokumentacije. Ovo obavještenje nije službeni akt o dodjeli sredstava.</p>
<p>Javni poziv: {{ $competitionTitle }}</p>
<p>Prijava: {{ $applicationReference }}</p>
<p>Datum odluke: {{ $decidedAt->format('d.m.Y. H:i:s') }} (Europe/Belgrade)</p>
@if($decision === \App\Models\ApplicationComplaint::ACCEPTED)
<p>Odluka: prigovor je <strong>prihvaćen</strong>.</p>
<p>Prijava se smatra potpunom i nastavlja postupak u fazi bodovanja. Podneseni dosije ostaje neizmijenjen.</p>
@else
<p>Odluka: prigovor je <strong>odbijen</strong>.</p>
<p>Nalaz o nepotpunosti ostaje na snazi i prijava ne učestvuje u daljem bodovanju. Podneseni dosije ostaje neizmijenjen.</p>
@endif
<p>Status prijave: <a href="{{ $statusUrl }}">{{ $statusUrl }}</a></p>
