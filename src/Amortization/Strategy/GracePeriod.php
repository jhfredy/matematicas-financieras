<?php

declare(strict_types=1);

namespace FinMath\Amortization\Strategy;

use FinMath\Amortization\AmortizationStrategy;
use FinMath\Amortization\Row;
use FinMath\Amortization\Schedule;
use FinMath\Annuity\Annuity;

/**
 * Sección 7.5.
 *
 * Gracia muerta: no se paga nada y los intereses se capitalizan, así que
 * la deuda crece. Gracia con cuota reducida: se pagan los intereses y el
 * capital queda quieto.
 */
final class GracePeriod implements AmortizationStrategy
{
    public const DEAD = 'muerto';
    public const INTEREST_ONLY = 'reducida';

    public function __construct(
        private readonly float $principal,
        private readonly float $rate,
        private readonly int $totalPeriods,
        private readonly int $gracePeriods,
        private readonly string $type = self::DEAD
    ) {
        if ($gracePeriods >= $totalPeriods) {
            throw new \DomainException('El periodo de gracia debe ser menor que el plazo total.');
        }
    }

    public function build(): Schedule
    {
        $balance = $this->principal;
        $rows = [];

        for ($k = 1; $k <= $this->gracePeriods; $k++) {
            $interest = $balance * $this->rate;

            if ($this->type === self::DEAD) {
                $balance += $interest;
                $rows[] = new Row($k, $balance, $interest, 0.0, -$interest, note: 'gracia muerta');
            } else {
                $rows[] = new Row($k, $balance, $interest, $interest, 0.0, note: 'gracia, solo intereses');
            }
        }

        $amortizing = $this->totalPeriods - $this->gracePeriods;
        $payment = Annuity::payment($balance, $this->rate, $amortizing);

        for ($k = $this->gracePeriods + 1; $k <= $this->totalPeriods; $k++) {
            $interest = $balance * $this->rate;
            $total = $k === $this->totalPeriods ? $balance + $interest : $payment;
            $principal = $total - $interest;
            $balance -= $principal;

            $rows[] = new Row($k, $balance, $interest, $total, $principal);
        }

        return new Schedule($rows, $this->principal, $this->rate, $this->label());
    }

    public function label(): string
    {
        return $this->type === self::DEAD
            ? 'Periodo de gracia muerto'
            : 'Periodo de gracia con cuota reducida';
    }
}
