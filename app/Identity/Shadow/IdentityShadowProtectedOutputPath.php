<?php

namespace App\Identity\Shadow;

use InvalidArgumentException;

/**
 * Resolves Step 6 shadow report paths under a non-public base directory.
 */
final class IdentityShadowProtectedOutputPath
{
    public static function baseDirectory(): string
    {
        return storage_path('app/private/identity-shadow');
    }

    public static function ensureBaseDirectory(): string
    {
        $base = self::baseDirectory();
        if (! is_dir($base) && ! mkdir($base, 0770, true) && ! is_dir($base)) {
            throw new InvalidArgumentException('Unable to prepare protected shadow output directory.');
        }

        $resolved = realpath($base);
        if ($resolved === false) {
            throw new InvalidArgumentException('Unable to resolve protected shadow output directory.');
        }

        return $resolved;
    }

    public static function resolve(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            throw new InvalidArgumentException('Shadow output path is required.');
        }

        $base = self::ensureBaseDirectory();
        $absolute = self::lexicalNormalize(self::toAbsolute($path));
        $resolved = self::resolveExistingPrefix($absolute);

        if (! self::isInside($resolved, $base)) {
            throw new InvalidArgumentException('Shadow output path must be inside the protected shadow directory.');
        }

        foreach (self::forbiddenRoots() as $forbidden) {
            if ($forbidden !== null && self::isInside($resolved, $forbidden)) {
                throw new InvalidArgumentException('Shadow output path must not be web-accessible.');
            }
        }

        return $resolved;
    }

    private static function toAbsolute(string $path): string
    {
        if (self::isAbsolute($path)) {
            return $path;
        }

        return base_path($path);
    }

    private static function isAbsolute(string $path): bool
    {
        if (str_starts_with($path, '/') || str_starts_with($path, '\\')) {
            return true;
        }

        return (bool) preg_match('/^[A-Za-z]:[\\\\\\/]/', $path);
    }

    private static function lexicalNormalize(string $path): string
    {
        $unix = str_replace('\\', '/', $path);
        $drive = '';
        if (preg_match('/^([A-Za-z]:)(\\/.*)$/', $unix, $matches) === 1) {
            $drive = $matches[1];
            $unix = $matches[2];
        }

        $leadingSlash = str_starts_with($unix, '/');
        $parts = [];
        foreach (explode('/', $unix) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($parts);

                continue;
            }
            $parts[] = $segment;
        }

        $normalized = ($leadingSlash ? '/' : '').implode('/', $parts);
        if ($drive !== '') {
            $normalized = $drive.$normalized;
        }

        return str_replace('/', DIRECTORY_SEPARATOR, $normalized);
    }

    private static function resolveExistingPrefix(string $absolute): string
    {
        $unix = str_replace('\\', '/', $absolute);
        $cursor = $unix;
        while ($cursor !== '' && $cursor !== '/' && ! preg_match('/^[A-Za-z]:$/', $cursor)) {
            $candidate = str_replace('/', DIRECTORY_SEPARATOR, $cursor);
            if (file_exists($candidate)) {
                $real = realpath($candidate);
                if ($real === false) {
                    break;
                }
                $suffix = substr($unix, strlen($cursor));
                $combined = rtrim(str_replace('\\', '/', $real), '/').$suffix;

                return str_replace('/', DIRECTORY_SEPARATOR, $combined);
            }
            $parent = dirname($cursor);
            if ($parent === $cursor) {
                break;
            }
            $cursor = $parent;
        }

        return $absolute;
    }

    private static function isInside(string $path, string $base): bool
    {
        $path = rtrim(str_replace('\\', '/', $path), '/');
        $base = rtrim(str_replace('\\', '/', $base), '/');
        $pathCmp = self::fold($path);
        $baseCmp = self::fold($base);

        return $pathCmp === $baseCmp || str_starts_with($pathCmp, $baseCmp.'/');
    }

    private static function fold(string $path): string
    {
        return DIRECTORY_SEPARATOR === '\\' ? strtolower($path) : $path;
    }

    /**
     * @return list<string|null>
     */
    private static function forbiddenRoots(): array
    {
        $public = realpath(public_path()) ?: null;
        $publicStorage = realpath(storage_path('app/public')) ?: null;

        return [$public, $publicStorage];
    }
}
