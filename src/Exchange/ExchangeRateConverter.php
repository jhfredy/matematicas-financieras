<?php

declare(strict_types=1);

namespace FinMath\Exchange;

/**
 * Sección 4.11. Devaluación, revaluación e inflación.
 *
 * La trampa recurrente: si el dólar se revalúa 3,8% frente al peso, el peso
 * NO se devalúa 3,8% frente al dólar, sino 3,95%. Las dos tasas miden el
 * mismo hecho desde bases distintas.
 */
final class ExchangeRateConverter
{
    /** (4.31) idv entre dos tasas de cambio */
    public function devaluationBetween(float $from, float $to): float
    {
        return ($to - $from) / $from;
    }

    /** (4.33) devaluación promedio de n periodos */
    public function averageDevaluation(float $from, float $to, float $periods): float
    {
        return ($to / $from) ** (1 / $periods) - 1;
    }

    /** (4.37) idv = irv / (1 - irv) */
    public function revaluationToDevaluation(float $revaluation): float
    {
        if ($revaluation >= 1.0) {
            throw new \DomainException('Una revaluación de 100% o más no tiene devaluación equivalente.');
        }

        return $revaluation / (1 - $revaluation);
    }

    /** (4.36) irv = idv / (1 + idv) */
    public function devaluationToRevaluation(float $devaluation): float
    {
        return $devaluation / (1 + $devaluation);
    }

    /** (4.30) costo o rentabilidad total: ieq = ime + idv + ime·idv */
    public function equivalentRate(float $foreignRate, float $devaluation): float
    {
        return $foreignRate + $devaluation + $foreignRate * $devaluation;
    }

    /** (4.38) tasa deflactada: ide = (if - idi) / (1 + idi) */
    public function deflate(float $nominalRate, float $inflation): float
    {
        return ($nominalRate - $inflation) / (1 + $inflation);
    }

    /** La inversa: tasa comercial que deja una rentabilidad real dada */
    public function inflate(float $realRate, float $inflation): float
    {
        return $realRate * (1 + $inflation) + $inflation;
    }

    /** Devaluación que mantiene competitividad entre dos economías */
    public function devaluationFromInflations(float $internal, float $external): float
    {
        return ($internal - $external) / (1 + $external);
    }

    /** Tasa de cambio proyectada: TC1 = TC0 (1 + idv)^n */
    public function projectRate(float $current, float $devaluation, float $periods): float
    {
        return $current * (1 + $devaluation) ** $periods;
    }

    /** UVR (4.39): UVRt = UVR15 (1 + IPC)^(t/d) */
    public function uvr(float $baseUvr, float $monthlyIpc, int $elapsedDays, int $periodDays): float
    {
        return $baseUvr * (1 + $monthlyIpc) ** ($elapsedDays / $periodDays);
    }
}
