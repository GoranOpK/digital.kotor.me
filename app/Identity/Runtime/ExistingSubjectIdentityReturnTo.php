<?php

namespace App\Identity\Runtime;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

final class ExistingSubjectIdentityReturnTo
{
    public const SESSION_KEY = 'identity_completion_intended';

    public function rememberFromRequest(Request $request): void
    {
        $candidate = '/'.$request->path();
        $query = $request->getQueryString();
        if (is_string($query) && $query !== '') {
            $candidate .= '?'.$query;
        }

        $this->remember($candidate);
    }

    public function remember(string $url): void
    {
        if ($this->isSafe($url)) {
            Session::put(self::SESSION_KEY, $url);
        }
    }

    public function consume(): string
    {
        $stored = Session::pull(self::SESSION_KEY);
        if (is_string($stored) && $this->isSafe($stored)) {
            return $stored;
        }

        return route('dashboard', absolute: false);
    }

    public function isSafe(string $url): bool
    {
        if ($url === '' || str_contains($url, '//') || str_contains($url, '\\') || str_contains($url, '@') || str_contains($url, '..')) {
            return false;
        }

        if (! str_starts_with($url, '/')) {
            return false;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '' || str_contains($path, '..')) {
            return false;
        }

        foreach (['/login', '/register', '/admin', '/evaluation'] as $denied) {
            if ($path === $denied || str_starts_with($path, $denied.'/')) {
                return false;
            }
        }

        return str_starts_with($path, '/competitions/');
    }
}
