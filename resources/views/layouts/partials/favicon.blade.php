@php
    $faviconSvg = public_path('favicon.svg');
    $favicon32 = public_path('favicon-32.png');
    $favicon48 = public_path('favicon-48.png');
    $favicon64 = public_path('favicon-64.png');
    $faviconIco = public_path('favicon.ico');
    $appleTouch = public_path('apple-touch-icon.png');

    $faviconVersion = max(array_filter([
        is_file($faviconSvg) ? filemtime($faviconSvg) : 0,
        is_file($favicon32) ? filemtime($favicon32) : 0,
        is_file($faviconIco) ? filemtime($faviconIco) : 0,
    ]));
@endphp
@if (is_file($faviconSvg))
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v={{ $faviconVersion }}">
@endif
@if (is_file($favicon32))
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}?v={{ $faviconVersion }}">
@endif
@if (is_file($favicon48))
    <link rel="icon" type="image/png" sizes="48x48" href="{{ asset('favicon-48.png') }}?v={{ $faviconVersion }}">
@endif
@if (is_file($favicon64))
    <link rel="icon" type="image/png" sizes="64x64" href="{{ asset('favicon-64.png') }}?v={{ $faviconVersion }}">
@endif
@if (is_file($faviconIco))
    <link rel="icon" href="{{ asset('favicon.ico') }}?v={{ $faviconVersion }}">
@endif
@if (is_file($appleTouch))
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}?v={{ $faviconVersion }}">
@endif
