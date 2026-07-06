<?php

use App\Http\Controllers\Api\Mobile\ActivationController;
use App\Http\Controllers\Api\Mobile\AuthController;
use App\Http\Controllers\Api\Mobile\CheckpointController;
use App\Http\Controllers\Api\Mobile\PilgrimController;
use App\Http\Controllers\Api\Mobile\ProfileController;
use App\Http\Controllers\Api\Mobile\StaffGroupController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->group(function () {
    Route::post('/activation/claim', [ActivationController::class, 'claim'])
        ->middleware('throttle:5,1')
        ->name('api.mobile.activation.claim');
    Route::post('/activation/status', [ActivationController::class, 'status'])
        ->middleware('throttle:60,1')
        ->name('api.mobile.activation.status');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1')
        ->name('api.mobile.login');

    Route::middleware(['auth:sanctum', 'mobile.role:jamaah,tour-leader,muthawwif'])->group(function () {
        Route::get('/profile', [ProfileController::class, 'show'])->name('api.mobile.profile');
        Route::post('/device-token', [ProfileController::class, 'registerDeviceToken'])->name('api.mobile.device-token');
        Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('api.mobile.profile.photo');
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.mobile.logout');
        Route::get('/checkpoints', [CheckpointController::class, 'index'])->name('api.mobile.checkpoints');
    });

    Route::middleware(['auth:sanctum', 'mobile.role:jamaah'])->group(function () {
        Route::post('/send-location', [PilgrimController::class, 'sendLocation'])->name('api.mobile.pilgrim.location');
        Route::post('/sos', [PilgrimController::class, 'sos'])->name('api.mobile.pilgrim.sos');
        Route::get('/hotel', [PilgrimController::class, 'hotel'])->name('api.mobile.pilgrim.hotel');
        Route::get('/muthawwif-location', [PilgrimController::class, 'muthawwifLocation'])->name('api.mobile.pilgrim.muthawwif');
        Route::get('/my-location-history', [PilgrimController::class, 'history'])->name('api.mobile.pilgrim.history');
    });

    Route::middleware(['auth:sanctum', 'mobile.role:tour-leader'])->group(function () {
        Route::get('/group-pilgrims', [StaffGroupController::class, 'leaderPilgrims'])->name('api.mobile.leader.pilgrims');
        Route::get('/group-locations', [StaffGroupController::class, 'leaderLocations'])->name('api.mobile.leader.locations');
        Route::get('/group-sos', [StaffGroupController::class, 'leaderSos'])->name('api.mobile.leader.sos');
        Route::get('/group-hotels', [StaffGroupController::class, 'leaderHotels'])->name('api.mobile.leader.hotels');
        Route::post('/group-sos/{sosReport}/resolve', [StaffGroupController::class, 'leaderResolveSos'])->name('api.mobile.leader.sos.resolve');
        Route::get('/activation-pilgrims', [ActivationController::class, 'pilgrims'])->name('api.mobile.activation.pilgrims');
        Route::get('/activation-requests', [ActivationController::class, 'pending'])->name('api.mobile.activation.pending');
        Route::post('/activation-requests/{session:public_id}/approve', [ActivationController::class, 'approve'])->name('api.mobile.activation.approve');
    });

    Route::middleware(['auth:sanctum', 'mobile.role:muthawwif'])->group(function () {
        Route::get('/assigned-pilgrims', [StaffGroupController::class, 'muthawwifPilgrims'])->name('api.mobile.muthawwif.pilgrims');
        Route::get('/assigned-locations', [StaffGroupController::class, 'muthawwifLocations'])->name('api.mobile.muthawwif.locations');
        Route::get('/assigned-sos', [StaffGroupController::class, 'muthawwifSos'])->name('api.mobile.muthawwif.sos');
        Route::get('/assigned-hotels', [StaffGroupController::class, 'muthawwifHotels'])->name('api.mobile.muthawwif.hotels');
        Route::post('/assigned-sos/{sosReport}/resolve', [StaffGroupController::class, 'muthawwifResolveSos'])->name('api.mobile.muthawwif.sos.resolve');
    });
});
