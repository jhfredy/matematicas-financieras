<?php

declare(strict_types=1);

namespace FinMath\Amortization\Strategy;

final class ForeignCurrencyConstantPrincipal extends AbstractForeignCurrency
{
    protected function periodAmounts(int $period, float $adjusted, float $interest, float $cumulative): array
    {
        $principal = ($this->principalForeign / $this->periods) * $cumulative;

        return [$principal + $interest, $principal];
    }

    public function label(): string
    {
        return 'Moneda extranjera, abono constante a capital';
    }
}
