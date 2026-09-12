<?php

declare(strict_types=1);

namespace FinMath\Rate;

/**
 * Tasa de interés como objeto de valor inmutable.
 *
 * El valor siempre va en decimal, nunca en porcentaje: 0.025 y no 2.5.
 * Separar el tipo del periodo evita el error más común del texto,
 * que es meter una tasa nominal (r) donde va una periódica (i).
 */
final class Rate
{
    public function __construct(
        public readonly float $value,
        public readonly RateType $type,
        public readonly Period $period,
        public readonly Period $reference = Period::ANNUAL
    ) {
        if ($type->isAdvance() && $this->periodicAdvanceValue() >= 1.0) {
            throw new \DomainException(
                'Una tasa anticipada por periodo de 100% o más no tiene equivalente vencida.'
            );
        }
    }

    public static function effective(float $value, Period $period): self
    {
        return new self($value, RateType::EFFECTIVE, $period, $period);
    }

    /** 32% capitalizable mensualmente => nominal(0.32, Period::MONTHLY) */
    public static function nominal(float $value, Period $capitalization, Period $reference = Period::ANNUAL): self
    {
        return new self($value, RateType::NOMINAL_DUE, $capitalization, $reference);
    }

    public static function nominalAdvance(float $value, Period $capitalization, Period $reference = Period::ANNUAL): self
    {
        return new self($value, RateType::NOMINAL_ADVANCE, $capitalization, $reference);
    }

    public static function periodicAdvance(float $value, Period $period): self
    {
        return new self($value, RateType::PERIODIC_ADVANCE, $period, $period);
    }

    public static function continuous(float $value, Period $reference = Period::ANNUAL): self
    {
        return new self($value, RateType::CONTINUOUS, $reference, $reference);
    }

    /** Fracción m: capitalizaciones dentro del periodo de referencia */
    public function m(): float
    {
        return $this->period->perYear() / $this->reference->perYear();
    }

    /** Tasa periódica vencida equivalente, en el propio periodo de la tasa */
    public function toPeriodicDue(): float
    {
        return match ($this->type) {
            RateType::EFFECTIVE => $this->value,
            RateType::NOMINAL_DUE => $this->value / $this->m(),
            RateType::PERIODIC_ADVANCE => $this->value / (1 - $this->value),
            RateType::NOMINAL_ADVANCE => $this->advanceToDue($this->value / $this->m()),
            RateType::CONTINUOUS => exp($this->value / $this->m()) - 1,
        };
    }

    private function advanceToDue(float $ia): float
    {
        return $ia / (1 - $ia);
    }

    private function periodicAdvanceValue(): float
    {
        return $this->type === RateType::PERIODIC_ADVANCE
            ? $this->value
            : $this->value / $this->m();
    }

    /** 2,5% mensual / 32% nominal mensual */
    public function describe(): string
    {
        return sprintf(
            '%s %% %s %s',
            rtrim(rtrim(number_format($this->value * 100, 4, ',', '.'), '0'), ','),
            $this->type->abbreviation(),
            $this->period->label()
        );
    }
}
