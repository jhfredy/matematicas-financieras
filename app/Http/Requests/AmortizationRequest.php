<?php

namespace App\Http\Requests;

use FinMath\Amortization\Strategy\ConstantPrincipal;
use FinMath\Amortization\Strategy\GracePeriod;
use FinMath\Amortization\Strategy\GradientInstallment;
use FinMath\Amortization\Strategy\UnscheduledExtra;
use FinMath\Rate\Period;
use Illuminate\Validation\Rule;

class AmortizationRequest extends RateInput
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'payment_period' => ['required', Rule::enum(Period::class)],
            'principal' => ['required', 'numeric', 'gt:0'],
            'periods' => ['required', 'integer', 'min:1', 'max:' . config('finmath.max_schedule_rows')],
            'method' => ['required', Rule::in([
                'cuota_fija', 'extra_no_pactada', 'gracia',
                'abono_constante', 'gradiente', 'moneda_extranjera',
            ])],

            'scheduled_extras' => ['array', 'max:20'],
            'scheduled_extras.*.period' => ['required_with:scheduled_extras', 'integer', 'min:1', 'lte:periods'],
            'scheduled_extras.*.amount' => ['required_with:scheduled_extras', 'numeric', 'gt:0'],

            'extra_period' => ['required_if:method,extra_no_pactada', 'nullable', 'integer', 'min:1', 'lt:periods'],
            'extra_amount' => ['required_if:method,extra_no_pactada', 'nullable', 'numeric', 'gt:0'],
            'behaviour' => ['nullable', Rule::in([
                UnscheduledExtra::RECALCULATE_PAYMENT,
                UnscheduledExtra::SHORTEN_TERM,
            ])],

            'grace_periods' => ['required_if:method,gracia', 'nullable', 'integer', 'min:1', 'lt:periods'],
            'grace_type' => ['nullable', Rule::in([GracePeriod::DEAD, GracePeriod::INTEREST_ONLY])],

            'interest_timing' => ['nullable', Rule::in([ConstantPrincipal::DUE, ConstantPrincipal::ADVANCE])],

            'variation' => ['required_if:method,gradiente', 'nullable', 'numeric'],
            'gradient_kind' => ['nullable', Rule::in([
                GradientInstallment::ARITHMETIC,
                GradientInstallment::GEOMETRIC,
            ])],
            'gradient_direction' => ['nullable', Rule::in(['creciente', 'decreciente'])],

            // El formulario siempre envía un tramo vacío por defecto; solo se
            // valida cuando el método realmente lo usa.
            'exchange_rate' => ['exclude_unless:method,moneda_extranjera', 'required', 'numeric', 'gt:0'],
            'exchange_changes' => ['exclude_unless:method,moneda_extranjera', 'required', 'array', 'min:1', 'max:20'],
            'exchange_changes.*.type' => ['exclude_unless:method,moneda_extranjera', 'required', Rule::in(['devaluacion', 'revaluacion'])],
            'exchange_changes.*.value' => ['exclude_unless:method,moneda_extranjera', 'required', 'numeric', 'gt:-100', 'lt:100'],
            'exchange_changes.*.periods' => ['exclude_unless:method,moneda_extranjera', 'required', 'integer', 'min:1'],
            'foreign_method' => ['nullable', Rule::in(['cuota_fija', 'abono_constante'])],
        ]);
    }

    public function withValidator($validator): void
    {
        parent::withValidator($validator);

        $validator->after(function ($validator) {
            $this->checkExtrasFitPrincipal($validator);
            $this->checkExchangeCoversTerm($validator);
            $this->checkGradientStaysPositive($validator);
        });
    }

    private function checkExtrasFitPrincipal($validator): void
    {
        if (! $this->filled('scheduled_extras')) {
            return;
        }

        $total = array_sum(array_column($this->input('scheduled_extras'), 'amount'));

        if ($total >= (float) $this->input('principal')) {
            $validator->errors()->add(
                'scheduled_extras',
                'Las cuotas extras suman más que el capital: no queda saldo para las cuotas ordinarias.'
            );
        }
    }

    private function checkExchangeCoversTerm($validator): void
    {
        if ($this->input('method') !== 'moneda_extranjera') {
            return;
        }

        $covered = array_sum(array_column($this->input('exchange_changes', []), 'periods'));
        $periods = (int) $this->input('periods');

        if ($covered < $periods) {
            $validator->errors()->add(
                'exchange_changes',
                "Los tramos cubren {$covered} periodos y el plazo es de {$periods}. Agregue los que faltan."
            );
        }
    }

    /**
     * Cota aproximada: la base real se conoce solo después de calcular, así
     * que esto atrapa los casos gruesos y el controlador revisa el resto
     * sobre la tabla ya construida.
     */
    private function checkGradientStaysPositive($validator): void
    {
        if ($this->input('method') !== 'gradiente'
            || $this->input('gradient_direction') !== 'decreciente'
            || $this->input('gradient_kind') === GradientInstallment::GEOMETRIC) {
            return;
        }

        $variation = abs((float) $this->input('variation'));
        $periods = (int) $this->input('periods');
        $average = (float) $this->input('principal') / max(1, $periods);

        if ($variation * ($periods - 1) > $average * 2) {
            $validator->errors()->add(
                'variation',
                'El decremento es tan grande que las últimas cuotas saldrían negativas. Reduzca la variación o el plazo.'
            );
        }
    }
}
