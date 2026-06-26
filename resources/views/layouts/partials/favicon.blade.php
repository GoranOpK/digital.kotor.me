@php
    $favicon32 = public_path('favicon-32.png');
    $favicon48 = public_path('favicon-48.png');
    $faviconIco = public_path('favicon.ico');

    if (is_file($favicon32)) {
        $faviconVersion = max(
            filemtime($favicon32),
            is_file($faviconIco) ? filemtime($faviconIco) : 0
        );
    } elseif (is_file(public_path('favicon.svg'))) {
        $faviconVersion = filemtime(public_path('favicon.svg'));
    } else {
        $faviconVersion = time();
    }
@endphp
@if (is_file($favicon32))
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}?v={{ $faviconVersion }}">
@endif
@if (is_file($favicon48))
    <link rel="icon" type="image/png" sizes="48x48" href="{{ asset('favicon-48.png') }}?v={{ $faviconVersion }}">
@endif
@if (is_file($faviconIco))
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v={{ $faviconVersion }}">
@endif
@if (is_file($favicon48))
    <link rel="apple-touch-icon" href="{{ asset('favicon-48.png') }}?v={{ $faviconVersion }}">
@endif
