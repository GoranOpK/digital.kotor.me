<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Javni portal — privremeni read source (Faza 6A / PO-TS9-08I)
    |--------------------------------------------------------------------------
    |
    | XOR: legacy ILI canonical. Nikada dual-read / merge / fallback.
    | Default i fail-safe: legacy (pogrešna vrijednost ne aktivira canonical).
    |
    */

    'public_read_source' => env('CULTURAL_PUBLIC_READ_SOURCE', 'legacy'),

];
