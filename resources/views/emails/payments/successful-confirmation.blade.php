@php
    /** @var \App\Services\Payments\PaymentConfirmation $confirmation */
@endphp
<p>Poštovani,</p>
<p>Transakcija kroz e-Plaćanje Digital Kotor je uspješna.</p>
<p>
    Iznos: {{ $confirmation->amountWithCurrency() }}<br>
    Vrsta plaćanja: {{ $confirmation->paymentTypeName }}<br>
    Identifikator transakcije: {{ $confirmation->merchantTransactionId }}
</p>
<p>{{ $confirmation->disclaimer }}</p>
<p>PDF potvrda je priložena kada je tehnički dostupna. Možete je preuzeti i sa stranice rezultata plaćanja.</p>
<p>{{ $confirmation->issuer }}</p>
