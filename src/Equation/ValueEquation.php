<?php

declare(strict_types=1);

namespace FinMath\Equation;

use FinMath\Solver\Bisection;
use FinMath\Solver\IterationTrace;

/**
 * Ecuación de valor: el teorema fundamental de las matemáticas financieras.
 *
 * Todo lo demás del proyecto es un caso particular de igualar flujos en una
 * fecha focal. Con interés compuesto el resultado no depende de la fecha
 * elegida, y focalDateInvariance() sirve justamente para comprobarlo.
 */
final class ValueEquation
{
    /** Tope de la búsqueda de cota al despejar n: 100 años mensuales */
    private const MAX_PERIOD_SEARCH = 1200.0;

    private readonly RateCurve $curve;

    public function __construct(
        private readonly FlowSet $flows,
        RateCurve|float $rate,
        private readonly float $focalDate = 0.0
    ) {
        $this->curve = $rate instanceof RateCurve ? $rate : RateCurve::flat($rate);
    }

    /**
     * Coeficiente de X y término constante, ambos llevados a la fecha focal.
     *
     * @return array{0: float, 1: float}
     */
    private function terms(?RateCurve $curve = null): array
    {
        $curve ??= $this->curve;
        $coefficient = 0.0;
        $constant = 0.0;

        foreach ($this->flows->all() as $flow) {
            $factor = $curve->factor($flow->period, $this->focalDate) * $flow->sign();

            $coefficient += $flow->coefficient * $factor;
            $constant += $flow->constant * $factor;
        }

        return [$coefficient, $constant];
    }

    /** Residuo: ingresos menos egresos en la fecha focal. Cero es equivalencia. */
    public function residual(float $unknown = 0.0, ?RateCurve $curve = null): float
    {
        [$coefficient, $constant] = $this->terms($curve);

        return $coefficient * $unknown + $constant;
    }

    public function solveUnknownAmount(): float
    {
        [$coefficient, $constant] = $this->terms();

        if (abs($coefficient) < 1e-12) {
            throw new \LogicException(
                $this->flows->hasUnknowns()
                    ? 'Los flujos desconocidos se anulan entre sí: la ecuación no determina X.'
                    : 'No hay ningún flujo marcado como incógnita.'
            );
        }

        return -$constant / $coefficient;
    }

    /** @return list<array{label: ?string, period: float, amount: float}> */
    public function resolvedUnknowns(): array
    {
        $unknown = $this->solveUnknownAmount();
        $out = [];

        foreach ($this->flows->all() as $flow) {
            if ($flow->isUnknown()) {
                $out[] = [
                    'label' => $flow->label,
                    'period' => $flow->period,
                    'amount' => $flow->resolve($unknown),
                ];
            }
        }

        return $out;
    }

    /** Fecha de un pago único, despejada por logaritmos */
    public function solveUnknownPeriod(float $amount, string $direction = CashFlow::OUTFLOW): float
    {
        if (! $this->curve->isFlat()) {
            throw new \LogicException(
                'El despeje por logaritmos exige tasa única. Use solveUnknownPeriodNumerically().'
            );
        }

        $rest = $this->residual();
        $signed = $direction === CashFlow::INFLOW ? $amount : -$amount;
        $ratio = -$rest / $signed;

        if ($ratio <= 0) {
            throw new \DomainException(
                'No existe un periodo real que equilibre la ecuación con ese monto.'
            );
        }

        $rate = $this->curve->rateAt($this->focalDate);

        return $this->focalDate - log($ratio) / log(1 + $rate);
    }

    /** Igual que el anterior, pero por bisección: sirve con tasa por tramos */
    public function solveUnknownPeriodNumerically(
        float $amount,
        string $direction = CashFlow::OUTFLOW,
        float $lo = 0.0,
        ?float $hi = null
    ): IterationTrace {
        $rest = $this->residual();
        $sign = $direction === CashFlow::INFLOW ? 1.0 : -1.0;
        $f = fn (float $n) => $rest + $sign * $amount * $this->curve->factor($n, $this->focalDate);

        // Sin cota dada se parte del triple del último flujo y se dobla hasta
        // encerrar la raíz: con un solo flujo en 0 el triple sería 0.
        if ($hi === null) {
            $hi = max(1.0, $this->flows->lastPeriod() * 3);

            while ($f($lo) * $f($hi) > 0 && $hi < self::MAX_PERIOD_SEARCH) {
                $hi *= 2;
            }
        }

        return (new Bisection)->solve($f, $lo, $hi);
    }

    /** Tasa única que equilibra toda la operación */
    public function solveRate(?float $lo = null, ?float $hi = null): IterationTrace
    {
        return (new Bisection)->solve(
            fn (float $i) => $this->residual(0.0, RateCurve::flat($i)),
            $lo,
            $hi
        );
    }

    public function isBalanced(float $tolerance = 0.01): bool
    {
        return abs($this->residual()) < $tolerance;
    }

    public function withFocalDate(float $focalDate): self
    {
        return new self($this->flows, $this->curve, $focalDate);
    }

    /**
     * El resultado debe ser el mismo en cualquier fecha focal. Si no lo es,
     * hay un error en el traslado de flujos.
     *
     * @param list<float> $dates
     * @return array<string, float>
     */
    public function focalDateInvariance(array $dates): array
    {
        $out = [];

        foreach ($dates as $date) {
            $out[(string) $date] = $this->withFocalDate($date)->solveUnknownAmount();
        }

        return $out;
    }

    public function flows(): FlowSet
    {
        return $this->flows;
    }

    public function curve(): RateCurve
    {
        return $this->curve;
    }
}
