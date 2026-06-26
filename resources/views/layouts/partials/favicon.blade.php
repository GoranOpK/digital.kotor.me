@php
    if (file_exists(public_path('img/logo.png'))) {
        $faviconPath = public_path('img/logo.png');
        $faviconUrl = asset('img/logo.png');
        $faviconType = 'image/png';
    } elseif (file_exists(public_path('favicon.svg'))) {
        $faviconPath = public_path('favicon.svg');
        $faviconUrl = asset('favicon.svg');
        $faviconType = 'image/svg+xml';
    } else {
        $faviconPath = public_path('images/srednji_grb.svg');
        $faviconUrl = asset('images/srednji_grb.svg');
        $faviconType = 'image/svg+xml';
    }
    $faviconVersion = filemtime($faviconPath);
@endphp
<link rel="icon" href="{{ $faviconUrl }}?v={{ $faviconVersion }}" type="{{ $faviconType }}">
<link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v={{ $faviconVersion }}" type="{{ $faviconType }}">
<link rel="apple-touch-icon" href="{{ $faviconUrl }}?v={{ $faviconVersion }}">
