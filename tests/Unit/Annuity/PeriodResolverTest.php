<?php

namespace Tests\Unit\Annuity;

use FinMath\Annuity\PeriodResolver;
use FinMath\Annuity\Periods\Context;
use PHPUnit\Framework\TestCase;

final class PeriodResolverTest extends TestCase
{
    /**
     * Ejemplo 5.15: depósitos bimestrales de 430.230 al 3% para juntar
     * 17.450.260. El texto da n = 26,93 y las cuatro salidas.
     */
    public function test_las_cuatro_salidas_del_texto(): void
    {
        $resolver = new PeriodResolver;
        $resolutions = $resolver->all(17_450_260, 430_230, 0.03, Context::future());

        $this->assertCount(4, $resolutions);

        $values = [];
        foreach ($resolutions as $label => $resolution) {
            $values[$label] = $resolution->irregularPayment()['amount']
                ?? $resolution->regularPayment;
        }

        $this->assertEqualsWithDelta(452_629.91, $values['Redondeo al entero anterior'], 1.0);
        $this->assertEqualsWithDelta(428_651.86, $values['Redondeo al entero posterior'], 1.0);
        $this->assertEqualsWithDelta(365_984.37, $values['Cuota reducida al final'], 1.0);
        $this->assertEqualsWithDelta(355_324.63, $values['Cuota extra en el último periodo'], 1.0);
    }

    public function test_n_exacto(): void
    {
        $resolver = new PeriodResolver;
        $periods = $resolver->exactPeriods(17_450_260, 430_230, 0.03, Context::future());

        $this->assertEqualsWithDelta(26.9317, $periods->exact, 0.001);
        $this->assertSame(26, $periods->floor);
        $this->assertSame(27, $periods->ceil);
    }
}
