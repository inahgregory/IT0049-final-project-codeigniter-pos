<?php
namespace App\Libraries;
class Money
{
    public const MAX_CENTS = 9999999999;
    public static function cents(string $amount): int
    {
        if (! preg_match('/^([0-9]{1,8})(?:\.([0-9]{1,2}))?$/D', $amount, $parts)) {
            throw new \DomainException('Enter a valid price with at most two decimal places.');
        }
        return ((int) $parts[1] * 100) + (int) str_pad($parts[2] ?? '', 2, '0');
    }
    public static function decimal(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
