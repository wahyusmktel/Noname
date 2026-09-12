<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\LandingPageController;

// Halaman Utama Publik / Landing Page
Route::get('/', [LandingPageController::class, 'index'])->name('home');

// Guest Routes (Login & Wizard Registrasi Lembaga Bimbel)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

use App\Http\Controllers\InstitutionProfileController;

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Modul Lembaga Bimbel
    Route::prefix('lembaga')->name('institution.')->group(function () {
        Route::get('/profil', [InstitutionProfileController::class, 'show'])->name('profile');
        Route::match(['put', 'post'], '/profil', [InstitutionProfileController::class, 'update'])->name('profile.update');
    });

    // Modul Tentor (Guru Bimbel)
    Route::post('/tentors/generate-accounts', [\App\Http\Controllers\TentorController::class, 'generateAccounts'])->name('tentors.generate-accounts');
    Route::get('/tentors/export-accounts', [\App\Http\Controllers\TentorController::class, 'exportAccounts'])->name('tentors.export-accounts');
    Route::post('/tentors/{tentor}/reset-password', [\App\Http\Controllers\TentorController::class, 'resetPassword'])->name('tentors.reset-password');
    Route::resource('tentors', \App\Http\Controllers\TentorController::class)->except(['create', 'show', 'edit']);

    // Modul Mata Pelajaran
    Route::resource('subjects', \App\Http\Controllers\SubjectController::class)->except(['create', 'show', 'edit']);

    // Modul Peserta Didik
    Route::get('/students/export-template', [\App\Http\Controllers\StudentController::class, 'downloadTemplate'])->name('students.template');
    Route::post('/students/import-excel', [\App\Http\Controllers\StudentController::class, 'importExcel'])->name('students.import');
    Route::post('/students/copy-from-previous', [\App\Http\Controllers\StudentController::class, 'copyFromPreviousYear'])->name('students.copy');
    Route::post('/students/generate-accounts', [\App\Http\Controllers\StudentController::class, 'generateAccounts'])->name('students.generate-accounts');
    Route::get('/students/export-accounts', [\App\Http\Controllers\StudentController::class, 'exportAccounts'])->name('students.export-accounts');
    Route::post('/students/{student}/reset-password', [\App\Http\Controllers\StudentController::class, 'resetPassword'])->name('students.reset-password');
    Route::post('/students/{student}/toggle-status', [\App\Http\Controllers\StudentController::class, 'toggleStatus'])->name('students.toggle-status');
    Route::resource('students', \App\Http\Controllers\StudentController::class)->except(['create', 'show', 'edit']);

    // Modul Kelompok Bimbel
    Route::post('/study-groups/{studyGroup}/map-students', [\App\Http\Controllers\StudyGroupController::class, 'mapStudents'])->name('study-groups.map-students');
    Route::post('/study-groups/copy-from-previous', [\App\Http\Controllers\StudyGroupController::class, 'copyFromPreviousYear'])->name('study-groups.copy');
    Route::resource('study-groups', \App\Http\Controllers\StudyGroupController::class)->except(['create', 'show', 'edit']);

    // Modul Tahun Pelajaran
    Route::post('/academic-years/{academicYear}/set-active', [\App\Http\Controllers\AcademicYearController::class, 'setActive'])->name('academic-years.set-active');
    Route::resource('academic-years', \App\Http\Controllers\AcademicYearController::class)->except(['create', 'show', 'edit']);

    // Modul Pengguna Admin & Staff
    Route::post('/admin-users/{admin_user}/toggle-status', [\App\Http\Controllers\AdminUserController::class, 'toggleStatus'])->name('admin-users.toggle-status');
    Route::resource('admin-users', \App\Http\Controllers\AdminUserController::class)->except(['create', 'show', 'edit']);

    // Modul Presensi Manual (Solusi Darurat / Susulan untuk Guru Lupa Absen)
    Route::get('/attendance/manual', [\App\Http\Controllers\ManualAttendanceController::class, 'index'])->name('attendance.manual');
    Route::post('/attendance/manual', [\App\Http\Controllers\ManualAttendanceController::class, 'store'])->name('attendance.manual.store');
    Route::delete('/attendance/manual/{attendanceSession}', [\App\Http\Controllers\ManualAttendanceController::class, 'destroy'])->name('attendance.manual.destroy');

    // Modul Khusus Guru (Tutor) - Absensi Pertemuan
    Route::get('/tutor/attendance', [\App\Http\Controllers\TutorAttendanceController::class, 'create'])->name('tutor.attendance');
    Route::post('/tutor/attendance', [\App\Http\Controllers\TutorAttendanceController::class, 'store'])->name('tutor.attendance.store');

    // Modul Laporan & Rekapitulasi Kehadiran Peserta Didik
    Route::get('/reports/student-attendance', [\App\Http\Controllers\StudentAttendanceReportController::class, 'index'])->name('reports.student-attendance');
    Route::get('/reports/student-attendance/export', [\App\Http\Controllers\StudentAttendanceReportController::class, 'export'])->name('reports.student-attendance.export');

    // Modul Laporan & Rekapitulasi Kehadiran Guru (Tutor)
    Route::get('/reports/tutor-attendance', [\App\Http\Controllers\TutorAttendanceReportController::class, 'index'])->name('reports.tutor-attendance');
    Route::get('/reports/tutor-attendance/export', [\App\Http\Controllers\TutorAttendanceReportController::class, 'export'])->name('reports.tutor-attendance.export');

    // Modul Portal Monitoring Kehadiran Anak (Orang Tua / Siswa)
    Route::get('/student/dashboard', [\App\Http\Controllers\StudentPortalController::class, 'index'])->name('student.dashboard');

    // Modul Profil Pengguna (Khusus Ganti Foto Profil)
    Route::get('/user/profile', [\App\Http\Controllers\UserProfileController::class, 'show'])->name('user.profile');
    Route::post('/user/profile/photo', [\App\Http\Controllers\UserProfileController::class, 'updatePhoto'])->name('user.profile.photo');
    Route::delete('/user/profile/photo', [\App\Http\Controllers\UserProfileController::class, 'deletePhoto'])->name('user.profile.photo.delete');

    // Modul Pengaturan Akun & Keamanan (Ganti Password)
    Route::get('/user/settings', [\App\Http\Controllers\AccountSettingController::class, 'index'])->name('user.settings');
    Route::put('/user/settings/password', [\App\Http\Controllers\AccountSettingController::class, 'updatePassword'])->name('user.settings.password');

    // Modul Pusat Bantuan & Panduan untuk Setiap Role
    Route::get('/help-center', [\App\Http\Controllers\HelpCenterController::class, 'index'])->name('help.index');
});
