<?php

declare(strict_types=1);

namespace FinMath\Equation;

use FinMath\Gradient\ArithmeticGradient;
use FinMath\Gradient\GeometricGradient;

/**
 * Conjunto de flujos del diagrama. Las series se expanden a flujos
 * individuales, así la ecuación no necesita saber qué es una anualidad.
 */
final class FlowSet
{
    /** @param list<CashFlow> $flows */
    private function __construct(private array $flows = []) {}

    public static function make(): self
    {
        return new self;
    }

    public function add(CashFlow ...$flows): self
    {
        $clone = clone $this;
        $clone->flows = [...$this->flows, ...$flows];

        return $clone;
    }

    public function annuity(
        float $payment,
        float $firstPeriod,
        int $count,
        string $direction = CashFlow::OUTFLOW,
        float $step = 1.0
    ): self {
        $flows = [];

        for ($k = 0; $k < $count; $k++) {
            $period = $firstPeriod + $k * $step;
            $label = sprintf('cuota %d', $k + 1);

            $flows[] = $direction === CashFlow::INFLOW
                ? CashFlow::inflow($payment, $period, $label)
                : CashFlow::outflow($payment, $period, $label);
        }

        return $this->add(...$flows);
    }

    public function arithmeticGradient(
        float $base,
        float $variation,
        float $firstPeriod,
        int $count,
        string $direction = CashFlow::OUTFLOW,
        float $step = 1.0
    ): self {
        $flows = [];

        for ($n = 1; $n <= $count; $n++) {
            $amount = ArithmeticGradient::payment($base, $variation, $n);
            $period = $firstPeriod + ($n - 1) * $step;
            $label = sprintf('gradiente %d', $n);

            $flows[] = $direction === CashFlow::INFLOW
                ? CashFlow::inflow($amount, $period, $label)
                : CashFlow::outflow($amount, $period, $label);
        }

        return $this->add(...$flows);
    }

    public function geometricGradient(
        float $first,
        float $growth,
        float $firstPeriod,
        int $count,
        string $direction = CashFlow::OUTFLOW,
        float $step = 1.0
    ): self {
        $flows = [];

        for ($n = 1; $n <= $count; $n++) {
            $amount = GeometricGradient::payment($first, $growth, $n);
            $period = $firstPeriod + ($n - 1) * $step;
            $label = sprintf('geométrico %d', $n);

            $flows[] = $direction === CashFlow::INFLOW
                ? CashFlow::inflow($amount, $period, $label)
                : CashFlow::outflow($amount, $period, $label);
        }

        return $this->add(...$flows);
    }

    /** N cuotas iguales de X: el caso más común de refinanciación */
    public function unknownAnnuity(
        float $firstPeriod,
        int $count,
        string $direction = CashFlow::OUTFLOW,
        float $step = 1.0,
        float $constant = 0.0
    ): self {
        $flows = [];

        for ($k = 0; $k < $count; $k++) {
            $period = $firstPeriod + $k * $step;
            $label = sprintf('cuota X %d', $k + 1);

            $flows[] = $direction === CashFlow::INFLOW
                ? CashFlow::unknownInflow(1.0, $constant, $period, $label)
                : CashFlow::unknownOutflow(1.0, $constant, $period, $label);
        }

        return $this->add(...$flows);
    }

    /** X, X+G, X+2G... la variación va al término constante */
    public function unknownArithmeticGradient(
        float $variation,
        float $firstPeriod,
        int $count,
        string $direction = CashFlow::OUTFLOW,
        float $step = 1.0
    ): self {
        $flows = [];

        for ($n = 1; $n <= $count; $n++) {
            $period = $firstPeriod + ($n - 1) * $step;
            $constant = ($n - 1) * $variation;
            $label = sprintf('gradiente X %d', $n);

            $flows[] = $direction === CashFlow::INFLOW
                ? CashFlow::unknownInflow(1.0, $constant, $period, $label)
                : CashFlow::unknownOutflow(1.0, $constant, $period, $label);
        }

        return $this->add(...$flows);
    }

    /** X, X(1+j), X(1+j)²... acá el crecimiento va al coeficiente */
    public function unknownGeometricGradient(
        float $growth,
        float $firstPeriod,
        int $count,
        string $direction = CashFlow::OUTFLOW,
        float $step = 1.0
    ): self {
        $flows = [];

        for ($n = 1; $n <= $count; $n++) {
            $period = $firstPeriod + ($n - 1) * $step;
            $coefficient = (1 + $growth) ** ($n - 1);
            $label = sprintf('geométrico X %d', $n);

            $flows[] = $direction === CashFlow::INFLOW
                ? CashFlow::unknownInflow($coefficient, 0.0, $period, $label)
                : CashFlow::unknownOutflow($coefficient, 0.0, $period, $label);
        }

        return $this->add(...$flows);
    }

    /** @return list<CashFlow> */
    public function all(): array
    {
        return $this->flows;
    }

    public function hasUnknowns(): bool
    {
        foreach ($this->flows as $flow) {
            if ($flow->isUnknown()) {
                return true;
            }
        }

        return false;
    }

    public function lastPeriod(): float
    {
        if ($this->flows === []) {
            return 0.0;
        }

        return max(array_map(fn (CashFlow $flow) => $flow->period, $this->flows));
    }

    public function count(): int
    {
        return count($this->flows);
    }
}
