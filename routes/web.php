<?php

use Illuminate\Support\Facades\Route;
use IndieSystems\Notifications\Http\Controllers\NotificationController;

Route::middleware(config('indie-notifications.route_middleware', ['web', 'auth']))
    ->prefix(config('indie-notifications.route_prefix', 'notifications'))
    ->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/recent', [NotificationController::class, 'recent'])->name('notifications.recent');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
        Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    });
