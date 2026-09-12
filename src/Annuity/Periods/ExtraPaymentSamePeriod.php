<?php

declare(strict_types=1);

namespace FinMath\Annuity\Periods;

use FinMath\Annuity\Annuity;

/**
 * Alternativa 3: el faltante se paga como cuota extra en el mismo
 * periodo del último pago regular, así que viene descontado un periodo.
 */
final class ExtraPaymentSamePeriod implements PeriodStrategy
{
    public function __construct(private readonly float $knownPayment) {}

    public function resolve(FractionalPeriods $periods, float $target, float $rate, Context $context): Resolution
    {
        $whole = $periods->floor;

        $accumulated = Annuity::futureValue($this->knownPayment, $rate, $whole, $context->mode);
        $future = $context->isFuture() ? $target : $target * (1 + $rate) ** $whole;
        $extra = ($future - $accumulated * (1 + $rate)) / (1 + $rate);

        $payments = [];
        for ($k = 1; $k <= $whole; $k++) {
            $payments[] = ['period' => $k, 'amount' => $this->knownPayment, 'kind' => 'regular'];
        }
        $payments[] = ['period' => $whole, 'amount' => $extra, 'kind' => 'extra'];

        return new Resolution($whole, $this->knownPayment, $payments, sprintf(
            '%d cuotas de %s más un pago extra de %s en el mismo periodo %d.',
            $whole,
            number_format($this->knownPayment, 2, ',', '.'),
            number_format($extra, 2, ',', '.'),
            $whole
        ));
    }

    public function label(): string
    {
        return 'Cuota extra en el último periodo';
    }
}
