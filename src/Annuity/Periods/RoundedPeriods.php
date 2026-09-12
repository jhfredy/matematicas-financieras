<?php

declare(strict_types=1);

namespace FinMath\Annuity\Periods;

use FinMath\Annuity\Annuity;

/** Alternativa 1 del texto: redondear n y recalcular la cuota. */
final class RoundedPeriods implements PeriodStrategy
{
    public const DOWN = 'anterior';
    public const UP = 'posterior';

    public function __construct(private readonly string $direction = self::DOWN) {}

    public function resolve(FractionalPeriods $periods, float $target, float $rate, Context $context): Resolution
    {
        $n = $this->direction === self::DOWN ? $periods->floor : $periods->ceil;

        if ($n < 1) {
            throw new \DomainException('El redondeo deja la serie sin pagos.');
        }

        $payment = $context->isFuture()
            ? Annuity::paymentFromFuture($target, $rate, $n, $context->mode)
            : Annuity::payment($target, $rate, $n, $context->mode);

        $payments = [];
        for ($k = 1; $k <= $n; $k++) {
            $payments[] = ['period' => $k, 'amount' => $payment, 'kind' => 'regular'];
        }

        return new Resolution($n, $payment, $payments, sprintf(
            'Se redondea n = %.4f al entero %s (%d) y se recalcula la cuota en %s.',
            $periods->exact,
            $this->direction,
            $n,
            number_format($payment, 2, ',', '.')
        ));
    }

    public function label(): string
    {
        return "Redondeo al entero {$this->direction}";
    }
}
