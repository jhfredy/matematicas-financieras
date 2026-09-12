<?php

declare(strict_types=1);

namespace FinMath\Rate;

/**
 * Periodos de capitalización y de pago.
 * perYear() es el "m" de las fórmulas del capítulo 4.
 */
enum Period: string
{
    case DAILY = 'diario';
    case WEEKLY = 'semanal';
    case BIWEEKLY = 'quincenal';
    case MONTHLY = 'mensual';
    case BIMONTHLY = 'bimestral';
    case QUARTERLY = 'trimestral';
    case FOURMONTHLY = 'cuatrimestral';
    case SEMIANNUAL = 'semestral';
    case ANNUAL = 'anual';

    /** Veces que el periodo cabe en un año */
    public function perYear(): float
    {
        return match ($this) {
            self::DAILY => 365.0,
            self::WEEKLY => 52.0,
            self::BIWEEKLY => 24.0,
            self::MONTHLY => 12.0,
            self::BIMONTHLY => 6.0,
            self::QUARTERLY => 4.0,
            self::FOURMONTHLY => 3.0,
            self::SEMIANNUAL => 2.0,
            self::ANNUAL => 1.0,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::DAILY => 'Diario',
            self::WEEKLY => 'Semanal',
            self::BIWEEKLY => 'Quincenal',
            self::MONTHLY => 'Mensual',
            self::BIMONTHLY => 'Bimestral',
            self::QUARTERLY => 'Trimestral',
            self::FOURMONTHLY => 'Cuatrimestral',
            self::SEMIANNUAL => 'Semestral',
            self::ANNUAL => 'Anual',
        };
    }

    /** Meses que dura el periodo; útil para el eje del diagrama */
    public function months(): float
    {
        return 12.0 / $this->perYear();
    }

    /** El año comercial de 360 días del capítulo 2 */
    public function daysCommercial(): float
    {
        return 360.0 / $this->perYear();
    }

    public function daysExact(): float
    {
        return 365.0 / $this->perYear();
    }
}
