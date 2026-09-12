<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValueEquationRequest;
use App\Services\EquationBuilder;
use FinMath\Equation\CashFlow;
use FinMath\Equation\ValueEquation;
use Inertia\Inertia;

class ValueEquationController extends Controller
{
    public function __construct(private readonly EquationBuilder $builder) {}

    public function create()
    {
        return Inertia::render('Equations/Builder', [
            'options' => $this->options(),
        ]);
    }

    public function solve(ValueEquationRequest $request)
    {
        $input = $request->validated();

        try {
            $equation = $this->builder->build($input);

            $result = match ($input['solve_for']) {
                'amount' => $this->solveAmount($equation, $input),
                'period' => $this->solvePeriod($equation, $input),
                'rate' => $this->solveRate($equation),
            };
        } catch (\DomainException|\LogicException|\InvalidArgumentException $e) {
            return back()->withErrors(['equation' => $e->getMessage()]);
        }

        return Inertia::render('Equations/Builder', [
            'options' => $this->options(),
            'input' => $input,
            'result' => $result,
            'diagram' => $this->diagram($input, $result),
        ]);
    }

    private function solveAmount(ValueEquation $equation, array $input): array
    {
        return [
            'type' => 'amount',
            'x' => $equation->solveUnknownAmount(),
            'resolved' => $equation->resolvedUnknowns(),
            'invariance' => $equation->focalDateInvariance($this->sampleFocalDates($input)),
        ];
    }

    private function solvePeriod(ValueEquation $equation, array $input): array
    {
        $amount = (float) $input['unknown_period_amount'];
        $direction = $input['unknown_period_direction'] === 'ingreso'
            ? CashFlow::INFLOW
            : CashFlow::OUTFLOW;

        // Con tasa por tramos el despeje por logaritmos no aplica
        if (($input['rate_mode'] ?? 'flat') === 'piecewise') {
            $trace = $equation->solveUnknownPeriodNumerically($amount, $direction);

            return [
                'type' => 'period',
                'n' => $trace->root,
                'method' => 'bisección',
                'iterations' => $trace->iterationCount(),
                'converged' => $trace->converged,
            ];
        }

        return [
            'type' => 'period',
            'n' => $equation->solveUnknownPeriod($amount, $direction),
            'method' => 'logaritmos',
        ];
    }

    private function solveRate(ValueEquation $equation): array
    {
        $trace = $equation->solveRate();

        return [
            'type' => 'rate',
            'i' => $trace->root,
            'interpolated' => $trace->linearInterpolation(),
            'error' => $trace->interpolationError(),
            'bounds' => $trace->boundsInOrder(),
            'iterations' => array_slice($trace->iterations, 0, 12),
            'iteration_count' => $trace->iterationCount(),
            'converged' => $trace->converged,
        ];
    }

    /** Flujos con su monto resuelto, para dibujar el diagrama */
    private function diagram(array $input, array $result): array
    {
        $unknown = $result['x'] ?? 0.0;

        return array_map(fn (CashFlow $flow) => [
            'period' => $flow->period,
            'amount' => $flow->resolve($unknown),
            'direction' => $flow->direction,
            'label' => $flow->label,
            'unknown' => $flow->isUnknown(),
        ], $this->builder->flows($input['items'])->all());
    }

    /** @return list<float> */
    private function sampleFocalDates(array $input): array
    {
        $periods = array_map(fn ($item) => (float) $item['period'], $input['items']);

        return array_values(array_unique([
            0.0,
            min($periods),
            (float) $input['focal_date'],
            max($periods),
        ]));
    }

    private function options(): array
    {
        return [
            'periods' => $this->periodOptions(),
            'rateTypes' => $this->rateTypeOptions(),
        ];
    }
}
