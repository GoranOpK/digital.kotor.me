<!doctype html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sedmični pregled događaja</title>
</head>
<body style="margin:0; padding:0; background:#f3f4f6; font-family:Arial, Helvetica, sans-serif; color:#111827;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6; padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px; width:100%; background:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #e5e7eb;">
                    <tr>
                        <td style="background:#0b3d91; padding:18px 24px;">
                            <div style="text-align:center; margin-bottom:12px;">
                                <img src="{{ asset('img/kalendar-kulture-logo.png') }}" alt="Logo Kalendara kulture" style="display:inline-block; max-width:120px; width:100%; height:auto;">
                            </div>
                            <h1 style="margin:0; color:#ffffff; font-size:22px; line-height:1.3;">Sedmični pregled događaja</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <p style="margin:0 0 14px; font-size:15px; line-height:1.6;">
                                Poštovani,
                            </p>
                            <p style="margin:0 0 14px; font-size:15px; line-height:1.6;">
                                u nastavku je link ka događajima za narednu sedmicu
                                (<strong>{{ $weekStart->format('d.m.Y') }}</strong> - <strong>{{ $weekEnd->format('d.m.Y') }}</strong>):
                            </p>
                            <p style="margin:18px 0;">
                                <a href="{{ $weekEventsLink }}" style="display:inline-block; background:#7a0f17; color:#ffffff; text-decoration:none; padding:10px 16px; border-radius:8px; font-weight:700;">
                                    Otvori događaje za narednu sedmicu
                                </a>
                            </p>
                            <p style="margin:0 0 10px; font-size:15px; line-height:1.6;">
                                Napomena: prije samog dolaska na događaj, molimo vas da na istom linku provjerite da li je bilo izmjena:
                            </p>
                            <ul style="margin:0 0 14px; padding-left:18px; color:#374151; font-size:14px; line-height:1.6;">
                                <li>da li je događaj otkazan,</li>
                                <li>da li je pomjeren za drugi dan ili termin,</li>
                                <li>da li su dodati novi događaji u okviru te sedmice.</li>
                            </ul>
                            <p style="margin:20px 0 0; font-size:14px; color:#6b7280;">
                                Srdačan pozdrav,<br>
                                Opština Kotor
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

