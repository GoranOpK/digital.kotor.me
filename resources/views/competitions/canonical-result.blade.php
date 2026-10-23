<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rezultat Poziva — {{ config('app.name', 'Digital Kotor') }}</title>
    <style>
        body { margin: 0; padding: 48px 16px; background: #f9fafb; font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif; color: #111827; }
        .box { max-width: 640px; margin: 0 auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 24px; }
        h1 { margin: 0 0 12px; font-size: 22px; color: #0B3D91; }
        p { margin: 0 0 12px; color: #374151; line-height: 1.5; }
        a { color: #0B3D91; font-weight: 600; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Rezultat Poziva</h1>
        <p><strong>Poziv:</strong> {{ $result['competition_title'] }} ({{ $result['competition_year'] }})</p>
        <p><strong>Odluka broj:</strong> {{ $result['decision_number'] }}</p>
        <p><strong>Datum Odluke:</strong> {{ \Carbon\Carbon::parse($result['decision_date'])->format('d.m.Y') }}</p>
        <p><strong>Datum objave:</strong> {{ \Carbon\Carbon::parse($result['published_at'])->format('d.m.Y') }}</p>
        <p><a href="{{ $result['signed_copy_url'] }}">Potpisani primjerak Odluke (PDF)</a></p>
        <p><a href="{{ route('home') }}">Nazad na početnu</a></p>
    </div>
</body>
</html>
