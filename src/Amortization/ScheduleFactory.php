<?php

declare(strict_types=1);

namespace FinMath\Amortization;

use FinMath\Amortization\Strategy\ConstantPrincipal;
use FinMath\Amortization\Strategy\FixedInstallment;
use FinMath\Amortization\Strategy\ForeignCurrencyConstantPrincipal;
use FinMath\Amortization\Strategy\ForeignCurrencyFixedInstallment;
use FinMath\Amortization\Strategy\GracePeriod;
use FinMath\Amortization\Strategy\GradientInstallment;
use FinMath\Amortization\Strategy\UnscheduledExtra;
use FinMath\Exchange\ExchangeRateConverter;
use FinMath\Rate\Period;
use FinMath\Rate\Rate;
use FinMath\Rate\RateConverter;
use FinMath\Rate\RateType;

/** Arma la estrategia a partir de la entrada ya validada del formulario. */
final class ScheduleFactory
{
    public function __construct(
        private readonly RateConverter $rates = new RateConverter,
        private readonly ExchangeRateConverter $exchange = new ExchangeRateConverter
    ) {}

    public function make(array $input): AmortizationStrategy
    {
        $rate = $this->periodicRate($input);
        $principal = (float) $input['principal'];
        $periods = (int) $input['periods'];

        return match ($input['method']) {
            'cuota_fija' => new FixedInstallment(
                $principal,
                $rate,
                $periods,
                $this->extras($input['scheduled_extras'] ?? [])
            ),

            'extra_no_pactada' => new UnscheduledExtra(
                $principal,
                $rate,
                $periods,
                (int) $input['extra_period'],
                (float) $input['extra_amount'],
                $input['behaviour'] ?? UnscheduledExtra::RECALCULATE_PAYMENT
            ),

            'gracia' => new GracePeriod(
                $principal,
                $rate,
                $periods,
                (int) $input['grace_periods'],
                $input['grace_type'] ?? GracePeriod::DEAD
            ),

            'abono_constante' => new ConstantPrincipal(
                $principal,
                $rate,
                $periods,
                $input['interest_timing'] ?? ConstantPrincipal::DUE
            ),

            'gradiente' => new GradientInstallment(
                $principal,
                $rate,
                $periods,
                $this->variation($input),
                $input['gradient_kind'] ?? GradientInstallment::ARITHMETIC,
                (int) ($input['grace_periods'] ?? 0),
                ($input['grace_type'] ?? GracePeriod::DEAD) === GracePeriod::DEAD
            ),

            'moneda_extranjera' => $this->foreign($input, $rate, $periods),

            default => throw new \InvalidArgumentException(
                "Método de amortización desconocido: {$input['method']}."
            ),
        };
    }

    /** Convierte la tasa declarada al periodo de pago */
    private function periodicRate(array $input): float
    {
        $rate = new Rate(
            (float) $input['rate'] / 100,
            RateType::from($input['rate_type']),
            Period::from($input['rate_period']),
            Period::from($input['rate_reference'] ?? Period::ANNUAL->value)
        );

        return $this->rates->toPeriodic($rate, Period::from($input['payment_period']));
    }

    /**
     * Aquí está el punto más fácil de romper: el formulario manda la
     * variación en positivo y elige la dirección aparte. Si esto no niega
     * el valor, un gradiente decreciente se calcula como creciente.
     */
    private function variation(array $input): float
    {
        $value = abs((float) $input['variation']);

        if (($input['gradient_kind'] ?? null) === GradientInstallment::GEOMETRIC) {
            $value /= 100;
        }

        return ($input['gradient_direction'] ?? 'creciente') === 'decreciente' ? -$value : $value;
    }

    private function foreign(array $input, float $rate, int $periods): AmortizationStrategy
    {
        $class = ($input['foreign_method'] ?? 'cuota_fija') === 'abono_constante'
            ? ForeignCurrencyConstantPrincipal::class
            : ForeignCurrencyFixedInstallment::class;

        return new $class(
            (float) $input['principal'],
            $rate,
            $periods,
            (float) $input['exchange_rate'],
            $this->devaluations($input['exchange_changes'] ?? []),
            $this->extras($input['scheduled_extras'] ?? [])
        );
    }

    /**
     * Cada tramo: ['type' => 'devaluacion'|'revaluacion', 'value' => %, 'periods' => n].
     * La revaluación de la divisa se convierte a devaluación de la moneda local.
     *
     * @return list<float>
     */
    private function devaluations(array $changes): array
    {
        $out = [];

        foreach ($changes as $change) {
            $value = (float) $change['value'] / 100;

            $devaluation = $change['type'] === 'revaluacion'
                ? $this->exchange->revaluationToDevaluation($value)
                : $value;

            $count = max(1, (int) ($change['periods'] ?? 1));
            $out = array_merge($out, array_fill(0, $count, $devaluation));
        }

        return $out;
    }

    /** @return array<int, float> */
    private function extras(array $extras): array
    {
        $out = [];

        foreach ($extras as $extra) {
            $out[(int) $extra['period']] = (float) $extra['amount'];
        }

        return $out;
    }
}
