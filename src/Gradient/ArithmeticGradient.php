<?php

declare(strict_types=1);

namespace FinMath\Gradient;

use FinMath\Annuity\Annuity;

/**
 * Capítulo 6. Serie que cambia en una cantidad fija de pesos cada periodo.
 *
 * El decreciente no tiene clase aparte: entra con G negativo. El texto lo
 * separa porque escribe el signo dentro de la fórmula, pero
 * A - (n-1)G y A + (n-1)(-G) son lo mismo.
 */
final class ArithmeticGradient
{
    public static function presentValue(
        float $base,
        float $variation,
        float $rate,
        int $periods,
        string $mode = Annuity::DUE,
        int $deferral = 0
    ): float {
        $annuityFactor = (1 - (1 + $rate) ** -$periods) / $rate;
        $gradientFactor = ($annuityFactor - $periods * (1 + $rate) ** -$periods) / $rate;

        $value = $base * $annuityFactor + $variation * $gradientFactor;

        if ($mode === Annuity::ADVANCE) {
            $value *= (1 + $rate);
        }

        return $value * (1 + $rate) ** -$deferral;
    }

    public static function futureValue(
        float $base,
        float $variation,
        float $rate,
        int $periods,
        string $mode = Annuity::DUE
    ): float {
        $annuityFactor = ((1 + $rate) ** $periods - 1) / $rate;
        $gradientFactor = ($annuityFactor - $periods) / $rate;

        $value = $base * $annuityFactor + $variation * $gradientFactor;

        return $mode === Annuity::ADVANCE ? $value * (1 + $rate) : $value;
    }

    /** Cuota n de la serie: A + (n-1)G */
    public static function payment(float $base, float $variation, int $n): float
    {
        return $base + ($n - 1) * $variation;
    }

    public static function baseFromPresent(
        float $present,
        float $variation,
        float $rate,
        int $periods,
        string $mode = Annuity::DUE,
        int $deferral = 0
    ): float {
        $withoutBase = self::presentValue(0, $variation, $rate, $periods, $mode, $deferral);
        $unitBase = self::presentValue(1, 0, $rate, $periods, $mode, $deferral);

        return ($present - $withoutBase) / $unitBase;
    }

    public static function baseFromFuture(
        float $future,
        float $variation,
        float $rate,
        int $periods,
        string $mode = Annuity::DUE
    ): float {
        $withoutBase = self::futureValue(0, $variation, $rate, $periods, $mode);
        $unitBase = self::futureValue(1, 0, $rate, $periods, $mode);

        return ($future - $withoutBase) / $unitBase;
    }

    public static function variationFromPresent(
        float $present,
        float $base,
        float $rate,
        int $periods,
        string $mode = Annuity::DUE,
        int $deferral = 0
    ): float {
        $withoutVariation = self::presentValue($base, 0, $rate, $periods, $mode, $deferral);
        $unitVariation = self::presentValue(0, 1, $rate, $periods, $mode, $deferral);

        return ($present - $withoutVariation) / $unitVariation;
    }

    /** Perpetua creciente: A/i + G/i² */
    public static function perpetuity(float $base, float $variation, float $rate): float
    {
        if ($rate <= 0) {
            throw new \DomainException('Una perpetuidad exige tasa positiva.');
        }

        return $base / $rate + $variation / ($rate ** 2);
    }

    /** Saldo después de cancelar la cuota k */
    public static function balance(
        float $present,
        float $base,
        float $variation,
        float $rate,
        int $paid,
        string $mode = Annuity::DUE
    ): float {
        return $present * (1 + $rate) ** $paid
            - self::futureValue($base, $variation, $rate, $paid, $mode);
    }

    /** Periodo en que la serie decreciente cruza cero */
    public static function crossesZeroAt(float $base, float $variation): ?int
    {
        if ($variation >= 0) {
            return null;
        }

        $n = (int) ceil($base / abs($variation)) + 1;

        return $n > 0 ? $n : null;
    }
}
