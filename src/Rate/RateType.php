<?php

declare(strict_types=1);

namespace FinMath\Rate;

/**
 * Modalidades de tasa del capítulo 4.
 *
 * Los valores son los que viajan en el JSON y en los formularios:
 * si cambian, hay que ajustar las claves de la tabla de equivalencias.
 */
enum RateType: string
{
    case EFFECTIVE = 'efectiva';
    case NOMINAL_DUE = 'nominal_vencida';
    case PERIODIC_ADVANCE = 'periodica_anticipada';
    case NOMINAL_ADVANCE = 'nominal_anticipada';
    case CONTINUOUS = 'continua';

    public function label(): string
    {
        return match ($this) {
            self::EFFECTIVE => 'Efectiva (periódica vencida)',
            self::NOMINAL_DUE => 'Nominal vencida',
            self::PERIODIC_ADVANCE => 'Periódica anticipada',
            self::NOMINAL_ADVANCE => 'Nominal anticipada',
            self::CONTINUOUS => 'Nominal capitalizable continuamente',
        };
    }

    /** Las nominales necesitan periodo de referencia además del de capitalización */
    public function needsReferencePeriod(): bool
    {
        return in_array($this, [self::NOMINAL_DUE, self::NOMINAL_ADVANCE, self::CONTINUOUS], true);
    }

    public function isAdvance(): bool
    {
        return in_array($this, [self::PERIODIC_ADVANCE, self::NOMINAL_ADVANCE], true);
    }

    /** Abreviatura del texto: 32% ACM, 36% NTA, 25% EA */
    public function abbreviation(): string
    {
        return match ($this) {
            self::EFFECTIVE => 'E',
            self::NOMINAL_DUE => 'N',
            self::PERIODIC_ADVANCE => 'A',
            self::NOMINAL_ADVANCE => 'NA',
            self::CONTINUOUS => 'CC',
        };
    }
}
