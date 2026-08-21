<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Processing = 'processing';
    case Successful = 'successful';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Processing => 'U obradi',
            self::Successful => 'Uspješna',
            self::Failed => 'Neuspješna',
            self::Cancelled => 'Otkazana',
        };
    }

    public function isTerminal(): bool
    {
        return $this !== self::Processing;
    }

    /**
     * @return list<self>
     */
    public static function casesInBusinessOrder(): array
    {
        return [
            self::Processing,
            self::Successful,
            self::Failed,
            self::Cancelled,
        ];
    }
}
