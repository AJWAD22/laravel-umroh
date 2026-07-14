<?php

use App\Http\Controllers\Api\Mobile\ActivationController;
use App\Http\Controllers\Api\Mobile\AuthController;
use App\Http\Controllers\Api\Mobile\CheckpointController;
use App\Http\Controllers\Api\Mobile\PilgrimController;
use App\Http\Controllers\Api\Mobile\ProfileController;
use App\Http\Controllers\Api\Mobile\StaffGroupController;
use Illuminate\Support\Facades\Route;

// Endpoint dengan prefix /api/mobile dipakai oleh aplikasi Flutter.
// Endpoint publik hanya untuk aktivasi PIN dan login; endpoint lainnya
// dilindungi Sanctum serta pemeriksaan role mobile.
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

    // Setelah login, token Bearer dikirim oleh ApiClient pada setiap request.
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
        Route::get('/staff-locations', [PilgrimController::class, 'staffLocations'])->name('api.mobile.pilgrim.staff-locations');
        Route::get('/my-location-history', [PilgrimController::class, 'history'])->name('api.mobile.pilgrim.history');
    });

    Route::middleware(['auth:sanctum', 'mobile.role:tour-leader,muthawwif'])->group(function () {
        Route::post('/staff-location', [StaffGroupController::class, 'sendLocation'])->name('api.mobile.staff.location');
        Route::post('/staff-checkpoints', [StaffGroupController::class, 'storeCheckpoint'])->name('api.mobile.staff.checkpoints.store');
        Route::get('/sos-reports', [StaffGroupController::class, 'sosReports'])->name('api.mobile.staff.sos');
        Route::post('/sos-reports/{sosReport}/acknowledge', [StaffGroupController::class, 'acknowledge'])->name('api.mobile.staff.sos.acknowledge');
        Route::post('/sos-reports/{sosReport}/resolve', [StaffGroupController::class, 'resolve'])->name('api.mobile.staff.sos.resolve');
    });

    Route::middleware(['auth:sanctum', 'mobile.role:tour-leader'])->group(function () {
        Route::get('/group-pilgrims', [StaffGroupController::class, 'leaderPilgrims'])->name('api.mobile.leader.pilgrims');
        Route::get('/group-locations', [StaffGroupController::class, 'leaderLocations'])->name('api.mobile.leader.locations');
        Route::get('/group-hotels', [StaffGroupController::class, 'leaderHotels'])->name('api.mobile.leader.hotels');
        Route::get('/activation-pilgrims', [ActivationController::class, 'pilgrims'])->name('api.mobile.activation.pilgrims');
        Route::get('/activation-requests', [ActivationController::class, 'pending'])->name('api.mobile.activation.pending');
        Route::post('/activation-requests/{session:public_id}/approve', [ActivationController::class, 'approve'])->name('api.mobile.activation.approve');
    });

    Route::middleware(['auth:sanctum', 'mobile.role:muthawwif'])->group(function () {
        Route::get('/assigned-pilgrims', [StaffGroupController::class, 'muthawwifPilgrims'])->name('api.mobile.muthawwif.pilgrims');
        Route::get('/assigned-locations', [StaffGroupController::class, 'muthawwifLocations'])->name('api.mobile.muthawwif.locations');
        Route::get('/assigned-hotels', [StaffGroupController::class, 'muthawwifHotels'])->name('api.mobile.muthawwif.hotels');
    });
});
