<?php

declare(strict_types=1);

namespace FinMath\Annuity;

use FinMath\Annuity\Periods\Context;
use FinMath\Annuity\Periods\ExtraPaymentSamePeriod;
use FinMath\Annuity\Periods\FractionalPeriods;
use FinMath\Annuity\Periods\ReducedFinalPayment;
use FinMath\Annuity\Periods\Resolution;
use FinMath\Annuity\Periods\RoundedPeriods;

/**
 * Cuando n no es entero la serie deja de ser propia. El texto plantea
 * tres salidas; este resolver las corre todas para poder compararlas.
 */
final class PeriodResolver
{
    /** @return array<string, Resolution> */
    public function all(float $target, float $payment, float $rate, Context $context): array
    {
        $periods = FractionalPeriods::from(
            $context->isFuture()
                ? Annuity::periodsFromFuture($target, $payment, $rate, $context->mode)
                : Annuity::periodsFromPresent($target, $payment, $rate, $context->mode)
        );

        if ($periods->isWhole()) {
            $strategy = new RoundedPeriods(RoundedPeriods::DOWN);

            return ['Periodos exactos' => $strategy->resolve($periods, $target, $rate, $context)];
        }

        $strategies = [
            new RoundedPeriods(RoundedPeriods::DOWN),
            new RoundedPeriods(RoundedPeriods::UP),
            new ReducedFinalPayment($payment),
            new ExtraPaymentSamePeriod($payment),
        ];

        $out = [];
        foreach ($strategies as $strategy) {
            $out[$strategy->label()] = $strategy->resolve($periods, $target, $rate, $context);
        }

        return $out;
    }

    public function exactPeriods(float $target, float $payment, float $rate, Context $context): FractionalPeriods
    {
        return FractionalPeriods::from(
            $context->isFuture()
                ? Annuity::periodsFromFuture($target, $payment, $rate, $context->mode)
                : Annuity::periodsFromPresent($target, $payment, $rate, $context->mode)
        );
    }
}
