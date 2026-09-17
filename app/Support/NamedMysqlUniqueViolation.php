<?php

namespace App\Support;

use Illuminate\Database\UniqueConstraintViolationException;
use Throwable;

final class NamedMysqlUniqueViolation
{
    public static function matches(Throwable $e, string $indexName): bool
    {
        $code = (int) ($e->errorInfo[1] ?? 0);
        $previous = $e->getPrevious();
        if ($code === 0 && $previous !== null) {
            $code = (int) ($previous->errorInfo[1] ?? 0);
        }

        $isDuplicate = $code === 1062 || $e instanceof UniqueConstraintViolationException;
        if (! $isDuplicate) {
            return false;
        }

        $haystack = $e->getMessage();
        if ($previous !== null) {
            $haystack .= ' '.$previous->getMessage();
        }
        if (method_exists($e, 'getSql')) {
            $haystack .= ' '.$e->getSql();
        }

        return str_contains($haystack, $indexName);
    }
}
