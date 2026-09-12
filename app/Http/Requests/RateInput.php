<?php

namespace App\Http\Requests;

use FinMath\Rate\Period;
use FinMath\Rate\RateType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Reglas comunes a todo formulario que reciba una tasa. */
class RateInput extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rate' => ['required', 'numeric', 'gt:0', 'max:1000'],
            'rate_type' => ['required', Rule::enum(RateType::class)],
            'rate_period' => ['required', Rule::enum(Period::class)],
            'rate_reference' => ['nullable', Rule::enum(Period::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'rate.gt' => 'La tasa debe ser mayor que cero.',
            'rate.max' => 'Una tasa por encima de 1000% no es plausible; revise si escribió decimal en vez de porcentaje.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->checkAdvanceRateIsConvertible($validator);
            $this->checkCapitalizationFitsReference($validator);
        });
    }

    /** Una anticipada del 100% o más no tiene equivalente vencida */
    protected function checkAdvanceRateIsConvertible($validator): void
    {
        if (! $this->filled('rate_type') || ! $this->filled('rate')) {
            return;
        }

        $type = RateType::tryFrom($this->input('rate_type'));

        if ($type === null || ! $type->isAdvance()) {
            return;
        }

        if ($this->periodicAdvanceRate() >= 1.0) {
            $validator->errors()->add(
                'rate',
                'Una tasa anticipada de 100% o más por periodo no tiene tasa vencida equivalente.'
            );
        }
    }

    protected function checkCapitalizationFitsReference($validator): void
    {
        if (! $this->filled('rate_reference') || ! $this->filled('rate_period')) {
            return;
        }

        $capitalization = Period::tryFrom($this->input('rate_period'));
        $reference = Period::tryFrom($this->input('rate_reference'));

        if ($capitalization === null || $reference === null) {
            return;
        }

        if ($capitalization->perYear() < $reference->perYear()) {
            $validator->errors()->add(
                'rate_period',
                'El periodo de capitalización debe ser igual o menor que el de referencia.'
            );
        }
    }

    protected function periodicAdvanceRate(): float
    {
        $value = (float) $this->input('rate') / 100;
        $type = RateType::tryFrom($this->input('rate_type'));

        if ($type === RateType::PERIODIC_ADVANCE) {
            return $value;
        }

        $capitalization = Period::tryFrom($this->input('rate_period'));
        $reference = Period::tryFrom($this->input('rate_reference', Period::ANNUAL->value)) ?? Period::ANNUAL;

        if ($capitalization === null) {
            return $value;
        }

        return $value / ($capitalization->perYear() / $reference->perYear());
    }
}
