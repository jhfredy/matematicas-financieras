<?php

namespace App\Http\Resources;

use FinMath\Amortization\Row;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \FinMath\Amortization\Schedule */
class ScheduleResource extends JsonResource
{
    public function toArray($request): array
    {
        $decimals = config('finmath.display_decimals');

        return [
            'metodo' => $this->strategyLabel,
            'capital' => round($this->principalAmount, $decimals),
            'tasa_periodica' => $this->ratePerPeriod,
            'total_intereses' => round($this->totalInterest(), $decimals),
            'total_pagado' => round($this->totalPaid(), $decimals),
            'cierre' => round($this->closingError(), $decimals),
            'tiene_saldo_ajustado' => $this->hasAdjustedBalances(),
            'filas' => array_map(fn (Row $row) => [
                'periodo' => $row->period,
                'saldo' => round($row->balance, $decimals),
                'saldo_ajustado' => $row->adjustedBalance !== null
                    ? round($row->adjustedBalance, $decimals)
                    : null,
                'interes' => round($row->interest, $decimals),
                'cuota' => round($row->payment, $decimals),
                'amortizacion' => round($row->principal, $decimals),
                'desamortiza' => $row->isNegativeAmortization(),
                'cuota_negativa' => $row->isNegativePayment(),
                'nota' => $row->note,
            ], $this->rows),
        ];
    }
}
