<?php

namespace App\Services\Competitions;

final class ZenskoDecisionProposalCloseResult
{
    /**
     * @param  array<string, mixed>  $redirectParameters
     */
    private function __construct(
        public readonly bool $succeeded,
        public readonly ?string $error = null,
        public readonly ?int $abortStatus = null,
        public readonly ?string $abortMessage = null,
        public readonly ?string $redirectRoute = null,
        public readonly array $redirectParameters = [],
    ) {}

    public static function success(): self
    {
        return new self(true);
    }

    public static function error(string $error): self
    {
        return new self(false, $error);
    }

    public static function abort(int $status, string $message): self
    {
        return new self(false, null, $status, $message);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function redirect(string $route, array $parameters, string $error): self
    {
        return new self(false, $error, null, null, $route, $parameters);
    }
}
