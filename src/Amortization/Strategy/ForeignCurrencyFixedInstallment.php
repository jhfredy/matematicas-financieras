<?php

declare(strict_types=1);

namespace FinMath\Amortization\Strategy;

use FinMath\Annuity\Annuity;

final class ForeignCurrencyFixedInstallment extends AbstractForeignCurrency
{
    private ?float $cachedInstallment = null;

    protected function periodAmounts(int $period, float $adjusted, float $interest, float $cumulative): array
    {
        $foreign = $this->installmentForeign() + ($this->scheduledExtras[$period] ?? 0.0);
        $payment = $foreign * $cumulative;

        return [$payment, $payment - $interest];
    }

    /** Cuota en moneda extranjera, constante en esa moneda */
    public function installmentForeign(): float
    {
        if ($this->cachedInstallment !== null) {
            return $this->cachedInstallment;
        }

        $extras = 0.0;
        foreach ($this->scheduledExtras as $period => $amount) {
            $extras += $amount * (1 + $this->rate) ** -$period;
        }

        return $this->cachedInstallment = Annuity::payment(
            $this->principalForeign - $extras,
            $this->rate,
            $this->periods
        );
    }

    public function label(): string
    {
        return 'Moneda extranjera, cuota uniforme';
    }
}
