<?php

namespace App\Http\Controllers;

use App\Http\Requests\AmortizationRequest;
use App\Http\Resources\ScheduleResource;
use FinMath\Amortization\Schedule;
use FinMath\Amortization\ScheduleFactory;
use FinMath\Rate\Period;
use FinMath\Rate\Rate;
use FinMath\Rate\RateConverter;
use FinMath\Rate\RateType;
use Inertia\Inertia;

class AmortizationController extends Controller
{
    public function __construct(
        private readonly ScheduleFactory $factory,
        private readonly RateConverter $rates
    ) {}

    public function create()
    {
        return Inertia::render('Amortization/Form', [
            'options' => $this->formOptions(),
        ]);
    }

    public function store(AmortizationRequest $request)
    {
        $input = $request->validated();

        try {
            $schedule = $this->factory->make($input)->build();
        } catch (\DomainException|\LogicException|\InvalidArgumentException $e) {
            return back()->withErrors(['calculation' => $e->getMessage()]);
        }

        return Inertia::render('Amortization/Form', [
            'options' => $this->formOptions(),
            'input' => $input,
            'schedule' => (new ScheduleResource($schedule))->resolve(),
            'summary' => [
                'effective_annual' => $this->effectiveAnnual($input),
                'warnings' => $this->warnings($schedule),
            ],
        ]);
    }

    /** @return list<string> */
    private function warnings(Schedule $schedule): array
    {
        $out = [];

        if ($schedule->negativePaymentRows() !== []) {
            $out[] = 'Algunas cuotas salieron negativas: la variación del gradiente es excesiva para el plazo.';
        }

        $threshold = config('finmath.closing_error_threshold');

        if (abs($schedule->closingError()) > $threshold) {
            $out[] = sprintf(
                'El saldo final cierra en %s por acumulación de redondeo en %d periodos. '
                . 'Es efecto del cálculo en punto flotante, no un error de la tabla.',
                number_format($schedule->closingError(), 2, ',', '.'),
                count($schedule->rows)
            );
        }

        return $out;
    }

    private function effectiveAnnual(array $input): float
    {
        $rate = new Rate(
            (float) $input['rate'] / 100,
            RateType::from($input['rate_type']),
            Period::from($input['rate_period']),
            Period::from($input['rate_reference'] ?? Period::ANNUAL->value)
        );

        return $this->rates->toEffectiveAnnual($rate);
    }

    private function formOptions(): array
    {
        return [
            'periods' => $this->periodOptions(),
            'rateTypes' => $this->rateTypeOptions(),
            'methods' => [
                ['value' => 'cuota_fija', 'label' => 'Cuota uniforme'],
                ['value' => 'extra_no_pactada', 'label' => 'Abono extra no pactado'],
                ['value' => 'gracia', 'label' => 'Periodo de gracia'],
                ['value' => 'abono_constante', 'label' => 'Abono constante a capital'],
                ['value' => 'gradiente', 'label' => 'Cuotas con gradiente'],
                ['value' => 'moneda_extranjera', 'label' => 'Moneda extranjera'],
            ],
        ];
    }
}
