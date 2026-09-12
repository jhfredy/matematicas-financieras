<?php

namespace Tests\Unit\Amortization;

use FinMath\Gradient\ArithmeticGradient;
use FinMath\Gradient\GeometricGradient;
use PHPUnit\Framework\TestCase;

final class GradientTest extends TestCase
{
    /** Ejemplo 6.1: 18 cuotas desde 220.000 creciendo 30.000 al 3,5% */
    public function test_valor_presente_gradiente_aritmetico(): void
    {
        $value = ArithmeticGradient::presentValue(220_000, 30_000, 0.035, 18);

        $this->assertEqualsWithDelta(5_901_028.16, $value, 1.0);
    }

    public function test_cuota_de_la_serie_aritmetica(): void
    {
        $this->assertEqualsWithDelta(550_000.0, ArithmeticGradient::payment(220_000, 30_000, 12), 0.01);
    }

    /** Ejemplo 6.20: 24 cuotas desde 850.000 creciendo 10% al 3% */
    public function test_valor_presente_gradiente_geometrico(): void
    {
        $value = GeometricGradient::presentValue(850_000, 0.10, 0.03, 24);

        $this->assertEqualsWithDelta(46_694_334.68, $value, 1.0);
    }

    /**
     * Ejemplo 6.28: decreciente del 1,8%.
     *
     * La tolerancia es de 5 pesos, no de un centavo, porque el texto redondea
     * el factor a 11,4261 y divide sobre ese valor. Con el factor completo
     * (11,42608275) la primera cuota da 1.750.381,16 en vez de 1.750.378,52.
     * La diferencia es del libro, no del cálculo.
     */
    public function test_gradiente_geometrico_decreciente(): void
    {
        $first = GeometricGradient::firstPaymentFromPresent(20_000_000, -0.018, 0.02, 15);

        $this->assertEqualsWithDelta(1_750_378.52, $first, 5.0);
    }

    public function test_saldo_tras_nueve_cuotas_decrecientes(): void
    {
        $first = GeometricGradient::firstPaymentFromPresent(20_000_000, -0.018, 0.02, 15);
        $balance = GeometricGradient::balance(20_000_000, $first, -0.018, 0.02, 9);

        $this->assertEqualsWithDelta(7_968_548.86, $balance, 30.0);
    }

    /** El decreciente con G negativo debe dar lo mismo que la fórmula con signo aparte */
    public function test_el_signo_de_la_variacion_es_equivalente(): void
    {
        $withSign = ArithmeticGradient::presentValue(3_900_000, -20_000, 0.025, 120);

        $annuity = (1 - 1.025 ** -120) / 0.025;
        $gradient = ($annuity - 120 * 1.025 ** -120) / 0.025;
        $manual = 3_900_000 * $annuity - 20_000 * $gradient;

        $this->assertEqualsWithDelta($manual, $withSign, 1e-6);
    }

    public function test_la_perpetuidad_geometrica_exige_tasa_mayor_al_crecimiento(): void
    {
        $this->expectException(\DomainException::class);

        GeometricGradient::perpetuity(300_000, 0.20, 0.20);
    }
}
