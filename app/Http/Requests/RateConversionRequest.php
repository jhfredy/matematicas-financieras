<?php

namespace App\Http\Requests;

use FinMath\Rate\Period;
use FinMath\Rate\RateType;
use Illuminate\Validation\Rule;

class RateConversionRequest extends RateInput
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'target_type' => ['required', Rule::enum(RateType::class)],
            'target_period' => ['required', Rule::enum(Period::class)],
            'target_reference' => ['nullable', Rule::enum(Period::class)],
        ]);
    }
}
