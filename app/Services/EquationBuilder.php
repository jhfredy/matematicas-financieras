<?php

namespace App\Services;

use FinMath\Equation\CashFlow;
use FinMath\Equation\FlowSet;
use FinMath\Equation\RateCurve;
use FinMath\Equation\ValueEquation;
use FinMath\Rate\Period;
use FinMath\Rate\Rate;
use FinMath\Rate\RateConverter;
use FinMath\Rate\RateType;

/** Traduce el payload del formulario a un FlowSet y una ecuación. */
class EquationBuilder
{
    public function __construct(private readonly RateConverter $rates) {}

    public function build(array $input): ValueEquation
    {
        return new ValueEquation(
            $this->flows($input['items']),
            $this->curve($input),
            (float) $input['focal_date']
        );
    }

    public function flows(array $items): FlowSet
    {
        $set = FlowSet::make();

        foreach ($items as $item) {
            $set = $this->addItem($set, $item);
        }

        return $set;
    }

    private function addItem(FlowSet $set, array $item): FlowSet
    {
        $kind = $item['kind'];
        $direction = $item['direction'] === 'ingreso' ? CashFlow::INFLOW : CashFlow::OUTFLOW;
        $period = (float) $item['period'];
        $step = (float) ($item['step'] ?? 1.0);
        $count = (int) ($item['count'] ?? 1);
        $constant = (float) ($item['constant'] ?? 0.0);

        return ($item['unknown'] ?? false)
            ? $this->addUnknown($set, $kind, $item, $direction, $period, $count, $step, $constant)
            : $this->addKnown($set, $kind, $item, $direction, $period, $count, $step);
    }

    private function addKnown(
        FlowSet $set,
        string $kind,
        array $item,
        string $direction,
        float $period,
        int $count,
        float $step
    ): FlowSet {
        $amount = (float) $item['amount'];

        return match ($kind) {
            'single' => $set->add(
                $direction === CashFlow::INFLOW
                    ? CashFlow::inflow($amount, $period, $item['label'] ?? null)
                    : CashFlow::outflow($amount, $period, $item['label'] ?? null)
            ),
            'annuity' => $set->annuity($amount, $period, $count, $direction, $step),
            'arithmetic' => $set->arithmeticGradient(
                $amount, $this->variation($item), $period, $count, $direction, $step
            ),
            'geometric' => $set->geometricGradient(
                $amount, $this->growth($item), $period, $count, $direction, $step
            ),
            default => throw new \InvalidArgumentException("Tipo de flujo desconocido: {$kind}."),
        };
    }

    private function addUnknown(
        FlowSet $set,
        string $kind,
        array $item,
        string $direction,
        float $period,
        int $count,
        float $step,
        float $constant
    ): FlowSet {
        return match ($kind) {
            'single' => $set->add(
                $direction === CashFlow::INFLOW
                    ? CashFlow::unknownInflow((float) $item['coefficient'], $constant, $period)
                    : CashFlow::unknownOutflow((float) $item['coefficient'], $constant, $period)
            ),
            'annuity' => $set->unknownAnnuity($period, $count, $direction, $step, $constant),
            'arithmetic' => $set->unknownArithmeticGradient(
                $this->variation($item), $period, $count, $direction, $step
            ),
            'geometric' => $set->unknownGeometricGradient(
                $this->growth($item), $period, $count, $direction, $step
            ),
            default => throw new \InvalidArgumentException("Tipo de flujo desconocido: {$kind}."),
        };
    }

    /** El formulario manda la magnitud y la dirección por separado */
    private function variation(array $item): float
    {
        $value = abs((float) $item['variation']);

        return ($item['gradient_direction'] ?? 'creciente') === 'decreciente' ? -$value : $value;
    }

    private function growth(array $item): float
    {
        $value = abs((float) $item['growth']) / 100;

        return ($item['gradient_direction'] ?? 'creciente') === 'decreciente' ? -$value : $value;
    }

    private function curve(array $input): RateCurve
    {
        if (($input['rate_mode'] ?? 'flat') === 'flat') {
            return RateCurve::flat($this->periodicRate((float) $input['rate'], $input));
        }

        $byStart = [];

        foreach ($input['segments'] as $segment) {
            $byStart[(string) $segment['from']] = $this->periodicRate((float) $segment['rate'], $input);
        }

        return RateCurve::piecewise($byStart);
    }

    /** La tasa se expresa en el periodo del eje de los flujos */
    private function periodicRate(float $percent, array $input): float
    {
        $rate = new Rate(
            $percent / 100,
            RateType::from($input['rate_type']),
            Period::from($input['rate_period']),
            Period::from($input['rate_reference'] ?? Period::ANNUAL->value)
        );

        return $this->rates->toPeriodic($rate, Period::from($input['rate_period']));
    }
}
