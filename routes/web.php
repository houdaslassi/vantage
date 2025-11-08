<?php

use Illuminate\Support\Facades\Route;
use houdaslassi\Vantage\Http\Controllers\QueueMonitorController;
use houdaslassi\Vantage\Http\Middleware\AuthorizeVantage;

Route::prefix('vantage')->name('vantage.')->middleware(['web', AuthorizeVantage::class])->group(function () {
    Route::get('/', [QueueMonitorController::class, 'index'])->name('dashboard');
    Route::get('/jobs', [QueueMonitorController::class, 'jobs'])->name('jobs');
    Route::get('/jobs/{id}', [QueueMonitorController::class, 'show'])->name('jobs.show');
    Route::get('/tags', [QueueMonitorController::class, 'tags'])->name('tags');
    Route::get('/failed', [QueueMonitorController::class, 'failed'])->name('failed');
    Route::post('/jobs/{id}/retry', [QueueMonitorController::class, 'retry'])->name('jobs.retry');
});

