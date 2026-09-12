<?php

namespace Tests\Feature;

use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** Las tres incógnitas de la ecuación de valor a través del formulario. */
class ValueEquationPageTest extends TestCase
{
    private function item(array $over = []): array
    {
        return array_merge([
            'kind' => 'single', 'direction' => 'ingreso', 'period' => 0, 'unknown' => false,
            'amount' => null, 'coefficient' => 1, 'constant' => 0, 'count' => null, 'step' => 1,
            'variation' => null, 'growth' => null, 'gradient_direction' => 'creciente',
        ], $over);
    }

    /** Payload tal como lo envía el formulario, tramo vacío incluido */
    private function solve(array $over): TestResponse
    {
        return $this->withHeaders(['X-Inertia' => 'true'])->post('/ecuaciones', array_merge([
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
        ], $over));
    }

    public function test_despeja_un_monto_con_tasa_unica(): void
    {
        $response = $this->solve(['items' => [
            $this->item(['amount' => 1_000_000]),
            $this->item(['direction' => 'egreso', 'period' => 12, 'unknown' => true]),
        ]]);

        $response->assertOk();
        $response->assertJsonPath('component', 'Equations/Builder');
        $response->assertJsonPath('props.result.type', 'amount');
        $this->assertEqualsWithDelta(1_268_241.79, $response->json('props.result.x'), 0.01);
    }

    public function test_despeja_la_cuota_de_una_serie_uniforme(): void
    {
        $response = $this->solve(['items' => [
            $this->item(['amount' => 12_000_000]),
            $this->item(['kind' => 'annuity', 'direction' => 'egreso', 'period' => 1, 'count' => 12, 'unknown' => true]),
        ]]);

        $response->assertOk();
        $this->assertEqualsWithDelta(1_134_715.16, $response->json('props.result.x'), 0.01);
        $this->assertCount(13, $response->json('props.diagram'));
    }

    public function test_despeja_el_periodo_por_logaritmos(): void
    {
        $response = $this->solve([
            'solve_for' => 'period',
            'unknown_period_amount' => 1_268_241.79,
            'items' => [
                $this->item(['amount' => 1_000_000]),
                $this->item(['direction' => 'egreso', 'period' => 6, 'amount' => 1]),
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('props.result.type', 'period');
        $response->assertJsonPath('props.result.method', 'logaritmos');
        $this->assertEqualsWithDelta(12.0, $response->json('props.result.n'), 1e-3);
    }

    public function test_despeja_el_periodo_por_biseccion_con_tasa_por_tramos(): void
    {
        $response = $this->solve([
            'rate_mode' => 'piecewise',
            'rate' => null,
            'segments' => [['from' => 0, 'rate' => 2], ['from' => 6, 'rate' => 3]],
            'solve_for' => 'period',
            'unknown_period_amount' => 1_344_696.823,
            'items' => [
                $this->item(['amount' => 1_000_000]),
                $this->item(['direction' => 'egreso', 'period' => 6, 'amount' => 1]),
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('props.result.method', 'bisección');
        $response->assertJsonPath('props.result.converged', true);
        $this->assertEqualsWithDelta(12.0, $response->json('props.result.n'), 1e-3);
    }

    public function test_despeja_la_tasa(): void
    {
        $response = $this->solve([
            'solve_for' => 'rate',
            'rate' => null,
            'items' => [
                $this->item(['amount' => 12_000_000]),
                $this->item(['kind' => 'annuity', 'direction' => 'egreso', 'period' => 1, 'count' => 12, 'amount' => 1_134_715.16]),
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('props.result.type', 'rate');
        $response->assertJsonPath('props.result.converged', true);
        $this->assertEqualsWithDelta(0.02, $response->json('props.result.i'), 1e-6);
    }

    /** Al buscar la tasa ningún flujo puede ser incógnita: la validación lo dice */
    public function test_tasa_con_incognitas_es_rechazada(): void
    {
        $response = $this->solve([
            'solve_for' => 'rate',
            'rate' => null,
            'items' => [
                $this->item(['amount' => 1_000_000]),
                $this->item(['direction' => 'egreso', 'period' => 12, 'unknown' => true]),
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['items']);
    }

    /** Todos los flujos en el mismo sentido: sin tasa que los equilibre */
    public function test_flujos_sin_contrapartida_devuelven_error_legible(): void
    {
        $response = $this->solve([
            'solve_for' => 'rate',
            'rate' => null,
            'items' => [
                $this->item(['amount' => 1_000_000]),
                $this->item(['period' => 6, 'amount' => 500_000]),
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['items']);
    }
}
