<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Odobreni ID-jevi za naknadno označavanje
    |--------------------------------------------------------------------------
    |
    | Prazno po pravilu. Vrijednost se čita iz okruženja, van Git repozitorijuma.
    | Samo konkretni numerički ID-jevi, odvojeni zarezom. Zvjezdica, raspon
    | i riječi "svi" ili "all" ne označavaju nijedan konkurs.
    |
    */

    'mark_ids' => env('ARCHIVED_TEST_COMPETITION_MARK_IDS', ''),

];
