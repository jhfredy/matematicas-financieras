<?php

declare(strict_types=1);

namespace FinMath\Amortization\Strategy;

use FinMath\Amortization\AmortizationStrategy;
use FinMath\Amortization\Row;
use FinMath\Amortization\Schedule;

/**
 * Secciones 7.7 y 7.8. El abono a capital es fijo y la cuota baja periodo
 * a periodo. Con interés anticipado aparece una fila en el periodo cero,
 * donde la cuota es solo interés y la amortización es cero.
 */
final class ConstantPrincipal implements AmortizationStrategy
{
    public const DUE = 'vencido';
    public const ADVANCE = 'anticipado';

    public function __construct(
        private readonly float $principal,
        private readonly float $rate,
        private readonly int $periods,
        private readonly string $interestTiming = self::DUE
    ) {}

    public function build(): Schedule
    {
        return $this->interestTiming === self::ADVANCE
            ? $this->buildAdvance()
            : $this->buildDue();
    }

    private function buildDue(): Schedule
    {
        $amortization = $this->principal / $this->periods;
        $balance = $this->principal;
        $rows = [];

        for ($k = 1; $k <= $this->periods; $k++) {
            $interest = $balance * $this->rate;
            $balance -= $amortization;

            $rows[] = new Row($k, $balance, $interest, $amortization + $interest, $amortization);
        }

        return new Schedule($rows, $this->principal, $this->rate, $this->label());
    }

    private function buildAdvance(): Schedule
    {
        $amortization = $this->principal / $this->periods;
        $balance = $this->principal;
        $rows = [];

        // El interés del primer periodo se cobra hoy, sobre el saldo inicial
        $interest = $balance * $this->rate;
        $rows[] = new Row(0, $balance, $interest, $interest, 0.0, note: 'interés anticipado');

        for ($k = 1; $k <= $this->periods; $k++) {
            $balance -= $amortization;
            // El interés que se cobra es el del periodo siguiente
            $interest = $k < $this->periods ? $balance * $this->rate : 0.0;

            $rows[] = new Row($k, $balance, $interest, $amortization + $interest, $amortization);
        }

        return new Schedule($rows, $this->principal, $this->rate, $this->label());
    }

    public function label(): string
    {
        return "Abono constante a capital, interés {$this->interestTiming}";
    }
}
