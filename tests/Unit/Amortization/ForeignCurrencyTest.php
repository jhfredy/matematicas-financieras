<?php

namespace Tests\Unit\Amortization;

use FinMath\Amortization\ScheduleFactory;
use FinMath\Amortization\Strategy\ForeignCurrencyConstantPrincipal;
use FinMath\Amortization\Strategy\ForeignCurrencyFixedInstallment;
use PHPUnit\Framework\TestCase;

/**
 * Sección 7.9. 10.000 USD al 3% por periodo en 4 cuotas, TRM inicial 4.000,
 * devaluación del peso 2% por periodo. Valores calculados aparte.
 */
final class ForeignCurrencyTest extends TestCase
{
    private const DEVALUATIONS = [0.02, 0.02, 0.02, 0.02];

    public function test_cuota_uniforme_en_divisa_crece_en_pesos_con_la_devaluacion(): void
    {
        $strategy = new ForeignCurrencyFixedInstallment(10_000, 0.03, 4, 4_000, self::DEVALUATIONS);
        $schedule = $strategy->build();

        $this->assertEqualsWithDelta(2_690.2705, $strategy->installmentForeign(), 0.0001);
        $this->assertTrue($schedule->hasAdjustedBalances());
        $this->assertEqualsWithDelta(40_000_000, $schedule->principalAmount, 0.01);

        $first = $schedule->rows[0];
        $this->assertEqualsWithDelta(40_800_000.00, $first->adjustedBalance, 0.01);
        $this->assertEqualsWithDelta(1_224_000.00, $first->interest, 0.01);
        $this->assertEqualsWithDelta(10_976_303.44, $first->payment, 0.01);

        // La cuota en pesos sube exactamente 2% cada periodo
        $this->assertEqualsWithDelta(11_648_141.03, $schedule->rows[3]->payment, 0.01);
        $this->assertEqualsWithDelta(0.0, $schedule->rows[3]->balance, 0.01);
    }

    public function test_abono_constante_en_divisa(): void
    {
        $schedule = (new ForeignCurrencyConstantPrincipal(10_000, 0.03, 4, 4_000, self::DEVALUATIONS))->build();

        $first = $schedule->rows[0];
        $this->assertEqualsWithDelta(10_200_000.00, $first->principal, 0.01);
        $this->assertEqualsWithDelta(11_424_000.00, $first->payment, 0.01);

        $this->assertEqualsWithDelta(10_824_321.60, $schedule->rows[3]->principal, 0.01);
        $this->assertEqualsWithDelta(0.0, $schedule->rows[3]->balance, 0.01);
    }

    public function test_sin_devaluacion_coincide_con_la_tabla_en_pesos(): void
    {
        $schedule = (new ForeignCurrencyFixedInstallment(10_000, 0.03, 4, 4_000, [0, 0, 0, 0]))->build();

        // 40.000.000 al 3% en 4 cuotas: cuota = 10.761.081,81
        $this->assertEqualsWithDelta(10_761_081.81, $schedule->rows[0]->payment, 0.01);
        $this->assertEqualsWithDelta(10_761_081.81, $schedule->rows[3]->payment, 0.01);
    }

    /** El formulario habla de "la divisa se revalúa"; la fábrica lo convierte a devaluación */
    public function test_la_fabrica_convierte_revaluacion_y_expande_tramos(): void
    {
        $schedule = (new ScheduleFactory)->make([
            'method' => 'moneda_extranjera',
            'foreign_method' => 'abono_constante',
            'principal' => 10_000,
            'periods' => 4,
            'payment_period' => 'mensual',
            'rate' => 3,
            'rate_type' => 'efectiva',
            'rate_period' => 'mensual',
            'exchange_rate' => 4_000,
            'exchange_changes' => [
                ['type' => 'revaluacion', 'value' => 5, 'periods' => 1],
                ['type' => 'devaluacion', 'value' => 2, 'periods' => 3],
            ],
        ])->build();

        // Revaluación 5% => devaluación 5/95 = 5,2632%
        $this->assertEqualsWithDelta(40_000_000 * (1 + 0.05 / 0.95), $schedule->rows[0]->adjustedBalance, 0.01);
        $this->assertCount(4, $schedule->rows);
        $this->assertEqualsWithDelta(0.0, $schedule->rows[3]->balance, 0.01);
    }
}
