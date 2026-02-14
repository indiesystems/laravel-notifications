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
        Route::delete('/bulk/read', [NotificationController::class, 'destroyRead'])->name('notifications.destroy-read');
        Route::delete('/bulk/all', [NotificationController::class, 'destroyAll'])->name('notifications.destroy-all');

        // Admin broadcast
        Route::get('/broadcast', [NotificationController::class, 'broadcastForm'])->name('notifications.broadcast');
        Route::post('/broadcast', [NotificationController::class, 'broadcastSend'])->name('notifications.broadcast.send');
    });
