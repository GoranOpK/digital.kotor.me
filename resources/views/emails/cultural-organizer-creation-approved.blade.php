<!doctype html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zahtjev odobren</title>
</head>
<body style="margin:0; padding:0; background:#f3f4f6; font-family:Arial, Helvetica, sans-serif; color:#111827;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6; padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px; width:100%; background:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #e5e7eb;">
                    <tr>
                        <td style="background:#7a0f17; padding:18px 24px;">
                            <div style="text-align:center; margin-bottom:12px;">
                                <img src="{{ asset('img/kalendar-kulture-logo.png') }}" alt="Logo Kalendara kulture" style="display:inline-block; max-width:120px; width:100%; height:auto;">
                            </div>
                            <h1 style="margin:0; color:#ffffff; font-size:22px; line-height:1.3;">Kalendar kulture Opštine Kotor</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <p style="margin:0 0 14px; font-size:15px; line-height:1.6;">
                                Poštovani,
                            </p>
                            <p style="margin:0 0 14px; font-size:15px; line-height:1.6;">
                                Zahtjev za kreiranje Organizatora
                                <strong>{{ $organizerName }}</strong>
                                je odobren.
                            </p>
                            <p style="margin:0 0 14px; font-size:15px; line-height:1.6;">
                                Odobreni ste kao Moderator tog Organizatora i možete koristiti Kalendar kulture kroz platformu Digital Kotor.
                            </p>
                            <p style="margin:0 0 14px; font-size:15px; line-height:1.6;">
                                Moderatorski radni prostor:
                                <a href="{{ $workspaceUrl }}" style="color:#7a0f17;">{{ $workspaceUrl }}</a>
                            </p>
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
