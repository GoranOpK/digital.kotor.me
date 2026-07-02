<?php

namespace App\Support;

class Pib
{
    public const LENGTH = 8;

    public const REGEX = '/^[0-9]{8}$/';

    public const VALIDATION_MESSAGE = 'PIB mora imati tačno 8 cifara.';
}
