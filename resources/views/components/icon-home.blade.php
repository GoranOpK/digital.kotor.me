@props([
    'class' => 'w-6 h-6',
])

<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" {{ $attributes->merge(['class' => $class]) }} aria-hidden="true">
    <path d="M3 10.5 12 3l9 7.5" />
    <path d="M5 10.5V20h14v-9.5" />
    <path d="M10 20v-5h4v5" />
</svg>
