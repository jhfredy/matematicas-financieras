<?php

declare(strict_types=1);

namespace FinMath\Amortization\Strategy;

use FinMath\Amortization\AmortizationStrategy;
use FinMath\Amortization\Row;
use FinMath\Amortization\Schedule;

/**
 * Sección 7.9. Deuda en moneda extranjera con cuotas en pesos.
 *
 * Cada fila lleva saldo sin ajustar y saldo ajustado por devaluación; el
 * interés se calcula sobre el ajustado. El arreglo de devaluaciones debe
 * llegar YA convertido a devaluación de la moneda local: si el dato de
 * origen es "el dólar se revalúa X%", hay que pasarlo antes por
 * ExchangeRateConverter::revaluationToDevaluation().
 */
abstract class AbstractForeignCurrency implements AmortizationStrategy
{
    /**
     * @param list<float> $devaluations una por periodo
     * @param array<int, float> $scheduledExtras periodo => monto en moneda extranjera
     */
    public function __construct(
        protected readonly float $principalForeign,
        protected readonly float $rate,
        protected readonly int $periods,
        protected readonly float $initialExchangeRate,
        protected readonly array $devaluations,
        protected readonly array $scheduledExtras = []
    ) {}

    public function build(): Schedule
    {
        $unadjusted = $this->principalForeign * $this->initialExchangeRate;
        $cumulative = $this->initialExchangeRate;
        $rows = [];

        for ($k = 1; $k <= $this->periods; $k++) {
            $devaluation = $this->devaluations[$k - 1] ?? 0.0;

            $cumulative *= (1 + $devaluation);
            $adjusted = $unadjusted * (1 + $devaluation);
            $interest = $adjusted * $this->rate;

            [$payment, $principal] = $this->periodAmounts($k, $adjusted, $interest, $cumulative);

            // La última cuota liquida el saldo: con float el arrastre de las
            // tasas de cambio acumuladas deja diferencias de pesos, no de centavos
            if ($k === $this->periods) {
                $principal = $adjusted;
                $payment = $principal + $interest;
            }

            $unadjusted = $adjusted - $principal;

            $rows[] = new Row(
                period: $k,
                balance: $unadjusted,
                interest: $interest,
                payment: $payment,
                principal: $principal,
                adjustedBalance: $adjusted,
                note: isset($this->scheduledExtras[$k]) ? 'cuota extra pactada' : null
            );
        }

        return new Schedule(
            $rows,
            $this->principalForeign * $this->initialExchangeRate,
            $this->rate,
            $this->label()
        );
    }

    /** @return array{0: float, 1: float} [cuota en pesos, amortización en pesos] */
    abstract protected function periodAmounts(
        int $period,
        float $adjusted,
        float $interest,
        float $cumulative
    ): array;
}
