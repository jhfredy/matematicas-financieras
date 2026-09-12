<?php

declare(strict_types=1);

namespace FinMath\Amortization;

interface AmortizationStrategy
{
    public function build(): Schedule;

    public function label(): string;
}
