<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $notice->title }} — {{ config('app.name', 'Digital Kotor') }}</title>
    <style>
        body { margin: 0; padding: 24px 0; background: #f9fafb; font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif; }
        .public-notice-wrap { max-width: 1200px; margin: 0 auto; padding: 0 16px; }
        .public-notice-nav { margin-bottom: 16px; }
        .public-notice-nav a { color: #0B3D91; font-weight: 600; text-decoration: none; }
        .public-notice-nav a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="public-notice-wrap">
        <div class="public-notice-nav">
            <a href="{{ route('home') }}">← Početna</a>
        </div>
        @include('competitions.partials.decision-document')
    </div>
</body>
</html>
