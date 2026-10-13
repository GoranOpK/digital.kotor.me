<p>Obavještenje o rezultatu provjere potpunosti. Ovo obavještenje nije službeni akt o odbijanju.</p>
<p>Podnosilac: {{ $frozenApplicantName }}</p>
<p>Konkurs: {{ $competitionTitle }}</p>
<p>Biznis plan: {{ $businessPlanName }}</p>
<p>Katalog profila: {{ $catalogKey }}</p>
<p>Dokumentacija podnesene prijave je ocijenjena kao nepotpuna. Podneseni dosije ostaje neizmijenjen.</p>
@if($reason)
<p>Obrazloženje Komisije: {{ $reason }}</p>
@endif
<p>Prigovor se može podnijeti preko digitalnog servisa u roku od {{ $complaintWindowDays }} dana od slanja ovog obavještenja. Prigovor ne otvara prijavu za izmjenu ni za naknadno dostavljanje dokumenata.</p>
@if($complaintDeadline)
<p>Rok za prigovor ističe: {{ $complaintDeadline->format('d.m.Y. H:i:s') }} (Europe/Belgrade).</p>
@endif
@if($complaintUrl)
<p>Status provjere i podnošenje prigovora: <a href="{{ $complaintUrl }}">{{ $complaintUrl }}</a></p>
@endif
