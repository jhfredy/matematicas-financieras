<?php

declare(strict_types=1);

namespace FinMath\Equation;

/**
 * Tasa por tramos. Varios ejercicios cambian la tasa a mitad del
 * horizonte: 30% el primer año y 33% de ahí en adelante.
 *
 * Convención: la tasa de un tramo rige DESDE el periodo indicado.
 */
final class RateCurve
{
    /** @param list<array{from: float, rate: float}> $segments ordenados */
    private function __construct(private readonly array $segments) {}

    public static function flat(float $rate): self
    {
        return new self([['from' => 0.0, 'rate' => $rate]]);
    }

    /**
     * @param array<int|string, float> $ratesByStart periodo inicial => tasa
     */
    public static function piecewise(array $ratesByStart): self
    {
        $segments = [];

        foreach ($ratesByStart as $from => $rate) {
            $segments[] = ['from' => (float) $from, 'rate' => (float) $rate];
        }

        usort($segments, fn (array $a, array $b) => $a['from'] <=> $b['from']);

        if ($segments === [] || $segments[0]['from'] > 0.0) {
            throw new \InvalidArgumentException('La curva debe definir una tasa desde el periodo 0.');
        }

        return new self($segments);
    }

    public function rateAt(float $period): float
    {
        $rate = $this->segments[0]['rate'];

        foreach ($this->segments as $segment) {
            if ($period >= $segment['from']) {
                $rate = $segment['rate'];
            }
        }

        return $rate;
    }

    /**
     * Factor de capitalización de $from a $to, acumulado tramo por tramo.
     * Si $to < $from devuelve el recíproco, o sea descuenta.
     */
    public function factor(float $from, float $to): float
    {
        if (abs($to - $from) < 1e-12) {
            return 1.0;
        }

        if ($to < $from) {
            return 1.0 / $this->factor($to, $from);
        }

        $factor = 1.0;
        $cursor = $from;

        foreach ($this->boundaries($from, $to) as $boundary) {
            $factor *= (1 + $this->rateAt($cursor)) ** ($boundary - $cursor);
            $cursor = $boundary;
        }

        return $factor * (1 + $this->rateAt($cursor)) ** ($to - $cursor);
    }

    /** @return list<float> fronteras estrictamente internas al intervalo */
    private function boundaries(float $from, float $to): array
    {
        $out = [];

        foreach ($this->segments as $segment) {
            if ($segment['from'] > $from && $segment['from'] < $to) {
                $out[] = $segment['from'];
            }
        }

        return $out;
    }

    public function isFlat(): bool
    {
        return count($this->segments) === 1;
    }

    /** @return list<array{from: float, rate: float}> */
    public function segments(): array
    {
        return $this->segments;
    }
}
