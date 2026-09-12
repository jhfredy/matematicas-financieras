<?php

namespace Tests\Unit\Equation;

use FinMath\Equation\CashFlow;
use FinMath\Equation\FlowSet;
use FinMath\Equation\RateCurve;
use FinMath\Equation\ValueEquation;
use FinMath\Exception\NoSignChangeException;
use PHPUnit\Framework\TestCase;

/** Las otras dos incógnitas de la ecuación de valor: el tiempo y la tasa. */
final class SolvePeriodAndRateTest extends TestCase
{
    /** 1.000.000 hoy se cancela con 1.268.241,79 al 2%: son 12 periodos */
    public function test_despeja_el_periodo_por_logaritmos(): void
    {
        $flows = FlowSet::make()->add(CashFlow::inflow(1_000_000, 0));
        $equation = new ValueEquation($flows, 0.02, 0);

        $this->assertEqualsWithDelta(12.0, $equation->solveUnknownPeriod(1_268_241.79), 1e-6);
    }

    /** El resultado no depende de la fecha focal tampoco al despejar n */
    public function test_el_periodo_no_depende_de_la_fecha_focal(): void
    {
        $flows = FlowSet::make()->add(CashFlow::inflow(1_000_000, 0));

        $atZero = (new ValueEquation($flows, 0.02, 0))->solveUnknownPeriod(1_268_241.79);
        $atTen = (new ValueEquation($flows, 0.02, 10))->solveUnknownPeriod(1_268_241.79);

        $this->assertEqualsWithDelta($atZero, $atTen, 1e-6);
    }

    /** Un pago en el mismo sentido que la deuda nunca la equilibra */
    public function test_periodo_sin_solucion_real_lanza_excepcion(): void
    {
        $flows = FlowSet::make()->add(CashFlow::inflow(1_000_000, 0));
        $equation = new ValueEquation($flows, 0.02, 0);

        $this->expectException(\DomainException::class);
        $equation->solveUnknownPeriod(500_000, CashFlow::INFLOW);
    }

    /** Con tasa por tramos el despeje por logaritmos no aplica */
    public function test_logaritmos_exige_tasa_unica(): void
    {
        $flows = FlowSet::make()->add(CashFlow::inflow(1_000_000, 0));
        $equation = new ValueEquation($flows, RateCurve::piecewise([0 => 0.02, 6 => 0.03]), 0);

        $this->expectException(\LogicException::class);
        $equation->solveUnknownPeriod(1_268_241.79);
    }

    /** 2% seis periodos y 3% los siguientes seis: 1.000.000 crece a 1.344.696,82 */
    public function test_despeja_el_periodo_por_biseccion_con_tasa_por_tramos(): void
    {
        $flows = FlowSet::make()->add(CashFlow::inflow(1_000_000, 0));
        $equation = new ValueEquation($flows, RateCurve::piecewise([0 => 0.02, 6 => 0.03]), 0);

        $trace = $equation->solveUnknownPeriodNumerically(1_344_696.8230);

        $this->assertTrue($trace->converged);
        $this->assertEqualsWithDelta(12.0, $trace->root, 1e-6);
    }

    /** 12.000.000 hoy contra 12 cuotas de 1.134.715,16: la tasa es 2% */
    public function test_despeja_la_tasa_de_una_serie_uniforme(): void
    {
        $flows = FlowSet::make()
            ->add(CashFlow::inflow(12_000_000, 0))
            ->annuity(1_134_715.16, 1, 12);

        $trace = (new ValueEquation($flows, 0.0, 0))->solveRate();

        $this->assertTrue($trace->converged);
        $this->assertEqualsWithDelta(0.02, $trace->root, 1e-6);
        // La interpolación lineal del libro queda cerca pero no igual
        $this->assertEqualsWithDelta(0.02, $trace->linearInterpolation(), 1e-3);
    }

    /** 1.000.000 hoy, 600.000 en 6 y 592.544,34 en 12 se equilibran al 2% */
    public function test_despeja_la_tasa_de_flujos_mixtos(): void
    {
        $flows = FlowSet::make()->add(
            CashFlow::inflow(1_000_000, 0),
            CashFlow::outflow(600_000, 6),
            CashFlow::outflow(592_544.343, 12),
        );

        $trace = (new ValueEquation($flows, 0.0, 0))->solveRate();

        $this->assertTrue($trace->converged);
        $this->assertEqualsWithDelta(0.02, $trace->root, 1e-6);
    }

    public function test_flujos_sin_cambio_de_signo_no_tienen_tasa(): void
    {
        $flows = FlowSet::make()->add(
            CashFlow::inflow(1_000_000, 0),
            CashFlow::inflow(500_000, 6),
        );

        $this->expectException(NoSignChangeException::class);
        (new ValueEquation($flows, 0.0, 0))->solveRate(0.0, 1.0);
    }

    /** Serie uniforme incógnita: 12.000.000 hoy en 12 cuotas de X al 2% */
    public function test_despeja_la_cuota_de_una_serie_incognita(): void
    {
        $flows = FlowSet::make()
            ->add(CashFlow::inflow(12_000_000, 0))
            ->unknownAnnuity(1, 12);

        $equation = new ValueEquation($flows, 0.02, 0);

        $this->assertEqualsWithDelta(1_134_715.16, $equation->solveUnknownAmount(), 0.01);
        $this->assertCount(12, $equation->resolvedUnknowns());
    }
}
