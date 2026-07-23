<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupMemberController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\MonitoringMapController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PilgrimPortalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationManagementController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SosReportController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\TrackingHistoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingPageController::class)->name('landing');
Route::get('/paket/{departure}', [LandingPageController::class, 'show'])->name('packages.show');
Route::redirect('/registrasi', '/daftar-jamaah')->name('public-registration.create');

Route::middleware('pilgrim.guest')->group(function () {
    Route::get('/daftar-jamaah', [PilgrimPortalController::class, 'register'])->name('portal.register');
    Route::post('/daftar-jamaah', [PilgrimPortalController::class, 'storeAccount'])->name('portal.register.store');
});
Route::redirect('/masuk-jamaah', '/login')->name('portal.login');

Route::prefix('jamaah')->middleware('pilgrim.portal')->name('portal.')->group(function () {
    Route::get('/', [PilgrimPortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/paket', [PilgrimPortalController::class, 'packages'])->name('packages.index');
    Route::get('/paket/{departure}', [PilgrimPortalController::class, 'showPackage'])->name('packages.show');
    Route::post('/paket/{departure}/pilih', [PilgrimPortalController::class, 'selectPackage'])->name('packages.select');
    Route::get('/biodata', [PilgrimPortalController::class, 'biodata'])->name('biodata.edit');
    Route::post('/biodata', [PilgrimPortalController::class, 'submitBiodata'])->name('biodata.store');
    Route::post('/keluar', [PilgrimPortalController::class, 'logout'])->name('logout');
});

// Semua route berikut adalah website admin. Middleware memastikan akun aktif
// dan hanya role Super Admin/Admin Cabang yang dapat mengaksesnya.
Route::middleware(['auth', 'active.account', 'role:super-admin|admin-cabang'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Monitoring operasional hanya menjadi tanggung jawab Admin Cabang.
    // Super Admin menerima ringkasan nasional dari dashboard dan laporan,
    // tanpa akses ke lokasi maupun histori individu jamaah.
    Route::middleware('role:admin-cabang')->group(function () {
        Route::get('/monitoring/live-map', [MonitoringMapController::class, 'index'])->name('monitoring.map.index');
        Route::get('/monitoring/live-map/data', [MonitoringMapController::class, 'data'])->name('monitoring.map.data');
        Route::get('/monitoring/tracking-history', [TrackingHistoryController::class, 'index'])->name('monitoring.tracking.index');
        Route::get('/monitoring/tracking-history/data', [TrackingHistoryController::class, 'data'])->name('monitoring.tracking.data');
        Route::get('/monitoring/sos', [SosReportController::class, 'index'])->name('monitoring.sos.index');
        Route::get('/monitoring/sos/{sosReport}', [SosReportController::class, 'show'])->name('monitoring.sos.show');
        Route::patch('/monitoring/sos/{sosReport}/resolve', [SosReportController::class, 'resolve'])->name('monitoring.sos.resolve');
        Route::post('/master-data/pilgrims/{pilgrim}/regenerate-pin', [MasterDataController::class, 'regeneratePin'])
            ->name('master-data.pilgrims.regenerate-pin');
        Route::post('/groups/{group}/reset-pins', [GroupMemberController::class, 'resetPins'])
            ->name('groups.reset-pins');
    });
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/registrations', [RegistrationManagementController::class, 'index'])->name('registrations.index');
    Route::patch('/registrations/{registration}', [RegistrationManagementController::class, 'update'])->name('registrations.update');
    Route::patch('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::delete('/notifications/delete-all', [NotificationController::class, 'destroyAll'])->name('notifications.destroy-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::redirect('/reports', '/reports/all')->name('reports.home');
    Route::get('/reports/{type}', [ReportController::class, 'index'])
        ->whereIn('type', ['all', 'pilgrims', 'tracking', 'sos'])
        ->name('reports.index');
    Route::get('/reports/{type}/download/{format}', [ReportController::class, 'download'])
        ->whereIn('type', ['all', 'pilgrims', 'tracking', 'sos'])
        ->whereIn('format', ['pdf', 'xlsx'])
        ->name('reports.download');
    Route::get('/groups/{group}/members', [GroupMemberController::class, 'index'])->name('groups.members.index');
    Route::patch('/groups/{group}/staff', [GroupMemberController::class, 'updateStaff'])->name('groups.staff.update');
    Route::post('/groups/{group}/members', [GroupMemberController::class, 'store'])->name('groups.members.store');
    Route::delete('/groups/{group}/members/{member}', [GroupMemberController::class, 'destroy'])->name('groups.members.destroy');
    Route::prefix('master-data/{resource}')
        ->whereIn('resource', ['branches', 'branch-admins', 'pilgrims', 'tour-leaders', 'muthawwifs', 'groups', 'checkpoints', 'departures', 'hotels'])
        ->name('master-data.')
        ->controller(MasterDataController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::get('/template', 'template')->name('template');
            Route::post('/import', 'import')->name('import');
            Route::post('/', 'store')->name('store');
            Route::get('/{record}/edit', 'edit')->whereNumber('record')->name('edit');
            Route::put('/{record}', 'update')->whereNumber('record')->name('update');
            Route::delete('/{record}', 'destroy')->whereNumber('record')->name('destroy');
        });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/settings/password', [ProfileController::class, 'password'])->name('settings.password');
    Route::get('/settings/system', [SystemSettingController::class, 'edit'])->name('settings.system.edit');
    Route::put('/settings/system', [SystemSettingController::class, 'update'])->name('settings.system.update');
});

require __DIR__.'/auth.php';
