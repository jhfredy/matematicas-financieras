<?php

declare(strict_types=1);

namespace FinMath\Amortization;

/** Una fila de la tabla de amortización. */
final class Row
{
    public function __construct(
        public readonly int $period,
        public readonly float $balance,
        public readonly float $interest,
        public readonly float $payment,
        public readonly float $principal,
        public readonly ?float $adjustedBalance = null,
        public readonly ?string $note = null
    ) {}

    /** Desamortización: la deuda creció en lugar de bajar */
    public function isNegativeAmortization(): bool
    {
        return $this->principal < 0;
    }

    public function isNegativePayment(): bool
    {
        return $this->payment < 0;
    }
}
