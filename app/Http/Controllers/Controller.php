<?php

namespace App\Http\Controllers;

use FinMath\Rate\Period;
use FinMath\Rate\RateType;

abstract class Controller
{
    /** Opciones que comparten los tres formularios */
    protected function periodOptions(): array
    {
        return array_map(fn (Period $period) => [
            'value' => $period->value,
            'label' => $period->label(),
            'perYear' => $period->perYear(),
        ], Period::cases());
    }

    protected function rateTypeOptions(): array
    {
        return array_map(fn (RateType $type) => [
            'value' => $type->value,
            'label' => $type->label(),
            'needsReference' => $type->needsReferencePeriod(),
        ], RateType::cases());
    }
}
