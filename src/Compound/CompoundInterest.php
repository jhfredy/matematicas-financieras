<?php

declare(strict_types=1);

namespace FinMath\Compound;

/** Capítulo 3. Aquí sí hay capitalización de intereses. */
final class CompoundInterest
{
    /** F = P (1 + i)^n */
    public static function futureValue(float $present, float $rate, float $periods): float
    {
        return $present * (1 + $rate) ** $periods;
    }

    /** P = F (1 + i)^-n */
    public static function presentValue(float $future, float $rate, float $periods): float
    {
        return $future * (1 + $rate) ** -$periods;
    }

    /** i = (F/P)^(1/n) - 1 */
    public static function rate(float $present, float $future, float $periods): float
    {
        return ($future / $present) ** (1 / $periods) - 1;
    }

    /** n = ln(F/P) / ln(1+i) */
    public static function periods(float $present, float $future, float $rate): float
    {
        return log($future / $present) / log(1 + $rate);
    }

    public static function interest(float $present, float $rate, float $periods): float
    {
        return self::futureValue($present, $rate, $periods) - $present;
    }

    /** F = P e^(rn), interés continuo */
    public static function futureValueContinuous(float $present, float $nominalRate, float $periods): float
    {
        return $present * exp($nominalRate * $periods);
    }

    public static function presentValueContinuous(float $future, float $nominalRate, float $periods): float
    {
        return $future * exp(-$nominalRate * $periods);
    }

    /** Dc = Vn [1 - (1+d)^-n], descuento compuesto (3.6) */
    public static function compoundDiscount(float $nominal, float $discountRate, float $periods): float
    {
        return $nominal * (1 - (1 + $discountRate) ** -$periods);
    }
}
