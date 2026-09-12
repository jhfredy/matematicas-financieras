<?php

namespace App\Http\Controllers;

use App\Http\Requests\RateConversionRequest;
use FinMath\Rate\Period;
use FinMath\Rate\Rate;
use FinMath\Rate\RateConverter;
use FinMath\Rate\RateType;
use Inertia\Inertia;

class RateController extends Controller
{
    public function __construct(private readonly RateConverter $converter) {}

    public function form()
    {
        return Inertia::render('Rates/Converter', [
            'options' => $this->options(),
        ]);
    }

    public function convert(RateConversionRequest $request)
    {
        $input = $request->validated();

        try {
            $source = new Rate(
                (float) $input['rate'] / 100,
                RateType::from($input['rate_type']),
                Period::from($input['rate_period']),
                Period::from($input['rate_reference'] ?? Period::ANNUAL->value)
            );

            $target = $this->converter->convert(
                $source,
                RateType::from($input['target_type']),
                Period::from($input['target_period']),
                Period::from($input['target_reference'] ?? Period::ANNUAL->value)
            );
        } catch (\DomainException $e) {
            return back()->withErrors(['rate' => $e->getMessage()]);
        }

        return Inertia::render('Rates/Converter', [
            'options' => $this->options(),
            'input' => $input,
            'result' => [
                'value' => $target->value,
                'describe' => $target->describe(),
                'periodic_due' => $target->toPeriodicDue(),
                'effective_annual' => $this->converter->toEffectiveAnnual($source),
                'label' => sprintf(
                    '%s, %s',
                    RateType::from($input['target_type'])->label(),
                    Period::from($input['target_period'])->label()
                ),
                'equivalences' => $this->converter->equivalenceTable($source),
            ],
        ]);
    }

    private function options(): array
    {
        return [
            'periods' => $this->periodOptions(),
            'rateTypes' => $this->rateTypeOptions(),
        ];
    }
}
