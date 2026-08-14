<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Odjava sa Newslettera</title>
</head>
<body style="font-family:Arial,Helvetica,sans-serif; background:#f3f4f6; margin:0; padding:32px;">
    <div style="max-width:480px; margin:0 auto; background:#fff; padding:24px; border-radius:12px; border:1px solid #e5e7eb;">
        <h1 style="font-size:22px; margin:0 0 16px;">Odjava sa Newslettera</h1>

        @if (session('error') || ! $valid)
            <p>{{ session('error') ?: $message }}</p>
        @else
            <p>Potvrdite da želite da se odjavite sa Newslettera Kalendara kulture.</p>
            <form method="POST" action="{{ route('newsletter.unsubscribe.public.consume', ['token' => $token]) }}">
                @csrf
                <p>
                    <label>
                        <input type="checkbox" name="confirm_unsubscribe" value="1" required>
                        Potvrđujem odjavu
                    </label>
                </p>
                @error('confirm_unsubscribe')
                    <p style="color:#b91c1c;">{{ $message }}</p>
                @enderror
                <button type="submit">Odjavi se</button>
            </form>
        @endif
    </div>
</body>
</html>
