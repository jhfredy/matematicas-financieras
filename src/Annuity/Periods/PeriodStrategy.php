<?php

declare(strict_types=1);

namespace FinMath\Annuity\Periods;

interface PeriodStrategy
{
    public function resolve(FractionalPeriods $periods, float $target, float $rate, Context $context): Resolution;

    public function label(): string;
}
