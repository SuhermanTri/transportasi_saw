<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\User;
use App\Http\Controllers\Driver;

// Root redirect
Route::get('/', fn() => redirect()->route('login'));

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ─── NOTIFIKASI (all authenticated roles) ──────────────────────────
// Static routes MUST be declared before parameterised /{id} routes
Route::middleware('auth')->group(function () {
    Route::get( '/notifikasi',           [NotifikasiController::class, 'index'])->name('notifikasi.index');
    Route::get( '/notifikasi/count',     [NotifikasiController::class, 'unreadCount'])->name('notifikasi.count');
    Route::post('/notifikasi/read-all',  [NotifikasiController::class, 'markAllRead'])->name('notifikasi.read-all');
    Route::get( '/notifikasi/{id}/goto', [NotifikasiController::class, 'goto'])->name('notifikasi.goto');
    Route::post('/notifikasi/{id}/read', [NotifikasiController::class, 'markRead'])->name('notifikasi.read');
});

// ─── ADMIN / DISPATCHER ────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:dispatcher'])->group(function () {

    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

    // Manajemen User
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/',          [Admin\UserController::class, 'index'])->name('index');
        Route::get('/create',    [Admin\UserController::class, 'create'])->name('create');
        Route::post('/',         [Admin\UserController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [Admin\UserController::class, 'edit'])->name('edit');
        Route::put('/{id}',      [Admin\UserController::class, 'update'])->name('update');
        Route::delete('/{id}',   [Admin\UserController::class, 'destroy'])->name('destroy');
    });

    // Permintaan
    Route::prefix('permintaan')->name('permintaan.')->group(function () {
        Route::get('/',                  [Admin\PermintaanController::class, 'index'])->name('index');
        Route::get('/{id}',              [Admin\PermintaanController::class, 'show'])->name('show');
        Route::post('/{id}/approve',     [Admin\PermintaanController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject',      [Admin\PermintaanController::class, 'reject'])->name('reject');
        Route::post('/{id}/reschedule',  [Admin\PermintaanController::class, 'reschedule'])->name('reschedule');
        // Fitur 3: ubah priority
        Route::post('/{id}/priority',    [Admin\PermintaanController::class, 'updatePriority'])->name('priority');
    });

    // Kendaraan
    Route::prefix('kendaraan')->name('kendaraan.')->group(function () {
        Route::get('/',          [Admin\KendaraanController::class, 'index'])->name('index');
        Route::get('/create',    [Admin\KendaraanController::class, 'create'])->name('create');
        Route::post('/',         [Admin\KendaraanController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [Admin\KendaraanController::class, 'edit'])->name('edit');
        Route::put('/{id}',      [Admin\KendaraanController::class, 'update'])->name('update');
        Route::delete('/{id}',   [Admin\KendaraanController::class, 'destroy'])->name('destroy');
    });

    // AHP
    Route::prefix('ahp')->name('ahp.')->group(function () {
        Route::get('/',                        [Admin\AhpController::class, 'index'])->name('index');
        Route::post('/kriteria',               [Admin\AhpController::class, 'storeKriteria'])->name('kriteria.store');
        Route::delete('/kriteria/{id}',        [Admin\AhpController::class, 'deleteKriteria'])->name('kriteria.delete');
        Route::post('/perbandingan',           [Admin\AhpController::class, 'storePerbandingan'])->name('perbandingan.store');
        Route::delete('/perbandingan/{id}',    [Admin\AhpController::class, 'deletePerbandingan'])->name('perbandingan.delete');
        Route::post('/hitung',                 [Admin\AhpController::class, 'hitung'])->name('hitung');
    });

    // SAW
    Route::prefix('saw')->name('saw.')->group(function () {
        Route::get('/',        [Admin\SawController::class, 'index'])->name('index');
        Route::post('/hitung', [Admin\SawController::class, 'hitung'])->name('hitung');
    });

    // Penjadwalan
    Route::prefix('penjadwalan')->name('penjadwalan.')->group(function () {
        Route::get('/',                     [Admin\PenjadwalanController::class, 'index'])->name('index');
        Route::get('/create',               [Admin\PenjadwalanController::class, 'create'])->name('create');
        Route::get('/kendaraan-tersedia',   [Admin\PenjadwalanController::class, 'kendaraanTersedia'])->name('kendaraan-tersedia');
        Route::post('/',                    [Admin\PenjadwalanController::class, 'store'])->name('store');
        Route::get('/{id}/edit',            [Admin\PenjadwalanController::class, 'edit'])->name('edit');
        Route::put('/{id}',                 [Admin\PenjadwalanController::class, 'update'])->name('update');
        Route::delete('/{id}',              [Admin\PenjadwalanController::class, 'destroy'])->name('destroy');
    });

    // Laporan
    Route::prefix('laporan')->name('laporan.')->group(function () {
        Route::get('/',                      [Admin\LaporanController::class, 'index'])->name('index');
        Route::get('/permintaan/pdf',        [Admin\LaporanController::class, 'exportPermintaanPdf'])->name('permintaan.pdf');
        Route::get('/permintaan/excel',      [Admin\LaporanController::class, 'exportPermintaanExcel'])->name('permintaan.excel');
        Route::get('/jadwal/pdf',            [Admin\LaporanController::class, 'exportJadwalPdf'])->name('jadwal.pdf');
        Route::get('/jadwal/excel',          [Admin\LaporanController::class, 'exportJadwalExcel'])->name('jadwal.excel');
    });
});

// ─── USER / PEGAWAI ────────────────────────────────────────────────
Route::prefix('user')->name('user.')->middleware(['auth', 'role:user'])->group(function () {

    Route::get('/dashboard', [User\DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('permintaan')->name('permintaan.')->group(function () {
        Route::get('/',          [User\PermintaanController::class, 'index'])->name('index');
        Route::get('/create',    [User\PermintaanController::class, 'create'])->name('create');
        Route::post('/',         [User\PermintaanController::class, 'store'])->name('store');
        Route::get('/{id}',      [User\PermintaanController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [User\PermintaanController::class, 'edit'])->name('edit');
        Route::put('/{id}',      [User\PermintaanController::class, 'update'])->name('update');
        Route::delete('/{id}',   [User\PermintaanController::class, 'destroy'])->name('destroy');
        // Fitur 1: cancel booking (terjadwal → dibatalkan_user)
        Route::get('/{id}/cancel',          [User\PermintaanController::class, 'cancelForm'])->name('cancel');
        Route::post('/{id}/cancel',         [User\PermintaanController::class, 'cancelStore'])->name('cancel.confirm');
        // Fitur 2: reschedule booking (disetujui/terjadwal → tanggal baru)
        Route::get('/{id}/reschedule',      [User\PermintaanController::class, 'rescheduleForm'])->name('reschedule');
        Route::post('/{id}/reschedule',     [User\PermintaanController::class, 'rescheduleStore'])->name('reschedule.store');
    });

    // Penilaian driver
    Route::get('/penilaian/{jadwal_id}/create', [User\PenilaianController::class, 'create'])->name('penilaian.create');
    Route::post('/penilaian/{jadwal_id}',        [User\PenilaianController::class, 'store'])->name('penilaian.store');

    Route::get('/jadwal', [User\JadwalController::class, 'index'])->name('jadwal');
});

// ─── DRIVER ────────────────────────────────────────────────────────
Route::prefix('driver')->name('driver.')->middleware(['auth', 'role:driver'])->group(function () {

    Route::get('/dashboard',    [Driver\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/jadwal',       [Driver\JadwalController::class, 'jadwalSaya'])->name('jadwal');
    Route::get('/jadwal/{id}',  [Driver\JadwalController::class, 'show'])->name('jadwal.show');
    Route::post('/jadwal/{id}/selesai', [Driver\JadwalController::class, 'selesai'])->name('jadwal.selesai');
    Route::get('/riwayat',      [Driver\JadwalController::class, 'riwayat'])->name('riwayat');
    Route::post('/status',      [Driver\JadwalController::class, 'updateStatus'])->name('status.update');
});
