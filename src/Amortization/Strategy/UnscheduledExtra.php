<?php

declare(strict_types=1);

namespace FinMath\Amortization\Strategy;

use FinMath\Amortization\AmortizationStrategy;
use FinMath\Amortization\Row;
use FinMath\Amortization\Schedule;
use FinMath\Annuity\Annuity;

/**
 * Abono extra no pactado. Dos salidas: reliquidar la cuota conservando
 * el plazo, o conservar la cuota y acortar el plazo.
 */
final class UnscheduledExtra implements AmortizationStrategy
{
    public const RECALCULATE_PAYMENT = 'reliquidar';
    public const SHORTEN_TERM = 'acortar';

    public function __construct(
        private readonly float $principal,
        private readonly float $rate,
        private readonly int $periods,
        private readonly int $extraPeriod,
        private readonly float $extraAmount,
        private readonly string $behaviour = self::RECALCULATE_PAYMENT
    ) {}

    public function build(): Schedule
    {
        $payment = Annuity::payment($this->principal, $this->rate, $this->periods);
        $balance = $this->principal;
        $rows = [];

        for ($k = 1; $k <= $this->periods; $k++) {
            $interest = $balance * $this->rate;
            $total = $payment;
            $note = null;

            if ($k === $this->extraPeriod) {
                $total += $this->extraAmount;
                $note = 'abono extra no pactado';
            }

            // Al acortar plazo, la cuota que alcanza a cubrir el saldo lo liquida
            if ($balance + $interest <= $total) {
                $total = $balance + $interest;
                $note = $note === null ? 'pago final' : $note . ' y pago final';
            }

            $principal = $total - $interest;
            $balance -= $principal;

            if ($k === $this->extraPeriod && $this->behaviour === self::RECALCULATE_PAYMENT) {
                $note = 'abono extra, cuota reliquidada';
            }

            $rows[] = new Row(
                period: $k,
                balance: $balance,
                interest: $interest,
                payment: $total,
                principal: $principal,
                note: $note
            );

            if (abs($balance) < 0.005) {
                break;
            }

            $remaining = $this->periods - $k;

            if ($k === $this->extraPeriod
                && $this->behaviour === self::RECALCULATE_PAYMENT
                && $remaining > 0) {
                $payment = Annuity::payment($balance, $this->rate, $remaining);
            }
        }

        return new Schedule($rows, $this->principal, $this->rate, $this->label());
    }

    public function label(): string
    {
        return $this->behaviour === self::RECALCULATE_PAYMENT
            ? 'Abono extra no pactado, plazo constante'
            : 'Abono extra no pactado, plazo reducido';
    }
}
