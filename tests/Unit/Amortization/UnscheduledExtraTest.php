<?php

namespace Tests\Unit\Amortization;

use FinMath\Amortization\Strategy\UnscheduledExtra;
use PHPUnit\Framework\TestCase;

/**
 * Sección 7.3. Valores calculados de forma independiente:
 * 10.000.000 al 2% en 12 cuotas, abono extra de 2.000.000 en la cuota 4.
 */
final class UnscheduledExtraTest extends TestCase
{
    public function test_reliquidar_conserva_el_plazo_y_baja_la_cuota(): void
    {
        $schedule = (new UnscheduledExtra(10_000_000, 0.02, 12, 4, 2_000_000))->build();

        $this->assertCount(12, $schedule->rows);
        $this->assertEqualsWithDelta(945_595.97, $schedule->rows[0]->payment, 0.01);
        $this->assertEqualsWithDelta(2_945_595.97, $schedule->rows[3]->payment, 0.01);
        $this->assertEqualsWithDelta(4_926_945.70, $schedule->rows[3]->balance, 0.01);
        $this->assertSame('abono extra, cuota reliquidada', $schedule->rows[3]->note);

        // De la cuota 5 en adelante la cuota es la nueva, constante
        $this->assertEqualsWithDelta(672_576.37, $schedule->rows[4]->payment, 0.01);
        $this->assertEqualsWithDelta(672_576.37, $schedule->rows[11]->payment, 0.01);
        $this->assertEqualsWithDelta(0.0, $schedule->closingError(), 0.01);
    }

    public function test_acortar_conserva_la_cuota_y_reduce_el_plazo(): void
    {
        $schedule = (new UnscheduledExtra(
            10_000_000, 0.02, 12, 4, 2_000_000, UnscheduledExtra::SHORTEN_TERM
        ))->build();

        $this->assertCount(10, $schedule->rows);

        // La cuota ordinaria no cambia después del abono
        $this->assertEqualsWithDelta(945_595.97, $schedule->rows[4]->payment, 0.01);
        $this->assertEqualsWithDelta(945_595.97, $schedule->rows[8]->payment, 0.01);

        // La última cuota es menor y liquida el saldo
        $last = $schedule->rows[9];
        $this->assertSame(10, $last->period);
        $this->assertEqualsWithDelta(529_203.32, $last->payment, 0.01);
        $this->assertStringContainsString('pago final', $last->note);
        $this->assertEqualsWithDelta(0.0, $last->balance, 0.01);
        $this->assertEqualsWithDelta(0.0, $schedule->closingError(), 0.01);
    }

    public function test_acortar_paga_menos_intereses_que_reliquidar(): void
    {
        $recalc = (new UnscheduledExtra(10_000_000, 0.02, 12, 4, 2_000_000))->build();
        $shorten = (new UnscheduledExtra(
            10_000_000, 0.02, 12, 4, 2_000_000, UnscheduledExtra::SHORTEN_TERM
        ))->build();

        $this->assertLessThan($recalc->totalInterest(), $shorten->totalInterest());
    }
}
