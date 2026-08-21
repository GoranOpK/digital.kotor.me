@php
    /** @var \App\Services\Payments\PaymentConfirmation $confirmation */
@endphp
<!DOCTYPE html>
<html lang="me">
<head>
    <meta charset="utf-8">
    <title>{{ $confirmation->title }}</title>
    <style>
        @page { size: A4; margin: 18mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 18px; margin: 0 0 8px 0; }
        .issuer { font-size: 13px; font-weight: bold; margin-bottom: 16px; }
        .row { margin: 0 0 8px 0; }
        .label { color: #444; font-size: 11px; }
        .value { font-size: 13px; }
        .mono { font-family: DejaVu Sans, sans-serif; letter-spacing: 0.02em; }
        .disclaimer { margin-top: 24px; padding-top: 12px; border-top: 1px solid #ccc; font-size: 11px; line-height: 1.45; }
        .note { margin-top: 8px; font-size: 10px; color: #444; }
    </style>
</head>
<body>
    <div class="issuer">{{ $confirmation->issuer }}</div>
    <h1>{{ $confirmation->title }}</h1>
    <div class="row"><span class="label">Status</span><div class="value">{{ $confirmation->statusLabel }}</div></div>
    @if($confirmation->succeededAtLabel)
        <div class="row"><span class="label">Datum i vrijeme uspješne transakcije</span><div class="value">{{ $confirmation->succeededAtLabel }}</div></div>
    @endif
    <div class="row"><span class="label">Uplatilac</span><div class="value">{{ $confirmation->payerLabel }}</div></div>
    <div class="row"><span class="label">Korisnička kategorija</span><div class="value">{{ $confirmation->userTypeLabel }}</div></div>
    <div class="row"><span class="label">Vrsta plaćanja</span><div class="value">{{ $confirmation->paymentTypeName }}</div></div>
    <div class="row"><span class="label">Račun primaoca</span><div class="value mono">{{ $confirmation->accountNumber }}</div></div>
    @if($confirmation->accountName)
        <div class="row"><span class="label">Naziv računa</span><div class="value">{{ $confirmation->accountName }}</div></div>
    @endif
    <div class="row"><span class="label">Iznos</span><div class="value">{{ $confirmation->amountWithCurrency() }}</div></div>
    <div class="row"><span class="label">Identifikator transakcije</span><div class="value mono">{{ $confirmation->merchantTransactionId }}</div></div>
    @if($confirmation->gatewayReference)
        <div class="row"><span class="label">Referenca provajdera</span><div class="value mono">{{ $confirmation->gatewayReference }}</div></div>
    @endif
    <div class="disclaimer">{{ $confirmation->disclaimer }}</div>
    <div class="note">Ovaj dokument nije bankarska uplatnica. Svrha, model, šifra i poziv na broj nijesu dio ove potvrde dok katalogska konfiguracija nije usvojena.</div>
</body>
</html>
