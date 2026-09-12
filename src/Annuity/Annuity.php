<?php

declare(strict_types=1);

namespace FinMath\Annuity;

use FinMath\Solver\Bisection;
use FinMath\Solver\IterationTrace;

/** Capítulo 5. Series uniformes vencidas, anticipadas, diferidas y perpetuas. */
final class Annuity
{
    public const DUE = 'vencida';
    public const ADVANCE = 'anticipada';

    /** Factor valor presente serie uniforme */
    public static function presentFactor(float $rate, int $periods, string $mode = self::DUE): float
    {
        $factor = (1 - (1 + $rate) ** -$periods) / $rate;

        return $mode === self::ADVANCE ? $factor * (1 + $rate) : $factor;
    }

    /** Factor cantidad compuesta serie uniforme */
    public static function futureFactor(float $rate, int $periods, string $mode = self::DUE): float
    {
        $factor = ((1 + $rate) ** $periods - 1) / $rate;

        return $mode === self::ADVANCE ? $factor * (1 + $rate) : $factor;
    }

    /** $deferral son los periodos de gracia antes del primer pago */
    public static function presentValue(
        float $payment,
        float $rate,
        int $periods,
        string $mode = self::DUE,
        int $deferral = 0
    ): float {
        return $payment * self::presentFactor($rate, $periods, $mode) * (1 + $rate) ** -$deferral;
    }

    public static function futureValue(
        float $payment,
        float $rate,
        int $periods,
        string $mode = self::DUE
    ): float {
        return $payment * self::futureFactor($rate, $periods, $mode);
    }

    /** Factor de recuperación de capital */
    public static function payment(
        float $present,
        float $rate,
        int $periods,
        string $mode = self::DUE,
        int $deferral = 0
    ): float {
        return $present / (self::presentFactor($rate, $periods, $mode) * (1 + $rate) ** -$deferral);
    }

    /** Factor fondo de amortización */
    public static function paymentFromFuture(
        float $future,
        float $rate,
        int $periods,
        string $mode = self::DUE
    ): float {
        return $future / self::futureFactor($rate, $periods, $mode);
    }

    /** Perpetua: P = A / i. La anticipada añade el primer pago completo. */
    public static function perpetuity(float $payment, float $rate, string $mode = self::DUE): float
    {
        if ($rate <= 0) {
            throw new \DomainException('Una perpetuidad exige tasa positiva.');
        }

        return $mode === self::DUE ? $payment / $rate : ($payment / $rate) * (1 + $rate);
    }

    /** (5.5) n en función del valor presente */
    public static function periodsFromPresent(
        float $present,
        float $payment,
        float $rate,
        string $mode = self::DUE
    ): float {
        $base = $mode === self::ADVANCE ? $payment * (1 + $rate) : $payment;
        $ratio = 1 - ($present * $rate) / $base;

        if ($ratio <= 0) {
            throw new \DomainException(
                'La cuota no alcanza a cubrir los intereses: la deuda no se amortiza nunca.'
            );
        }

        return -log($ratio) / log(1 + $rate);
    }

    /** (5.6) n en función del valor futuro */
    public static function periodsFromFuture(
        float $future,
        float $payment,
        float $rate,
        string $mode = self::DUE
    ): float {
        $base = $mode === self::ADVANCE ? $payment * (1 + $rate) : $payment;

        return log(1 + ($future * $rate) / $base) / log(1 + $rate);
    }

    /** Tasa por bisección, con la traza para mostrar la interpolación del texto */
    public static function rateFromPresent(
        float $present,
        float $payment,
        int $periods,
        string $mode = self::DUE,
        ?float $lo = null,
        ?float $hi = null
    ): IterationTrace {
        return (new Bisection)->solve(
            fn (float $i) => self::presentValue($payment, $i, $periods, $mode) - $present,
            $lo,
            $hi
        );
    }

    public static function rateFromFuture(
        float $future,
        float $payment,
        int $periods,
        string $mode = self::DUE,
        ?float $lo = null,
        ?float $hi = null
    ): IterationTrace {
        return (new Bisection)->solve(
            fn (float $i) => self::futureValue($payment, $i, $periods, $mode) - $future,
            $lo,
            $hi
        );
    }
}
