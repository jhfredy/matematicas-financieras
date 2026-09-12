<?php

namespace Tests\Unit\Rate;

use FinMath\Rate\Period;
use FinMath\Rate\Rate;
use FinMath\Rate\RateConverter;
use FinMath\Rate\RateType;
use PHPUnit\Framework\TestCase;

/** Ejercicios del capítulo 4, con los valores que da el texto. */
final class RateConverterTest extends TestCase
{
    private RateConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new RateConverter;
    }

    /** Ejemplo 4.2: 20% convertible bimestralmente da 21,74% efectivo anual */
    public function test_nominal_a_efectiva_anual(): void
    {
        $rate = Rate::nominal(0.20, Period::BIMONTHLY);

        $this->assertEqualsWithDelta(0.2174, $this->converter->toEffectiveAnnual($rate), 0.0001);
    }

    /** Ejemplo 4.3: 35% efectivo anual da 31,16% nominal trimestral */
    public function test_efectiva_anual_a_nominal_trimestral(): void
    {
        $rate = Rate::effective(0.35, Period::ANNUAL);
        $target = $this->converter->convert($rate, RateType::NOMINAL_DUE, Period::QUARTERLY);

        $this->assertEqualsWithDelta(0.3116, $target->value, 0.0001);
    }

    /** Ejemplo 4.6: 36% CT equivale a 35,48% nominal bimestral */
    public function test_nominal_trimestral_a_nominal_bimestral(): void
    {
        $rate = Rate::nominal(0.36, Period::QUARTERLY);
        $target = $this->converter->convert($rate, RateType::NOMINAL_DUE, Period::BIMONTHLY);

        $this->assertEqualsWithDelta(0.3548, $target->value, 0.0001);
    }

    /** Ejemplo 4.14: 36% trimestre anticipado da 38,93% nominal bimestral vencida */
    public function test_nominal_anticipada_a_nominal_vencida(): void
    {
        $rate = Rate::nominalAdvance(0.36, Period::QUARTERLY);
        $target = $this->converter->convert($rate, RateType::NOMINAL_DUE, Period::BIMONTHLY);

        $this->assertEqualsWithDelta(0.3893, $target->value, 0.0001);
    }

    /** Ejemplo 4.17: 15% semestral da 6,75% trimestral anticipada */
    public function test_efectiva_a_periodica_anticipada(): void
    {
        $rate = Rate::effective(0.15, Period::SEMIANNUAL);
        $target = $this->converter->convert($rate, RateType::PERIODIC_ADVANCE, Period::QUARTERLY);

        $this->assertEqualsWithDelta(0.0675, $target->value, 0.0001);
    }

    /** Ejemplo 4.8: 18% semestral da 1,389% bimensual */
    public function test_efectiva_semestral_a_bimensual(): void
    {
        $rate = Rate::effective(0.18, Period::SEMIANNUAL);
        $target = $this->converter->convert($rate, RateType::EFFECTIVE, Period::BIWEEKLY);

        $this->assertEqualsWithDelta(0.01389, $target->value, 0.00001);
    }

    public function test_una_anticipada_del_cien_por_ciento_no_es_convertible(): void
    {
        $this->expectException(\DomainException::class);

        Rate::periodicAdvance(1.0, Period::MONTHLY);
    }

    public function test_ida_y_vuelta_conserva_la_tasa(): void
    {
        $original = Rate::nominal(0.32, Period::MONTHLY);

        $advance = $this->converter->convert($original, RateType::NOMINAL_ADVANCE, Period::QUARTERLY);
        $back = $this->converter->convert($advance, RateType::NOMINAL_DUE, Period::MONTHLY);

        $this->assertEqualsWithDelta($original->value, $back->value, 1e-9);
    }
}
