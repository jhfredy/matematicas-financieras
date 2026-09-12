<?php

declare(strict_types=1);

namespace FinMath\Annuity\Periods;

use FinMath\Annuity\Annuity;

/** Si el objetivo es un presente o un futuro, y si los pagos son vencidos. */
final class Context
{
    public const PRESENT = 'presente';
    public const FUTURE = 'futuro';

    public function __construct(
        public readonly string $target = self::PRESENT,
        public readonly string $mode = Annuity::DUE
    ) {}

    public static function present(string $mode = Annuity::DUE): self
    {
        return new self(self::PRESENT, $mode);
    }

    public static function future(string $mode = Annuity::DUE): self
    {
        return new self(self::FUTURE, $mode);
    }

    public function isFuture(): bool
    {
        return $this->target === self::FUTURE;
    }
}
