<?php

declare(strict_types=1);

namespace FinMath\Annuity\Periods;

final class FractionalPeriods
{
    public function __construct(
        public readonly float $exact,
        public readonly int $floor,
        public readonly int $ceil
    ) {}

    public static function from(float $periods): self
    {
        return new self($periods, (int) floor($periods), (int) ceil($periods));
    }

    public function isWhole(): bool
    {
        return abs($this->exact - round($this->exact)) < 1e-9;
    }
}
