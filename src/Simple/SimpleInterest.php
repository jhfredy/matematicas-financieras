<?php

declare(strict_types=1);

namespace FinMath\Simple;

/** Capítulo 2. El interés simple no capitaliza: la base queda fija. */
final class SimpleInterest
{
    public const COMMERCIAL_YEAR = 360;
    public const EXACT_YEAR = 365;

    /** I = P i n */
    public static function interest(float $present, float $rate, float $periods): float
    {
        return $present * $rate * $periods;
    }

    /** F = P (1 + i n) */
    public static function futureValue(float $present, float $rate, float $periods): float
    {
        return $present * (1 + $rate * $periods);
    }

    /** P = F / (1 + i n) */
    public static function presentValue(float $future, float $rate, float $periods): float
    {
        return $future / (1 + $rate * $periods);
    }

    public static function rate(float $present, float $future, float $periods): float
    {
        return ($future / $present - 1) / $periods;
    }

    public static function periods(float $present, float $future, float $rate): float
    {
        return ($future / $present - 1) / $rate;
    }

    /**
     * Interés por días. $yearBase 360 da el comercial u ordinario;
     * 365 da el real o exacto. El texto los llama bancario y racional.
     */
    public static function interestByDays(
        float $present,
        float $annualRate,
        int $days,
        int $yearBase = self::COMMERCIAL_YEAR
    ): float {
        return $present * $annualRate * $days / $yearBase;
    }

    /** Días entre dos fechas; base 360 usa meses de 30 días */
    public static function daysBetween(
        \DateTimeInterface $from,
        \DateTimeInterface $to,
        int $yearBase = self::EXACT_YEAR
    ): int {
        if ($yearBase === self::EXACT_YEAR) {
            return (int) $from->diff($to)->days;
        }

        $years = (int) $to->format('Y') - (int) $from->format('Y');
        $months = (int) $to->format('n') - (int) $from->format('n');
        $days = (int) $to->format('j') - (int) $from->format('j');

        return $years * 360 + $months * 30 + $days;
    }
}
