<?php

namespace App\Security;

use RuntimeException;

final class JmbEncryptedReadException extends RuntimeException
{
    public const USER_MESSAGE = 'Identitet nije validan i mora se ispraviti (D14).';
}
