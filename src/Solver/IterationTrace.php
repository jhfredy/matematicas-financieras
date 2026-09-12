<?php

declare(strict_types=1);

namespace FinMath\Solver;

/**
 * Resultado de una búsqueda numérica, con la traza que permite
 * reproducir el método de tanteo e interpolación del texto.
 */
final class IterationTrace
{
    /**
     * @param list<array{x: float, value: float}> $iterations
     * @param array{x: float, value: float} $lowerBound valor negativo más cercano a cero
     * @param array{x: float, value: float} $upperBound valor positivo más cercano a cero
     */
    public function __construct(
        public readonly float $root,
        public readonly array $iterations,
        public readonly array $lowerBound,
        public readonly array $upperBound,
        public readonly bool $converged
    ) {}

    /**
     * Interpolación lineal entre las cotas, fórmula (3.5):
     *   ? = A1 + [(A2 - A1) / (X2 - X1)] * (X - X1), con X = 0
     */
    public function linearInterpolation(): float
    {
        $a1 = $this->lowerBound['x'];
        $a2 = $this->upperBound['x'];
        $x1 = $this->lowerBound['value'];
        $x2 = $this->upperBound['value'];

        if (abs($x2 - $x1) < 1e-15) {
            return $a1;
        }

        return $a1 + (($a2 - $a1) / ($x2 - $x1)) * (0 - $x1);
    }

    /** Cuánto se aparta la interpolación del valor exacto */
    public function interpolationError(): float
    {
        return $this->root - $this->linearInterpolation();
    }

    public function iterationCount(): int
    {
        return count($this->iterations);
    }

    /** Cotas ordenadas por x, como aparecen en las tablas del texto */
    public function boundsInOrder(): array
    {
        $bounds = [$this->lowerBound, $this->upperBound];
        usort($bounds, fn (array $a, array $b) => $a['x'] <=> $b['x']);

        return $bounds;
    }
}
