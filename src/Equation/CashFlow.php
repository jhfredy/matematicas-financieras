<?php

declare(strict_types=1);

namespace FinMath\Equation;

/**
 * Un flujo de caja del diagrama económico.
 *
 * Cada flujo representa la expresión lineal coefficient·X + constant, lo
 * que permite plantear "X", "3X" o "X + 200.000" sin casos especiales.
 * Un flujo conocido es 0·X + monto.
 */
final class CashFlow
{
    public const INFLOW = 'ingreso';
    public const OUTFLOW = 'egreso';

    private function __construct(
        public readonly float $coefficient,
        public readonly float $constant,
        public readonly float $period,
        public readonly string $direction,
        public readonly ?string $label = null
    ) {}

    public static function inflow(float $amount, float $period, ?string $label = null): self
    {
        return new self(0.0, $amount, $period, self::INFLOW, $label);
    }

    public static function outflow(float $amount, float $period, ?string $label = null): self
    {
        return new self(0.0, $amount, $period, self::OUTFLOW, $label);
    }

    public static function unknownInflow(
        float $coefficient,
        float $constant,
        float $period,
        ?string $label = null
    ): self {
        return new self(
            $coefficient,
            $constant,
            $period,
            self::INFLOW,
            $label ?? self::describe($coefficient, $constant)
        );
    }

    public static function unknownOutflow(
        float $coefficient,
        float $constant,
        float $period,
        ?string $label = null
    ): self {
        return new self(
            $coefficient,
            $constant,
            $period,
            self::OUTFLOW,
            $label ?? self::describe($coefficient, $constant)
        );
    }

    public function isUnknown(): bool
    {
        return abs($this->coefficient) > 1e-12;
    }

    /** Ingresos suman, egresos restan */
    public function sign(): float
    {
        return $this->direction === self::INFLOW ? 1.0 : -1.0;
    }

    /** Monto ya resuelto, una vez conocida la incógnita */
    public function resolve(float $unknown): float
    {
        return $this->coefficient * $unknown + $this->constant;
    }

    private static function describe(float $coefficient, float $constant): string
    {
        $term = abs($coefficient - 1.0) < 1e-12 ? 'X' : sprintf('%gX', $coefficient);

        if (abs($constant) < 1e-12) {
            return $term;
        }

        return sprintf('%s %s %s', $term, $constant > 0 ? '+' : '-', number_format(abs($constant), 0, ',', '.'));
    }
}
