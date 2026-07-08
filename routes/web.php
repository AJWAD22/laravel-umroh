<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupMemberController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\MonitoringMapController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\TrackingHistoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth', 'active.account', 'role:super-admin|admin-cabang'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/monitoring/live-map', [MonitoringMapController::class, 'index'])->name('monitoring.map.index');
    Route::get('/monitoring/live-map/data', [MonitoringMapController::class, 'data'])->name('monitoring.map.data');
    Route::get('/monitoring/tracking-history', [TrackingHistoryController::class, 'index'])->name('monitoring.tracking.index');
    Route::get('/monitoring/tracking-history/data', [TrackingHistoryController::class, 'data'])->name('monitoring.tracking.data');
    Route::redirect('/monitoring/sos', '/notifications')->name('monitoring.sos.index');
    Route::redirect('/monitoring/sos/{sosReport}', '/notifications')->name('monitoring.sos.show');
    Route::redirect('/monitoring/sos/{sosReport}/resolve', '/notifications')->name('monitoring.sos.resolve');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::delete('/notifications/delete-all', [NotificationController::class, 'destroyAll'])->name('notifications.destroy-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::redirect('/reports', '/reports/pilgrims')->name('reports.home');
    Route::get('/reports/{type}', [ReportController::class, 'index'])
        ->whereIn('type', ['pilgrims', 'tracking'])
        ->name('reports.index');
    Route::get('/reports/{type}/download/{format}', [ReportController::class, 'download'])
        ->whereIn('type', ['pilgrims', 'tracking'])
        ->whereIn('format', ['pdf', 'xlsx'])
        ->name('reports.download');
    Route::get('/groups/{group}/members', [GroupMemberController::class, 'index'])->name('groups.members.index');
    Route::patch('/groups/{group}/staff', [GroupMemberController::class, 'updateStaff'])->name('groups.staff.update');
    Route::post('/groups/{group}/members', [GroupMemberController::class, 'store'])->name('groups.members.store');
    Route::delete('/groups/{group}/members/{member}', [GroupMemberController::class, 'destroy'])->name('groups.members.destroy');
    Route::post('/master-data/pilgrims/{pilgrim}/regenerate-pin', [MasterDataController::class, 'regeneratePin'])
        ->name('master-data.pilgrims.regenerate-pin');
    Route::prefix('master-data/{resource}')
        ->whereIn('resource', ['branches', 'branch-admins', 'pilgrims', 'tour-leaders', 'muthawwifs', 'groups'])
        ->name('master-data.')
        ->controller(MasterDataController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{record}/edit', 'edit')->whereNumber('record')->name('edit');
            Route::put('/{record}', 'update')->whereNumber('record')->name('update');
            Route::delete('/{record}', 'destroy')->whereNumber('record')->name('destroy');
        });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/settings/password', [ProfileController::class, 'password'])->name('settings.password');
    Route::get('/settings/system', [SystemSettingController::class, 'edit'])->name('settings.system.edit');
    Route::put('/settings/system', [SystemSettingController::class, 'update'])->name('settings.system.update');
});

require __DIR__.'/auth.php';
