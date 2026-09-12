<?php

use App\Http\Controllers\AmortizationController;
use App\Http\Controllers\RateController;
use App\Http\Controllers\ValueEquationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Home'))->name('home');

Route::prefix('tasas')->name('rates.')->group(function () {
    Route::get('/', [RateController::class, 'form'])->name('form');
    Route::post('/', [RateController::class, 'convert'])->name('convert');
});

Route::prefix('amortizacion')->name('amortization.')->group(function () {
    Route::get('/', [AmortizationController::class, 'create'])->name('create');
    Route::post('/', [AmortizationController::class, 'store'])->name('store');
});

Route::prefix('ecuaciones')->name('equations.')->group(function () {
    Route::get('/', [ValueEquationController::class, 'create'])->name('create');
    Route::post('/', [ValueEquationController::class, 'solve'])->name('solve');
});
