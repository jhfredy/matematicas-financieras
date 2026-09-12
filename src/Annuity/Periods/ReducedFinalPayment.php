<?php

declare(strict_types=1);

namespace FinMath\Annuity\Periods;

use FinMath\Annuity\Annuity;

/**
 * Alternativa 2: se mantiene la cuota conocida y el faltante se paga
 * como cuota menor en el periodo siguiente al último completo.
 */
final class ReducedFinalPayment implements PeriodStrategy
{
    public function __construct(private readonly float $knownPayment) {}

    public function resolve(FractionalPeriods $periods, float $target, float $rate, Context $context): Resolution
    {
        $whole = $periods->floor;
        $last = $whole + 1;

        $accumulated = Annuity::futureValue($this->knownPayment, $rate, $whole, $context->mode) * (1 + $rate);
        $future = $context->isFuture() ? $target : $target * (1 + $rate) ** $last;
        $residual = $future - $accumulated;

        $payments = [];
        for ($k = 1; $k <= $whole; $k++) {
            $payments[] = ['period' => $k, 'amount' => $this->knownPayment, 'kind' => 'regular'];
        }
        $payments[] = ['period' => $last, 'amount' => $residual, 'kind' => 'final'];

        return new Resolution($last, $this->knownPayment, $payments, sprintf(
            '%d cuotas de %s y un pago menor de %s en el periodo %d.',
            $whole,
            number_format($this->knownPayment, 2, ',', '.'),
            number_format($residual, 2, ',', '.'),
            $last
        ));
    }

    public function label(): string
    {
        return 'Cuota reducida al final';
    }
}
