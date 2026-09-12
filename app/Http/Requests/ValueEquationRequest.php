<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ValueEquationRequest extends RateInput
{
    public function rules(): array
    {
        return [
            'rate_mode' => ['required', Rule::in(['flat', 'piecewise'])],
            'rate' => ['required_if:rate_mode,flat', 'nullable', 'numeric', 'gt:0', 'max:1000'],
            'rate_type' => ['required', Rule::enum(\FinMath\Rate\RateType::class)],
            'rate_period' => ['required', Rule::enum(\FinMath\Rate\Period::class)],
            'rate_reference' => ['nullable', Rule::enum(\FinMath\Rate\Period::class)],

            // El formulario siempre envía un tramo vacío; solo cuenta en modo por tramos
            'segments' => ['exclude_unless:rate_mode,piecewise', 'required', 'array', 'min:1', 'max:10'],
            'segments.*.from' => ['exclude_unless:rate_mode,piecewise', 'required', 'numeric', 'min:0'],
            'segments.*.rate' => ['exclude_unless:rate_mode,piecewise', 'required', 'numeric', 'gt:0'],

            'focal_date' => ['required', 'numeric', 'min:0'],
            'solve_for' => ['required', Rule::in(['amount', 'period', 'rate'])],

            'unknown_period_amount' => ['required_if:solve_for,period', 'nullable', 'numeric', 'gt:0'],
            'unknown_period_direction' => ['required_if:solve_for,period', 'nullable', Rule::in(['ingreso', 'egreso'])],

            'items' => ['required', 'array', 'min:2', 'max:40'],
            'items.*.kind' => ['required', Rule::in(['single', 'annuity', 'arithmetic', 'geometric'])],
            'items.*.direction' => ['required', Rule::in(['ingreso', 'egreso'])],
            'items.*.period' => ['required', 'numeric', 'min:0'],
            'items.*.unknown' => ['required', 'boolean'],

            'items.*.amount' => ['required_if:items.*.unknown,false', 'nullable', 'numeric'],
            'items.*.coefficient' => ['required_if:items.*.unknown,true', 'nullable', 'numeric', 'not_in:0'],
            'items.*.constant' => ['nullable', 'numeric'],

            'items.*.count' => ['required_unless:items.*.kind,single', 'nullable', 'integer', 'min:1', 'max:600'],
            'items.*.step' => ['nullable', 'numeric', 'gt:0'],
            'items.*.variation' => ['required_if:items.*.kind,arithmetic', 'nullable', 'numeric'],
            'items.*.growth' => ['required_if:items.*.kind,geometric', 'nullable', 'numeric', 'gt:-100'],
            'items.*.gradient_direction' => ['nullable', Rule::in(['creciente', 'decreciente'])],
        ];
    }

    public function withValidator($validator): void
    {
        parent::withValidator($validator);

        $validator->after(function ($validator) {
            $items = $this->input('items', []);

            $this->checkUnknownsMatchGoal($validator, $items);
            $this->checkBothDirectionsPresent($validator, $items);
            $this->checkPeriodGoalIsSolvable($validator, $items);
            $this->checkSegmentsStartAtZero($validator);
        });
    }

    private function checkUnknownsMatchGoal($validator, array $items): void
    {
        $unknowns = array_filter($items, fn ($item) => $item['unknown'] ?? false);

        if ($this->input('solve_for') === 'amount' && $unknowns === []) {
            $validator->errors()->add(
                'items',
                'Para hallar un monto marque al menos un flujo como incógnita.'
            );
        }

        if ($this->input('solve_for') !== 'amount' && $unknowns !== []) {
            $validator->errors()->add(
                'items',
                'Al buscar el tiempo o la tasa todos los flujos deben tener monto conocido.'
            );
        }
    }

    private function checkBothDirectionsPresent($validator, array $items): void
    {
        $directions = array_unique(array_column($items, 'direction'));

        if (count($directions) < 2) {
            $validator->errors()->add(
                'items',
                'La ecuación necesita flujos en los dos sentidos: deudas e ingresos por un lado, pagos por el otro.'
            );
        }
    }

    /** Al buscar el tiempo la ecuación debe estar desbalanceada */
    private function checkPeriodGoalIsSolvable($validator, array $items): void
    {
        if ($this->input('solve_for') !== 'period') {
            return;
        }

        $amount = (float) $this->input('unknown_period_amount');

        if ($amount <= 0) {
            return;
        }

        $balance = 0.0;
        foreach ($items as $item) {
            $sign = ($item['direction'] ?? 'egreso') === 'ingreso' ? 1 : -1;
            $balance += $sign * (float) ($item['amount'] ?? 0);
        }

        if (abs($balance) < 1e-9) {
            $validator->errors()->add(
                'items',
                'Los flujos ya están equilibrados: no queda nada que cancelar con el pago único.'
            );

            return;
        }

        if (abs($balance) >= $amount * 100) {
            $validator->errors()->add(
                'unknown_period_amount',
                'El pago es demasiado pequeño frente al saldo pendiente; el periodo resultante no sería razonable.'
            );
        }
    }

    private function checkSegmentsStartAtZero($validator): void
    {
        if ($this->input('rate_mode') !== 'piecewise') {
            return;
        }

        $starts = array_map('floatval', array_column($this->input('segments', []), 'from'));

        if (! in_array(0.0, $starts, true)) {
            $validator->errors()->add('segments', 'El primer tramo debe empezar en el periodo 0.');
        }
    }
}
