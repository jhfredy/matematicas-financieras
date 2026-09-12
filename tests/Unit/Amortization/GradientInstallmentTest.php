<?php

namespace Tests\Unit\Amortization;

use FinMath\Amortization\ScheduleFactory;
use FinMath\Amortization\Strategy\GradientInstallment;
use PHPUnit\Framework\TestCase;

final class GradientInstallmentTest extends TestCase
{
    /** Ejemplo 6.1 al revés: el torno vale 5.901.028,16 y se paga con 220.000 + 30.000 por mes al 3,5% */
    public function test_gradiente_aritmetico_creciente(): void
    {
        $schedule = (new GradientInstallment(5_901_028.16, 0.035, 18, 30_000))->build();

        $this->assertCount(18, $schedule->rows);
        $this->assertEqualsWithDelta(220_000, $schedule->rows[0]->payment, 0.05);
        $this->assertEqualsWithDelta(250_000, $schedule->rows[1]->payment, 0.05);
        $this->assertEqualsWithDelta(730_000, $schedule->rows[17]->payment, 0.5);
        $this->assertEqualsWithDelta(0.0, $schedule->closingError(), 0.01);
        $this->assertSame([], $schedule->negativePaymentRows());
        $this->assertSame('Cuotas con gradiente aritmético creciente', $schedule->strategyLabel);
    }

    /** Ejemplo 6.28: 20 millones al 2%, 15 cuotas que bajan 1,8% cada una */
    public function test_gradiente_geometrico_decreciente(): void
    {
        $schedule = (new GradientInstallment(
            20_000_000, 0.02, 15, -0.018, GradientInstallment::GEOMETRIC
        ))->build();

        $this->assertEqualsWithDelta(1_750_381.16, $schedule->rows[0]->payment, 0.01);
        $this->assertEqualsWithDelta(1_750_381.16 * 0.982, $schedule->rows[1]->payment, 0.01);
        $this->assertEqualsWithDelta(7_968_524.80, $schedule->rows[8]->balance, 0.01);
        $this->assertEqualsWithDelta(0.0, $schedule->closingError(), 0.01);
    }

    public function test_gradiente_con_gracia_muerta(): void
    {
        $schedule = (new GradientInstallment(10_000_000, 0.02, 12, 50_000, gracePeriods: 2))->build();

        $this->assertCount(12, $schedule->rows);
        $this->assertSame('gracia muerta', $schedule->rows[0]->note);
        $this->assertEqualsWithDelta(0.0, $schedule->rows[0]->payment, 0.01);
        $this->assertEqualsWithDelta(10_000_000 * 1.02 ** 2, $schedule->rows[1]->balance, 0.01);
        $this->assertEqualsWithDelta(50_000, $schedule->rows[3]->payment - $schedule->rows[2]->payment, 0.01);
        $this->assertEqualsWithDelta(0.0, $schedule->closingError(), 0.01);
    }

    public function test_variacion_excesiva_marca_cuotas_negativas(): void
    {
        $schedule = (new GradientInstallment(1_000_000, 0.02, 12, -200_000))->build();

        $this->assertNotEmpty($schedule->negativePaymentRows());
    }

    /** El formulario manda la variación en positivo y la dirección aparte */
    public function test_la_fabrica_niega_la_variacion_decreciente(): void
    {
        $base = [
            'method' => 'gradiente',
            'principal' => 20_000_000,
            'periods' => 15,
            'payment_period' => 'mensual',
            'rate' => 2,
            'rate_type' => 'efectiva',
            'rate_period' => 'mensual',
            'gradient_kind' => 'geometrica',
            'variation' => 1.8,
        ];

        $down = (new ScheduleFactory)->make($base + ['gradient_direction' => 'decreciente'])->build();
        $up = (new ScheduleFactory)->make($base + ['gradient_direction' => 'creciente'])->build();

        $this->assertLessThan($down->rows[0]->payment, $down->rows[1]->payment);
        $this->assertGreaterThan($up->rows[0]->payment, $up->rows[1]->payment);
        $this->assertEqualsWithDelta(1_750_381.16, $down->rows[0]->payment, 0.01);
    }
}
