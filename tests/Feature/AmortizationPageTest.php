<?php

namespace Tests\Feature;

use Tests\TestCase;

class AmortizationPageTest extends TestCase
{
    public function test_genera_tabla_de_cuota_fija(): void
    {
        $response = $this->withHeaders(['X-Inertia' => 'true'])->post('/amortizacion', [
            'principal' => 12_000_000,
            'periods' => 12,
            'payment_period' => 'mensual',
            'method' => 'cuota_fija',
            'rate' => 2,
            'rate_type' => 'efectiva',
            'rate_period' => 'mensual',
            'rate_reference' => 'anual',
            'scheduled_extras' => [],
            // El formulario siempre manda un tramo vacío de moneda extranjera
            'exchange_changes' => [['type' => 'devaluacion', 'value' => null, 'periods' => 1]],
            'extra_period' => null,
            'extra_amount' => null,
            'grace_periods' => null,
            'variation' => null,
            'exchange_rate' => null,
        ]);

        $response->assertOk();
        $response->assertJsonPath('component', 'Amortization/Form');
        $this->assertCount(12, $response->json('props.schedule.filas'));
    }
}
