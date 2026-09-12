<?php

declare(strict_types=1);

namespace FinMath\Annuity\Periods;

/** Plan de pagos ya resuelto por una de las estrategias. */
final class Resolution
{
    /** @param list<array{period: int, amount: float, kind: string}> $payments */
    public function __construct(
        public readonly int $periods,
        public readonly float $regularPayment,
        public readonly array $payments,
        public readonly string $explanation
    ) {}

    public function irregularPayment(): ?array
    {
        foreach ($this->payments as $payment) {
            if ($payment['kind'] !== 'regular') {
                return $payment;
            }
        }

        return null;
    }
}
