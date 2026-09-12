<?php

namespace Tests\Unit\Equation;

use FinMath\Compound\CompoundInterest;
use FinMath\Equation\CashFlow;
use FinMath\Equation\FlowSet;
use FinMath\Equation\RateCurve;
use FinMath\Equation\ValueEquation;
use PHPUnit\Framework\TestCase;

final class ValueEquationTest extends TestCase
{
    /** Ejemplo 4.37: tres deudas con tasa propia, refinanciadas al 24% CM */
    public function test_refinanciacion_con_tasas_propias(): void
    {
        $flows = FlowSet::make()->add(
            CashFlow::inflow(CompoundInterest::futureValue(200_000, 0.15, 1), 6, 'deuda 1'),
            CashFlow::inflow(CompoundInterest::futureValue(150_000, 0.07, 16 / 3), 16, 'deuda 2'),
            CashFlow::inflow(CompoundInterest::futureValue(220_000, 0.03, 20), 20, 'deuda 3'),
            CashFlow::outflow(300_000, 0, 'pago hoy'),
            CashFlow::unknownOutflow(1.0, 0.0, 14),
        );

        $equation = new ValueEquation($flows, 0.02, 14);

        $this->assertEqualsWithDelta(433_294.19, $equation->solveUnknownAmount(), 1.0);
    }

    /** La fecha focal no debe cambiar el resultado */
    public function test_el_resultado_no_depende_de_la_fecha_focal(): void
    {
        $flows = FlowSet::make()->add(
            CashFlow::inflow(1_000_000, 0),
            CashFlow::unknownOutflow(1.0, 0.0, 6),
            CashFlow::unknownOutflow(1.0, 0.0, 12),
        );

        $equation = new ValueEquation($flows, 0.025, 0);
        $values = array_values($equation->focalDateInvariance([0.0, 6.0, 12.0, 20.0]));

        foreach ($values as $value) {
            $this->assertEqualsWithDelta($values[0], $value, 1e-6);
        }
    }

    /** Ejercicio 2.50: X dentro de 3 meses y el 300% de X dentro de 8 */
    public function test_incognita_con_coeficiente(): void
    {
        $flows = FlowSet::make()->add(
            CashFlow::inflow(9_000_000, 5),
            CashFlow::inflow(17_000_000, 10),
            CashFlow::unknownOutflow(1.0, 0.0, 3),
            CashFlow::unknownOutflow(3.0, 0.0, 8),
        );

        $equation = new ValueEquation($flows, 0.32 / 12, 8);
        $resolved = $equation->resolvedUnknowns();

        $this->assertCount(2, $resolved);
        $this->assertEqualsWithDelta(3 * $resolved[0]['amount'], $resolved[1]['amount'], 0.01);
    }

    /** Ejercicio 4.51: el último pago es 200.000 mayor que el primero */
    public function test_incognita_con_termino_constante(): void
    {
        $flows = FlowSet::make()->add(
            CashFlow::inflow(1_500_000, 0),
            CashFlow::unknownOutflow(1.0, 0.0, 4),
            CashFlow::unknownOutflow(1.0, 200_000.0, 7),
        );

        $equation = new ValueEquation($flows, 0.015, 0);
        $resolved = $equation->resolvedUnknowns();

        $this->assertEqualsWithDelta(716_030.14, $resolved[0]['amount'], 1.0);
        $this->assertEqualsWithDelta(916_030.14, $resolved[1]['amount'], 1.0);
    }

    /** Con tasa por tramos el factor debe ser consistente al partir el intervalo */
    public function test_la_curva_por_tramos_es_consistente(): void
    {
        $curve = RateCurve::piecewise([0 => 0.02, 12 => 0.03]);

        $direct = $curve->factor(0, 24);
        $split = $curve->factor(0, 12) * $curve->factor(12, 24);

        $this->assertEqualsWithDelta($direct, $split, 1e-12);
    }

    public function test_el_descuento_es_el_reciproco_de_la_capitalizacion(): void
    {
        $curve = RateCurve::piecewise([0 => 0.02, 10 => 0.035]);

        $this->assertEqualsWithDelta(1.0, $curve->factor(0, 18) * $curve->factor(18, 0), 1e-12);
    }

    public function test_sin_incognita_avisa_en_lugar_de_dividir_por_cero(): void
    {
        $flows = FlowSet::make()->add(
            CashFlow::inflow(100_000, 0),
            CashFlow::outflow(100_000, 1),
        );

        $this->expectException(\LogicException::class);

        (new ValueEquation($flows, 0.02, 0))->solveUnknownAmount();
    }
}
