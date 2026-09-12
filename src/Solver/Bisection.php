<?php

declare(strict_types=1);

namespace FinMath\Solver;

use FinMath\Exception\NoSignChangeException;

/**
 * Bisección con traza.
 *
 * Reemplaza el tanteo manual del texto para hallar i y n. Devuelve la raíz
 * exacta y además las dos cotas que la encierran, para poder mostrar la
 * interpolación lineal del libro al lado del valor exacto.
 */
final class Bisection
{
    public function __construct(
        private readonly float $tolerance = 1e-10,
        private readonly int $maxIterations = 200
    ) {}

    /**
     * @param callable(float): float $f
     * @param float|null $lo cota inferior; null busca el intervalo
     */
    public function solve(callable $f, ?float $lo = null, ?float $hi = null): IterationTrace
    {
        if ($lo === null || $hi === null) {
            [$lo, $hi] = $this->bracket($f);
        }

        $fLo = $f($lo);
        $fHi = $f($hi);

        if ($fLo * $fHi > 0) {
            throw new NoSignChangeException(sprintf(
                'No hay cambio de signo entre %.6f y %.6f: los residuos son %.4f y %.4f. '
                . 'Pruebe con cotas más amplias.',
                $lo, $hi, $fLo, $fHi
            ));
        }

        // Las cotas del texto: el residuo negativo y el positivo más próximos a cero
        $negative = $fLo < 0 ? ['x' => $lo, 'value' => $fLo] : ['x' => $hi, 'value' => $fHi];
        $positive = $fLo < 0 ? ['x' => $hi, 'value' => $fHi] : ['x' => $lo, 'value' => $fLo];

        $iterations = [];
        $mid = $lo;
        $converged = false;

        for ($k = 0; $k < $this->maxIterations; $k++) {
            $mid = ($lo + $hi) / 2;
            $fMid = $f($mid);

            $iterations[] = ['x' => $mid, 'value' => $fMid];

            if (abs($fMid) < $this->tolerance || ($hi - $lo) / 2 < $this->tolerance) {
                $converged = true;
                break;
            }

            if ($fLo * $fMid < 0) {
                $hi = $mid;
            } else {
                $lo = $mid;
                $fLo = $fMid;
            }
        }

        return new IterationTrace($mid, $iterations, $negative, $positive, $converged);
    }

    /** Amplía el intervalo hasta encontrar cambio de signo */
    private function bracket(callable $f): array
    {
        $lo = 1e-9;
        $fLo = $f($lo);

        for ($hi = 0.01; $hi <= 100.0; $hi *= 1.5) {
            if ($fLo * $f($hi) < 0) {
                return [$lo, $hi];
            }
        }

        throw new NoSignChangeException(
            'No se encontró intervalo con cambio de signo. Revise los datos: '
            . 'puede que los flujos no admitan solución.'
        );
    }
}
