<?php

declare(strict_types=1);

namespace FinMath\Rate;

/**
 * Equivalencia entre tasas.
 *
 * El texto plantea dieciséis igualdades según la combinación de origen
 * y destino. Aquí hay un solo camino: toda tasa sube a efectiva anual y
 * de ahí baja al periodo y la modalidad pedidos. El resultado es el mismo
 * y no hay dieciséis fórmulas que mantener.
 */
final class RateConverter
{
    /** El pivote de toda conversión */
    public function toEffectiveAnnual(Rate $rate): float
    {
        return (1 + $rate->toPeriodicDue()) ** $rate->period->perYear() - 1;
    }

    /** Periódica vencida equivalente en cualquier periodo */
    public function toPeriodic(Rate $rate, Period $target): float
    {
        return (1 + $this->toEffectiveAnnual($rate)) ** (1 / $target->perYear()) - 1;
    }

    public function convert(
        Rate $from,
        RateType $type,
        Period $period,
        Period $reference = Period::ANNUAL
    ): Rate {
        $i = $this->toPeriodic($from, $period);
        $m = $period->perYear() / $reference->perYear();

        $value = match ($type) {
            RateType::EFFECTIVE => $i,
            RateType::NOMINAL_DUE => $i * $m,
            RateType::PERIODIC_ADVANCE => $this->dueToAdvance($i),
            RateType::NOMINAL_ADVANCE => $this->dueToAdvance($i) * $m,
            RateType::CONTINUOUS => log(1 + $i) * $m,
        };

        return new Rate($value, $type, $period, $reference);
    }

    /** (4.8) ia = i / (1 + i) */
    public function dueToAdvance(float $i): float
    {
        return $i / (1 + $i);
    }

    /** (4.7) i = ia / (1 - ia) */
    public function advanceToDue(float $ia): float
    {
        if ($ia >= 1.0) {
            throw new \DomainException('Una tasa anticipada de 100% o más no tiene equivalente vencida.');
        }

        return $ia / (1 - $ia);
    }

    /**
     * La misma tasa en todas las modalidades y periodos usuales.
     * Las claves del segundo nivel son los value del enum RateType.
     *
     * @param list<Period>|null $periods
     * @return list<array<string, mixed>>
     */
    public function equivalenceTable(Rate $source, ?array $periods = null): array
    {
        $periods ??= [
            Period::MONTHLY,
            Period::BIMONTHLY,
            Period::QUARTERLY,
            Period::FOURMONTHLY,
            Period::SEMIANNUAL,
            Period::ANNUAL,
        ];

        $types = [
            RateType::EFFECTIVE,
            RateType::NOMINAL_DUE,
            RateType::PERIODIC_ADVANCE,
            RateType::NOMINAL_ADVANCE,
        ];

        $rows = [];

        foreach ($periods as $period) {
            $row = ['period' => $period->value, 'period_label' => $period->label()];

            foreach ($types as $type) {
                try {
                    $row[$type->value] = $this->convert($source, $type, $period)->value;
                } catch (\DomainException) {
                    // Una anticipada puede pasarse del 100% en periodos largos
                    $row[$type->value] = null;
                }
            }

            $rows[] = $row;
        }

        return $rows;
    }
}
