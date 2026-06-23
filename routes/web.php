<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PermitController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\FeeScheduleController;
use App\Http\Controllers\OnlineApplicationController;
use App\Http\Controllers\ZoningController;
use Illuminate\Support\Facades\Route;

// Redirect root to login or dashboard
Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'));

// Auth routes (guest only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::get('/change-password', [ChangePasswordController::class, 'showChangeForm'])->name('password.change');
    Route::post('/change-password', [ChangePasswordController::class, 'update'])->name('password.change.update');

    // Notifications
    Route::post('/notifications/mark-read', function () {
        auth()->user()->unreadNotifications->markAsRead();
        return back();
    })->name('notifications.markRead');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Applications
    Route::prefix('applications')->name('applications.')->group(function () {
        Route::get('/', [ApplicationController::class, 'index'])->name('index')->middleware('can:view-applications');
        Route::get('/create', [ApplicationController::class, 'create'])->name('create')->middleware('can:create-applications');
        Route::post('/', [ApplicationController::class, 'store'])->name('store')->middleware('can:create-applications');
        Route::get('/{application}', [ApplicationController::class, 'show'])->name('show')->middleware('can:view-applications');
        Route::get('/{application}/edit', [ApplicationController::class, 'edit'])->name('edit')->middleware('can:edit-applications');
        Route::put('/{application}', [ApplicationController::class, 'update'])->name('update')->middleware('can:edit-applications');
        Route::post('/{application}/submit', [ApplicationController::class, 'submit'])->name('submit')->middleware('can:submit-applications');
        Route::post('/{application}/cancel', [ApplicationController::class, 'cancel'])->name('cancel')->middleware('can:cancel-applications');
        Route::get('/{application}/print', [ApplicationController::class, 'printForm'])->name('print')->middleware('can:view-applications');
    });

    // Zoning Assessment (Planning Office)
    Route::prefix('zoning')->name('zoning.')->middleware('can:view-zoning')->group(function () {
        Route::get('/', [ZoningController::class, 'index'])->name('index');
        Route::get('/{application}', [ZoningController::class, 'assess'])->name('assess')->middleware('can:create-zoning');
        Route::post('/{application}', [ZoningController::class, 'store'])->name('store')->middleware('can:create-zoning');
        Route::post('/{application}/finalize', [ZoningController::class, 'finalize'])->name('finalize')->middleware('can:finalize-zoning');
        Route::post('/{application}/skip', [ZoningController::class, 'skip'])->name('skip')->middleware('can:skip-zoning');
    });

    // Engineering Assessment
    Route::prefix('assessments')->name('assessments.')->middleware('can:view-assessments')->group(function () {
        Route::get('/', [AssessmentController::class, 'index'])->name('index');
        Route::get('/occupancy', [AssessmentController::class, 'occupancyIndex'])->name('occupancy');
        Route::get('/{application}', [AssessmentController::class, 'assess'])->name('assess')->middleware('can:create-assessments');
        Route::post('/{application}/item', [AssessmentController::class, 'addItem'])->name('addItem')->middleware('can:create-assessments');
        Route::delete('/item/{assessmentItem}', [AssessmentController::class, 'removeItem'])->name('removeItem')->middleware('can:edit-assessments');
        Route::get('/{application}/summary', [AssessmentController::class, 'summary'])->name('summary');
        Route::post('/{application}/finalize', [AssessmentController::class, 'finalize'])->name('finalize')->middleware('can:finalize-assessments');
        Route::get('/{application}/print', [AssessmentController::class, 'print'])->name('print');
    });

    // Billing
    Route::prefix('billing')->name('billing.')->middleware('can:view-billing')->group(function () {
        Route::get('/', [BillingController::class, 'index'])->name('index');
        Route::post('/{application}/generate', [BillingController::class, 'generate'])->name('generate')->middleware('can:generate-billing');
        Route::get('/{billing}/print', [BillingController::class, 'print'])->name('print');
    });

    // Collections / Payments
    Route::prefix('collections')->name('collections.')->middleware('can:view-collections')->group(function () {
        Route::get('/', [CollectionController::class, 'index'])->name('index');
        Route::get('/{application}/pay', [CollectionController::class, 'create'])->name('create')->middleware('can:create-collections');
        Route::post('/{application}/pay', [CollectionController::class, 'store'])->name('store')->middleware('can:create-collections');
        Route::get('/{collection}/receipt', [CollectionController::class, 'receipt'])->name('receipt')->middleware('can:print-receipts');
        Route::get('/void', [CollectionController::class, 'voidForm'])->name('void')->middleware('can:void-collections');
        Route::post('/void', [CollectionController::class, 'processVoid'])->name('void.process')->middleware('can:void-collections');
    });

    // Permit Generation
    Route::prefix('permits')->name('permits.')->middleware('can:view-permits')->group(function () {
        Route::get('/building', [PermitController::class, 'buildingIndex'])->name('building');
        Route::get('/occupancy', [PermitController::class, 'occupancyIndex'])->name('occupancy');
        Route::post('/{application}/generate', [PermitController::class, 'generate'])->name('generate')->middleware('can:generate-permits');
        Route::get('/{permit}/print', [PermitController::class, 'print'])->name('print')->middleware('can:print-permits');
        Route::get('/{application}/zoning-cert', [PermitController::class, 'zoningCertification'])->name('zoningCert');
        Route::get('/{application}/locational', [PermitController::class, 'locationalClearance'])->name('locational');
        Route::get('/{application}/evaluation', [PermitController::class, 'evaluationReport'])->name('evaluation');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->middleware('can:view-reports')->group(function () {
        Route::get('/permits', [ReportController::class, 'permits'])->name('permits');
        Route::get('/revenue', [ReportController::class, 'revenue'])->name('revenue');
        Route::get('/collections', [ReportController::class, 'collections'])->name('collections');
        Route::post('/generate', [ReportController::class, 'generate'])->name('generate');
    });

    // Settings (Admin)
    Route::prefix('settings')->name('settings.')->middleware('can:manage-settings')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::post('/', [SettingsController::class, 'update'])->name('update');

        Route::get('/users', [SettingsController::class, 'users'])->name('users')->middleware('can:manage-users');
        Route::get('/users/create', [SettingsController::class, 'createUser'])->name('users.create')->middleware('can:manage-users');
        Route::post('/users', [SettingsController::class, 'storeUser'])->name('users.store')->middleware('can:manage-users');
        Route::get('/users/{user}/edit', [SettingsController::class, 'editUser'])->name('users.edit')->middleware('can:manage-users');
        Route::put('/users/{user}', [SettingsController::class, 'updateUser'])->name('users.update')->middleware('can:manage-users');
        Route::post('/users/{user}/toggle', [SettingsController::class, 'toggleUser'])->name('users.toggle')->middleware('can:manage-users');
        Route::post('/users/{user}/reset-password', [SettingsController::class, 'resetUserPassword'])->name('users.resetPassword')->middleware('can:manage-users');

        Route::get('/roles', [SettingsController::class, 'roles'])->name('roles')->middleware('can:manage-roles');
        Route::get('/fees', [SettingsController::class, 'fees'])->name('fees')->middleware('can:manage-fee-schedules');
        Route::get('/fees/category/{feeCategory}', [FeeScheduleController::class, 'showCategory'])->name('fees.category')->middleware('can:manage-fee-schedules');
        Route::get('/fees/type/{feeType}', [FeeScheduleController::class, 'showType'])->name('fees.type')->middleware('can:manage-fee-schedules');
        Route::post('/fees/type', [FeeScheduleController::class, 'storeType'])->name('fees.type.store')->middleware('can:manage-fee-schedules');
        Route::put('/fees/type/{feeType}', [FeeScheduleController::class, 'updateType'])->name('fees.type.update')->middleware('can:manage-fee-schedules');
        Route::post('/fees/type/{feeType}/schedule', [FeeScheduleController::class, 'storeSchedule'])->name('fees.schedule.store')->middleware('can:manage-fee-schedules');
        Route::put('/fees/schedule/{feeSchedule}', [FeeScheduleController::class, 'updateSchedule'])->name('fees.schedule.update')->middleware('can:manage-fee-schedules');
        Route::delete('/fees/schedule/{feeSchedule}', [FeeScheduleController::class, 'destroySchedule'])->name('fees.schedule.destroy')->middleware('can:manage-fee-schedules');
        Route::get('/signatories', [SettingsController::class, 'signatories'])->name('signatories')->middleware('can:manage-signatories');
        Route::post('/signatories/{signatory}', [SettingsController::class, 'updateSignatory'])->name('signatories.update')->middleware('can:manage-signatories');
    });

    // Online Application Portal (Clients)
    Route::prefix('online')->name('online.')->middleware('can:online-apply')->group(function () {
        Route::get('/dashboard', [OnlineApplicationController::class, 'dashboard'])->name('dashboard');
        Route::get('/apply', [OnlineApplicationController::class, 'create'])->name('apply');
        Route::post('/apply', [OnlineApplicationController::class, 'store'])->name('store');
        Route::get('/application/{application}', [OnlineApplicationController::class, 'show'])->name('show');
        Route::get('/application/{application}/upload', [OnlineApplicationController::class, 'uploadRequirements'])->name('upload');
        Route::post('/application/{application}/upload', [OnlineApplicationController::class, 'storeRequirement'])->name('upload.store');
        Route::get('/application/{application}/track', [OnlineApplicationController::class, 'track'])->name('track');
        Route::get('/application/{application}/download', [OnlineApplicationController::class, 'downloadPermit'])->name('download');
    });
});
