@php
    if (file_exists(public_path('img/logo.png'))) {
        $faviconUrl = asset('img/logo.png');
        $faviconType = 'image/png';
    } elseif (file_exists(public_path('favicon.svg'))) {
        $faviconUrl = asset('favicon.svg');
        $faviconType = 'image/svg+xml';
    } else {
        $faviconUrl = asset('images/srednji_grb.svg');
        $faviconType = 'image/svg+xml';
    }
@endphp
<link rel="icon" href="{{ $faviconUrl }}" type="{{ $faviconType }}">
<link rel="shortcut icon" href="{{ $faviconUrl }}" type="{{ $faviconType }}">
