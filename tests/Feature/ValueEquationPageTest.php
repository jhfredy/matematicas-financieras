<?php

namespace Tests\Feature;

use Tests\TestCase;

class ValueEquationPageTest extends TestCase
{
    /** Con el payload tal como lo envía el formulario (tramo vacío incluido) */
    public function test_despeja_un_monto_con_tasa_unica(): void
    {
        $item = fn (array $over) => array_merge([
            'kind' => 'single', 'direction' => 'ingreso', 'period' => 0, 'unknown' => false,
            'amount' => null, 'coefficient' => 1, 'constant' => 0, 'count' => null, 'step' => 1,
            'variation' => null, 'growth' => null, 'gradient_direction' => 'creciente',
        ], $over);

        $response = $this->withHeaders(['X-Inertia' => 'true'])->post('/ecuaciones', [
            'rate_mode' => 'flat',
            'rate' => 2,
            'rate_type' => 'efectiva',
            'rate_period' => 'mensual',
            'rate_reference' => 'anual',
            'segments' => [['from' => 0, 'rate' => null]],
            'focal_date' => 0,
            'solve_for' => 'amount',
            'unknown_period_amount' => null,
            'unknown_period_direction' => 'egreso',
            'items' => [
                $item(['amount' => 1_000_000]),
                $item(['direction' => 'egreso', 'period' => 12, 'unknown' => true]),
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('component', 'Equations/Builder');
        $this->assertEqualsWithDelta(1_268_241.79, $response->json('props.result.x'), 1);
    }
}
