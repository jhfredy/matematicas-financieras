<?php

declare(strict_types=1);

namespace FinMath\Amortization\Strategy;

use FinMath\Amortization\AmortizationStrategy;
use FinMath\Amortization\Row;
use FinMath\Amortization\Schedule;
use FinMath\Gradient\ArithmeticGradient;
use FinMath\Gradient\GeometricGradient;

/**
 * Cuotas que crecen o decrecen, con periodo de gracia opcional.
 *
 * La variación entra con signo: negativa para decreciente. Si el
 * decremento es grande frente al plazo, las últimas cuotas salen
 * negativas; la tabla lo deja visible en vez de ocultarlo.
 */
final class GradientInstallment implements AmortizationStrategy
{
    public const ARITHMETIC = 'aritmetica';
    public const GEOMETRIC = 'geometrica';

    public function __construct(
        private readonly float $principal,
        private readonly float $rate,
        private readonly int $periods,
        private readonly float $variation,
        private readonly string $kind = self::ARITHMETIC,
        private readonly int $gracePeriods = 0,
        private readonly bool $deadGrace = true
    ) {}

    public function build(): Schedule
    {
        $balance = $this->principal;
        $rows = [];

        for ($k = 1; $k <= $this->gracePeriods; $k++) {
            $interest = $balance * $this->rate;

            if ($this->deadGrace) {
                $balance += $interest;
                $rows[] = new Row($k, $balance, $interest, 0.0, -$interest, note: 'gracia muerta');
            } else {
                $rows[] = new Row($k, $balance, $interest, $interest, 0.0, note: 'gracia, solo intereses');
            }
        }

        $amortizing = $this->periods - $this->gracePeriods;
        $base = $this->firstPayment($balance, $amortizing);

        for ($n = 1; $n <= $amortizing; $n++) {
            $payment = $this->kind === self::ARITHMETIC
                ? ArithmeticGradient::payment($base, $this->variation, $n)
                : GeometricGradient::payment($base, $this->variation, $n);

            $interest = $balance * $this->rate;

            if ($n === $amortizing) {
                $payment = $balance + $interest;
            }

            $principal = $payment - $interest;
            $balance -= $principal;

            $rows[] = new Row(
                period: $this->gracePeriods + $n,
                balance: $balance,
                interest: $interest,
                payment: $payment,
                principal: $principal,
                note: $payment < 0 ? 'cuota negativa: variación excesiva' : null
            );
        }

        return new Schedule($rows, $this->principal, $this->rate, $this->label());
    }

    public function firstPayment(float $balance, int $amortizing): float
    {
        return $this->kind === self::ARITHMETIC
            ? ArithmeticGradient::baseFromPresent($balance, $this->variation, $this->rate, $amortizing)
            : GeometricGradient::firstPaymentFromPresent($balance, $this->variation, $this->rate, $amortizing);
    }

    public function label(): string
    {
        $kind = $this->kind === self::ARITHMETIC ? 'aritmético' : 'geométrico';
        $direction = $this->variation < 0 ? 'decreciente' : 'creciente';

        return "Cuotas con gradiente {$kind} {$direction}";
    }
}
