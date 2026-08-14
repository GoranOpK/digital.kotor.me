<!doctype html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('newsletter.priority_subject') }}</title>
</head>
<body style="margin:0; padding:0; background:#f3f4f6; font-family:Arial, Helvetica, sans-serif; color:#111827;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6; padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px; width:100%; background:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #e5e7eb;">
                    <tr>
                        <td style="background:#7a0f17; padding:18px 24px;">
                            <h1 style="margin:0; color:#ffffff; font-size:22px; line-height:1.3;">Važna izmjena događaja</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <p style="margin:0 0 16px; font-size:15px; line-height:1.6;">
                                Došlo je do poslovno značajne izmjene događaja koji ste već dobili u Newsletteru.
                            </p>

                            @foreach ($payload->groups as $group)
                                <h2 style="margin:20px 0 10px; font-size:18px; color:#0b3d91;">
                                    {{ $group['organizer_name'] }}
                                </h2>
                                @if (! empty($group['organizer_url']))
                                    <p style="margin:0 0 12px; font-size:14px;">
                                        <a href="{{ $group['organizer_url'] }}" style="color:#0b3d91;">Pregled organizatora</a>
                                    </p>
                                @endif

                                @foreach ($group['changes'] as $change)
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px; border:1px solid #e5e7eb; border-radius:8px;">
                                        <tr>
                                            <td style="padding:14px;">
                                                <p style="margin:0 0 8px; font-size:16px; font-weight:700;">
                                                    {{ $change['naslov'] }}
                                                </p>
                                                <p style="margin:0 0 8px; font-size:14px; color:#7a0f17; font-weight:700;">
                                                    {{ $change['change_label'] }}
                                                </p>
                                                @if (! empty($change['term_date']))
                                                    <p style="margin:0 0 4px; font-size:14px; color:#374151;">
                                                        Termin: {{ $change['term_date'] }}
                                                        @if (! empty($change['term_time']))
                                                            · {{ $change['term_time'] }}
                                                        @endif
                                                    </p>
                                                @endif
                                                @if (! empty($change['location']))
                                                    <p style="margin:0 0 8px; font-size:14px; color:#374151;">
                                                        Lokacija: {{ $change['location'] }}
                                                    </p>
                                                @endif
                                                <p style="margin:10px 0 0; font-size:14px;">
                                                    <a href="{{ $change['detail_url'] }}" style="color:#7a0f17; font-weight:700;">Detalji događaja</a>
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                @endforeach
                            @endforeach

                            <p style="margin:24px 0 0; font-size:13px; color:#6b7280; line-height:1.6;">
                                Ako više ne želite da primate Newsletter,
                                <a href="{{ $payload->unsubscribeUrl }}" style="color:#0b3d91;">odjavite se ovdje</a>.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
