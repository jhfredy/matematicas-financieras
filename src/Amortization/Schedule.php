<?php

declare(strict_types=1);

namespace FinMath\Amortization;

final class Schedule
{
    /** @param list<Row> $rows */
    public function __construct(
        public readonly array $rows,
        public readonly float $principalAmount,
        public readonly float $ratePerPeriod,
        public readonly string $strategyLabel
    ) {}

    public function totalInterest(): float
    {
        return array_sum(array_map(fn (Row $row) => $row->interest, $this->rows));
    }

    public function totalPaid(): float
    {
        return array_sum(array_map(fn (Row $row) => $row->payment, $this->rows));
    }

    public function row(int $period): Row
    {
        foreach ($this->rows as $row) {
            if ($row->period === $period) {
                return $row;
            }
        }

        throw new \OutOfRangeException("La tabla no tiene el periodo {$period}.");
    }

    public function balanceAt(int $period): float
    {
        return $this->row($period)->balance;
    }

    /** Distribución de un pago, sección 7.6 */
    public function paymentBreakdown(int $period): array
    {
        $row = $this->row($period);

        return [
            'cuota' => $row->payment,
            'interes' => $row->interest,
            'amortizacion' => $row->principal,
            'saldo' => $row->balance,
        ];
    }

    /**
     * Saldo de la última fila. Con float rara vez es cero exacto:
     * en tablas largas el arrastre llega al orden del peso.
     */
    public function closingError(): float
    {
        if ($this->rows === []) {
            return 0.0;
        }

        return $this->rows[count($this->rows) - 1]->balance;
    }

    /** @return list<Row> */
    public function negativePaymentRows(): array
    {
        return array_values(array_filter($this->rows, fn (Row $row) => $row->isNegativePayment()));
    }

    public function hasAdjustedBalances(): bool
    {
        return $this->rows !== [] && $this->rows[0]->adjustedBalance !== null;
    }
}
