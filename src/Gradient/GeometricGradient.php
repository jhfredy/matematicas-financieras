<?php

declare(strict_types=1);

namespace FinMath\Gradient;

use FinMath\Annuity\Annuity;

/**
 * Serie que cambia en un porcentaje fijo cada periodo.
 *
 * Ojo con el signo de j: acá entra con signo, de modo que un decreciente
 * del 1,8% es j = -0.018 y el denominador (i - j) se vuelve (i + j) solo.
 * El texto escribe dos fórmulas distintas; esta es una sola.
 */
final class GeometricGradient
{
    private const EPSILON = 1e-12;

    public static function presentValue(
        float $first,
        float $growth,
        float $rate,
        int $periods,
        string $mode = Annuity::DUE,
        int $deferral = 0
    ): float {
        $value = abs($rate - $growth) < self::EPSILON
            ? $periods * $first / (1 + $rate)
            : $first * (1 - ((1 + $growth) / (1 + $rate)) ** $periods) / ($rate - $growth);

        if ($mode === Annuity::ADVANCE) {
            $value *= (1 + $rate);
        }

        return $value * (1 + $rate) ** -$deferral;
    }

    public static function futureValue(
        float $first,
        float $growth,
        float $rate,
        int $periods,
        string $mode = Annuity::DUE
    ): float {
        $value = abs($rate - $growth) < self::EPSILON
            ? $periods * $first * (1 + $rate) ** ($periods - 1)
            : $first * ((1 + $rate) ** $periods - (1 + $growth) ** $periods) / ($rate - $growth);

        return $mode === Annuity::ADVANCE ? $value * (1 + $rate) : $value;
    }

    /** Cuota n: k(1+j)^(n-1) */
    public static function payment(float $first, float $growth, int $n): float
    {
        return $first * (1 + $growth) ** ($n - 1);
    }

    public static function firstPaymentFromPresent(
        float $present,
        float $growth,
        float $rate,
        int $periods,
        string $mode = Annuity::DUE,
        int $deferral = 0
    ): float {
        return $present / self::presentValue(1, $growth, $rate, $periods, $mode, $deferral);
    }

    public static function firstPaymentFromFuture(
        float $future,
        float $growth,
        float $rate,
        int $periods,
        string $mode = Annuity::DUE
    ): float {
        return $future / self::futureValue(1, $growth, $rate, $periods, $mode);
    }

    /** Perpetua creciente; sin i > j la serie no converge */
    public static function perpetuity(float $first, float $growth, float $rate): float
    {
        if ($rate <= $growth) {
            throw new \DomainException(
                'La perpetuidad geométrica exige que la tasa de interés supere la de crecimiento.'
            );
        }

        return $first / ($rate - $growth);
    }

    public static function balance(
        float $present,
        float $first,
        float $growth,
        float $rate,
        int $paid,
        string $mode = Annuity::DUE
    ): float {
        return $present * (1 + $rate) ** $paid
            - self::futureValue($first, $growth, $rate, $paid, $mode);
    }
}
