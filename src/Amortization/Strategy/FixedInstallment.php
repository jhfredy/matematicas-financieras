<?php

declare(strict_types=1);

namespace FinMath\Amortization\Strategy;

use FinMath\Amortization\AmortizationStrategy;
use FinMath\Amortization\Row;
use FinMath\Amortization\Schedule;
use FinMath\Annuity\Annuity;

/**
 * Cuota uniforme, con o sin cuotas extras pactadas.
 *
 * Las pactadas entran en la ecuación de valor antes de calcular la cuota:
 * ahí está la diferencia con las no pactadas.
 */
final class FixedInstallment implements AmortizationStrategy
{
    /** @param array<int, float> $scheduledExtras periodo => monto */
    public function __construct(
        private readonly float $principal,
        private readonly float $rate,
        private readonly int $periods,
        private readonly array $scheduledExtras = [],
        private readonly string $mode = Annuity::DUE
    ) {}

    public function build(): Schedule
    {
        $payment = $this->installment();
        $balance = $this->principal;
        $rows = [];

        for ($k = 1; $k <= $this->periods; $k++) {
            $interest = $balance * $this->rate;
            $extra = $this->scheduledExtras[$k] ?? 0.0;
            $total = $payment + $extra;

            if ($k === $this->periods) {
                $total = $balance + $interest;
            }

            $principal = $total - $interest;
            $balance -= $principal;

            $rows[] = new Row(
                period: $k,
                balance: $balance,
                interest: $interest,
                payment: $total,
                principal: $principal,
                note: $extra > 0 ? 'cuota extra pactada' : null
            );
        }

        return new Schedule($rows, $this->principal, $this->rate, $this->label());
    }

    public function installment(): float
    {
        $extrasPresent = 0.0;
        foreach ($this->scheduledExtras as $period => $amount) {
            $extrasPresent += $amount * (1 + $this->rate) ** -$period;
        }

        if ($extrasPresent >= $this->principal) {
            throw new \DomainException(
                'Las cuotas extras pactadas cubren todo el capital: no queda saldo para las ordinarias.'
            );
        }

        return Annuity::payment($this->principal - $extrasPresent, $this->rate, $this->periods, $this->mode);
    }

    public function label(): string
    {
        return $this->scheduledExtras === []
            ? 'Cuota uniforme'
            : 'Cuota uniforme con extras pactadas';
    }
}
