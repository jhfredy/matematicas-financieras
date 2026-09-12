<?php

namespace Tests\Unit\Amortization;

use FinMath\Amortization\Strategy\ConstantPrincipal;
use FinMath\Amortization\Strategy\FixedInstallment;
use FinMath\Amortization\Strategy\GracePeriod;
use PHPUnit\Framework\TestCase;

final class AmortizationTest extends TestCase
{
    /**
     * Ejemplo 7.1.
     *
     * Ojo: el enunciado dice 36% CT, y 0,36/4 = 0,09. Pero el texto escribe
     * "i = 0,36/4 = 8 trimestral" y arma la tabla con 8%, no con 9%. La cuota
     * de 104.408,86 corresponde a 8%. Es un error aritmético del libro, así
     * que el test usa 8% para reproducir la tabla publicada.
     */
    public function test_cuota_uniforme(): void
    {
        $schedule = (new FixedInstallment(600_000, 0.08, 8))->build();

        $this->assertEqualsWithDelta(104_408.86, $schedule->rows[0]->payment, 0.01);
        $this->assertEqualsWithDelta(48_000.00, $schedule->rows[0]->interest, 0.01);
        $this->assertEqualsWithDelta(56_408.86, $schedule->rows[0]->principal, 0.01);
        $this->assertEqualsWithDelta(0.0, $schedule->closingError(), 0.01);
    }

    /** Ejemplo 7.2: extras pactadas de 80.000 y 100.000 en los trimestres 3 y 5 */
    public function test_cuota_uniforme_con_extras_pactadas(): void
    {
        $schedule = (new FixedInstallment(600_000, 0.08, 8, [3 => 80_000, 5 => 100_000]))->build();

        $this->assertEqualsWithDelta(81_514.62, $schedule->rows[0]->payment, 0.01);
        $this->assertEqualsWithDelta(161_514.62, $schedule->rows[2]->payment, 0.01);
        $this->assertEqualsWithDelta(181_514.62, $schedule->rows[4]->payment, 0.01);
        $this->assertEqualsWithDelta(0.0, $schedule->closingError(), 0.01);
    }

    /** Ejemplo 7.6: distribución del pago 80 de un crédito a 10 años */
    public function test_distribucion_de_un_pago(): void
    {
        $schedule = (new FixedInstallment(35_000_000, 0.02, 120))->build();

        $this->assertEqualsWithDelta(21_452_404.48, $schedule->balanceAt(79), 1.0);

        $breakdown = $schedule->paymentBreakdown(80);

        $this->assertEqualsWithDelta(429_048.09, $breakdown['interes'], 1.0);
        $this->assertEqualsWithDelta(342_635.30, $breakdown['amortizacion'], 1.0);
    }

    /** Ejemplo 7.4: en gracia muerta la deuda crece de 10 a 11.576.250 */
    public function test_gracia_muerta_desamortiza(): void
    {
        $schedule = (new GracePeriod(10_000_000, 0.05, 9, 3, GracePeriod::DEAD))->build();

        $this->assertEqualsWithDelta(11_576_250.00, $schedule->rows[2]->balance, 0.01);
        $this->assertTrue($schedule->rows[0]->isNegativeAmortization());
        $this->assertEqualsWithDelta(2_280_723.47, $schedule->rows[3]->payment, 0.01);
    }

    /** Ejemplo 7.5: con cuota reducida el saldo no se mueve durante la gracia */
    public function test_gracia_con_cuota_reducida(): void
    {
        $schedule = (new GracePeriod(10_000_000, 0.05, 9, 3, GracePeriod::INTEREST_ONLY))->build();

        $this->assertEqualsWithDelta(10_000_000.00, $schedule->rows[2]->balance, 0.01);
        $this->assertEqualsWithDelta(500_000.00, $schedule->rows[0]->payment, 0.01);
        $this->assertEqualsWithDelta(0.0, $schedule->rows[0]->principal, 0.01);
        $this->assertEqualsWithDelta(1_970_174.68, $schedule->rows[3]->payment, 0.01);
    }

    /** Ejemplo 7.7: 30% NT sobre 20 millones a 8 trimestres */
    public function test_abono_constante_interes_vencido(): void
    {
        $schedule = (new ConstantPrincipal(20_000_000, 0.075, 8))->build();

        $this->assertEqualsWithDelta(2_500_000.00, $schedule->rows[0]->principal, 0.01);
        $this->assertEqualsWithDelta(4_000_000.00, $schedule->rows[0]->payment, 0.01);
        $this->assertEqualsWithDelta(2_687_500.00, $schedule->rows[7]->payment, 0.01);
    }

    /** Ejemplo 7.8: con interés anticipado aparece la fila del periodo 0 */
    public function test_abono_constante_interes_anticipado(): void
    {
        $schedule = (new ConstantPrincipal(12_000_000, 0.18, 8, ConstantPrincipal::ADVANCE))->build();

        $this->assertSame(0, $schedule->rows[0]->period);
        $this->assertEqualsWithDelta(2_160_000.00, $schedule->rows[0]->payment, 0.01);
        $this->assertEqualsWithDelta(0.0, $schedule->rows[0]->principal, 0.01);
        // Periodo 1: amortiza 1.5M y paga anticipado el interés del periodo 2 sobre 10.5M
        $this->assertEqualsWithDelta(3_390_000.00, $schedule->rows[1]->payment, 0.01);
        // Última cuota: solo capital, el interés ya se pagó en el periodo 7
        $this->assertEqualsWithDelta(1_500_000.00, $schedule->rows[8]->payment, 0.01);
    }
}
