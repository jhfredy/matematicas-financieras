<?php

namespace App\Providers;

use App\Services\EquationBuilder;
use FinMath\Amortization\ScheduleFactory;
use FinMath\Annuity\PeriodResolver;
use FinMath\Exchange\ExchangeRateConverter;
use FinMath\Rate\RateConverter;
use FinMath\Solver\Bisection;
use Illuminate\Support\ServiceProvider;

class FinMathServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Ninguna de estas clases tiene estado, así que van como singleton
        $this->app->singleton(RateConverter::class);
        $this->app->singleton(ExchangeRateConverter::class);
        $this->app->singleton(PeriodResolver::class);

        $this->app->singleton(Bisection::class, fn () => new Bisection(
            tolerance: config('finmath.tolerance'),
            maxIterations: config('finmath.max_iterations'),
        ));

        $this->app->singleton(ScheduleFactory::class, fn ($app) => new ScheduleFactory(
            $app->make(RateConverter::class),
            $app->make(ExchangeRateConverter::class),
        ));

        $this->app->singleton(EquationBuilder::class);
    }
}
