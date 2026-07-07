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
use App\Http\Controllers\GeoController;
use App\Http\Controllers\OccupancyApplicationController;
use App\Http\Controllers\PermitController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\FeeScheduleController;
use App\Http\Controllers\OnlineApplicationController;
use App\Http\Controllers\ZoningController;
use App\Http\Controllers\ZoningFeeController;
use App\Http\Controllers\MechInspFeeController;
use App\Http\Controllers\AccessoryFeeController;
use App\Http\Controllers\AccFeeController;
use App\Http\Controllers\SurchargeFeeController;
use App\Http\Controllers\ElectronicsFeeController;
use App\Http\Controllers\PlumbingFeeController;
use App\Http\Controllers\VerifyController;
use Illuminate\Support\Facades\Route;

// Default page = client login
Route::get('/', fn () => auth()->check()
    ? (auth()->user()->hasRole('client') ? redirect()->route('online.dashboard') : redirect()->route('dashboard'))
    : redirect()->route('login'));

// Client auth (guest only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');

    // Staff auth (separate portal)
    Route::get('/staff/login', [LoginController::class, 'showStaffLoginForm'])->name('staff.login');
    Route::post('/staff/login', [LoginController::class, 'staffLogin'])->name('staff.login.submit');
});

// Public permit verification (QR code target — no auth required)
Route::middleware('throttle:30,1')->get('/verify/permit/{token}', [VerifyController::class, 'show'])->name('verify.permit');

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

    // Geo lookups (AJAX, on-demand barangays for the address cascading dropdowns)
    Route::get('/geo/barangays/{city}', [GeoController::class, 'barangaysForCity'])->name('geo.barangays');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Building Permit Applications (BP)
    Route::prefix('applications')->name('applications.')->group(function () {
        Route::get('/', [ApplicationController::class, 'index'])->name('index')->middleware('can:view-applications');
        Route::get('/create', [ApplicationController::class, 'create'])->name('create')->middleware('can:create-applications');
        Route::post('/', [ApplicationController::class, 'store'])->name('store')->middleware('can:create-applications');
        Route::get('/{application}', [ApplicationController::class, 'show'])->name('show')->middleware('can:view-applications');
        Route::get('/{application}/edit', [ApplicationController::class, 'edit'])->name('edit')->middleware('can:edit-applications');
        Route::put('/{application}', [ApplicationController::class, 'update'])->name('update')->middleware('can:edit-applications');
        Route::post('/{application}/submit', [ApplicationController::class, 'submit'])->name('submit')->middleware('can:submit-applications');
        Route::post('/{application}/cancel', [ApplicationController::class, 'cancel'])->name('cancel')->middleware('can:cancel-applications');
        Route::post('/{application}/revert-submission', [ApplicationController::class, 'revertSubmission'])->name('revertSubmission')->middleware('can:revert-submission');
        Route::get('/{application}/print', [ApplicationController::class, 'printForm'])->name('print')->middleware('can:view-applications');
    });

    // Occupancy Permit Applications (OP)
    Route::prefix('occupancy-applications')->name('occupancy-applications.')->group(function () {
        Route::get('/', [OccupancyApplicationController::class, 'index'])->name('index')->middleware('can:view-applications');
        Route::get('/create', [OccupancyApplicationController::class, 'create'])->name('create')->middleware('can:create-applications');
        Route::post('/', [OccupancyApplicationController::class, 'store'])->name('store')->middleware('can:create-applications');
        Route::get('/{occupancyApplication}', [OccupancyApplicationController::class, 'show'])->name('show')->middleware('can:view-applications');
        Route::get('/{occupancyApplication}/edit', [OccupancyApplicationController::class, 'edit'])->name('edit')->middleware('can:edit-applications');
        Route::put('/{occupancyApplication}', [OccupancyApplicationController::class, 'update'])->name('update')->middleware('can:edit-applications');
        Route::post('/{occupancyApplication}/submit', [OccupancyApplicationController::class, 'submit'])->name('submit')->middleware('can:submit-applications');
        Route::post('/{occupancyApplication}/cancel', [OccupancyApplicationController::class, 'cancel'])->name('cancel')->middleware('can:cancel-applications');
        Route::post('/{occupancyApplication}/revert-submission', [OccupancyApplicationController::class, 'revertSubmission'])->name('revertSubmission')->middleware('can:revert-submission');
        Route::get('/{occupancyApplication}/print', [OccupancyApplicationController::class, 'printForm'])->name('print')->middleware('can:view-applications');
    });

    // Zoning Assessment (Planning Office) — BP only
    Route::prefix('zoning')->name('zoning.')->middleware('can:view-zoning')->group(function () {
        Route::get('/', [ZoningController::class, 'index'])->name('index');
        Route::get('/{application}', [ZoningController::class, 'assess'])->name('assess')->middleware('can:create-zoning');
        Route::post('/{application}', [ZoningController::class, 'store'])->name('store')->middleware('can:create-zoning');
        Route::post('/{application}/auto-compute', [ZoningController::class, 'autoCompute'])->name('autoCompute')->middleware('can:create-zoning');
        Route::post('/{application}/add-item', [ZoningController::class, 'addItem'])->name('addItem')->middleware('can:create-zoning');
        Route::delete('/item/{assessmentItem}', [ZoningController::class, 'removeItem'])->name('removeItem')->middleware('can:create-zoning');
        Route::delete('/{application}/remove-items', [ZoningController::class, 'removeItems'])->name('removeItems')->middleware('can:create-zoning');
        Route::post('/{application}/finalize', [ZoningController::class, 'finalize'])->name('finalize')->middleware('can:finalize-zoning');
        Route::post('/{application}/revert-finalize', [ZoningController::class, 'revertZoning'])->name('revertFinalize')->middleware('can:revert-zoning');
        Route::post('/{application}/send-back', [ZoningController::class, 'sendBackForEditing'])->name('sendBack')->middleware('can:revert-submission');
        Route::post('/{application}/skip', [ZoningController::class, 'skip'])->name('skip')->middleware('can:skip-zoning');
    });

    // Engineering Assessment
    Route::prefix('assessments')->name('assessments.')->middleware('can:view-assessments')->group(function () {
        Route::get('/', [AssessmentController::class, 'index'])->name('index');
        Route::get('/occupancy', [AssessmentController::class, 'occupancyIndex'])->name('occupancy');
        // BP assessment
        Route::get('/{application}', [AssessmentController::class, 'assess'])->name('assess')->middleware('can:create-assessments');
        Route::post('/{application}/item', [AssessmentController::class, 'addItem'])->name('addItem')->middleware('can:create-assessments');
        Route::post('/{application}/construction-item', [AssessmentController::class, 'addConstructionItem'])->name('constructionItem')->middleware('can:create-assessments');
        Route::post('/{application}/electrical-item', [AssessmentController::class, 'addElectricalItem'])->name('electricalItem')->middleware('can:create-assessments');
        Route::post('/{application}/mechanical-item', [AssessmentController::class, 'addMechanicalItem'])->name('mechanicalItem')->middleware('can:create-assessments');
        Route::post('/{application}/plumbing-item', [AssessmentController::class, 'addPlumbingItem'])->name('plumbingItem')->middleware('can:create-assessments');
        Route::post('/{application}/electronics-item', [AssessmentController::class, 'addElectronicsItem'])->name('electronicsItem')->middleware('can:create-assessments');
        Route::post('/{application}/accessory-item', [AssessmentController::class, 'addAccessoryItem'])->name('accessoryItem')->middleware('can:create-assessments');
        Route::post('/{application}/acc-fee-item', [AssessmentController::class, 'addAccFeeItem'])->name('accFeeItem')->middleware('can:create-assessments');
        Route::post('/{application}/surcharge-item', [AssessmentController::class, 'addSurchargeItem'])->name('surchargeItem')->middleware('can:create-assessments');
        Route::get('/{application}/summary', [AssessmentController::class, 'summary'])->name('summary');
        Route::post('/{application}/finalize', [AssessmentController::class, 'finalize'])->name('finalize')->middleware('can:finalize-assessments');
        Route::post('/{application}/revert-finalize', [AssessmentController::class, 'revertEngineering'])->name('revertFinalize')->middleware('can:revert-assessments');
        Route::post('/{application}/return-to-zoning', [AssessmentController::class, 'returnToZoning'])->name('returnToZoning')->middleware('can:return-to-zoning');
        Route::get('/{application}/print', [AssessmentController::class, 'print'])->name('print');
        // OP assessment
        Route::get('/op/{occupancyApplication}', [AssessmentController::class, 'assessOp'])->name('assess.op')->middleware('can:create-assessments');
        Route::post('/op/{occupancyApplication}/item', [AssessmentController::class, 'addItemOp'])->name('addItem.op')->middleware('can:create-assessments');
        Route::get('/op/{occupancyApplication}/summary', [AssessmentController::class, 'summaryOp'])->name('summary.op');
        Route::post('/op/{occupancyApplication}/finalize', [AssessmentController::class, 'finalizeOp'])->name('finalize.op')->middleware('can:finalize-assessments');
        Route::post('/op/{occupancyApplication}/revert-finalize', [AssessmentController::class, 'revertEngineeringOp'])->name('revertFinalize.op')->middleware('can:revert-assessments');
        Route::post('/op/{occupancyApplication}/revert-to-draft', [AssessmentController::class, 'revertToDraftOp'])->name('revertToDraft.op')->middleware('can:revert-submission');
        Route::get('/op/{occupancyApplication}/print', [AssessmentController::class, 'printOp'])->name('print.op');
        Route::post('/op/{occupancyApplication}/occupancy-fee', [AssessmentController::class, 'addOccupancyFeeItem'])->name('occupancyFeeItem')->middleware('can:create-assessments');
        // Shared
        Route::delete('/item/{assessmentItem}', [AssessmentController::class, 'removeItem'])->name('removeItem')->middleware('can:edit-assessments');
    });

    // Billing (auto-generated on assessment finalize; statement PDF only)
    Route::prefix('billing')->name('billing.')->middleware('can:view-billing')->group(function () {
        Route::get('/{billing}/print', [BillingController::class, 'print'])->name('print');
    });

    // Collections / Payments
    Route::prefix('collections')->name('collections.')->middleware('can:view-collections')->group(function () {
        Route::get('/', [CollectionController::class, 'index'])->name('index');
        // BP payment
        Route::get('/{application}/pay', [CollectionController::class, 'create'])->name('create')->middleware('can:create-collections');
        Route::post('/{application}/pay', [CollectionController::class, 'store'])->name('store')->middleware('can:create-collections');
        // OP payment
        Route::get('/op/{occupancyApplication}/pay', [CollectionController::class, 'createOp'])->name('create.op')->middleware('can:create-collections');
        Route::post('/op/{occupancyApplication}/pay', [CollectionController::class, 'storeOp'])->name('store.op')->middleware('can:create-collections');
        // Shared
        Route::get('/{collection}/receipt', [CollectionController::class, 'receipt'])->name('receipt')->middleware('can:print-receipts');
        Route::get('/void', [CollectionController::class, 'voidForm'])->name('void')->middleware('can:void-collections');
        Route::post('/void', [CollectionController::class, 'processVoid'])->name('void.process')->middleware('can:void-collections');
    });

    // Permit Generation
    Route::prefix('permits')->name('permits.')->middleware('can:view-permits')->group(function () {
        Route::get('/building', [PermitController::class, 'buildingIndex'])->name('building');
        Route::get('/occupancy', [PermitController::class, 'occupancyIndex'])->name('occupancy');
        // BP permit
        Route::post('/{application}/generate', [PermitController::class, 'generate'])->name('generate')->middleware('can:generate-permits');
        Route::post('/{application}/revert-generate', [PermitController::class, 'revertGenerate'])->name('revertGenerate')->middleware('can:revert-permits');
        Route::post('/{application}/restore-permit', [PermitController::class, 'restoreRevoke'])->name('restorePermit')->middleware('can:revert-permits');
        // OP permit
        Route::post('/op/{occupancyApplication}/generate', [PermitController::class, 'generateOp'])->name('generate.op')->middleware('can:generate-permits');
        Route::post('/op/{occupancyApplication}/revert-generate', [PermitController::class, 'revertGenerateOp'])->name('revertGenerate.op')->middleware('can:revert-permits');
        Route::post('/op/{occupancyApplication}/restore-permit', [PermitController::class, 'restoreRevokeOp'])->name('restorePermit.op')->middleware('can:revert-permits');
        // Shared
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
        Route::get('/zoning-fees', [ZoningFeeController::class, 'index'])->name('zoning-fees')->middleware('can:manage-fee-schedules');
        Route::put('/zoning-fees/{landUseAndZoningFee}', [ZoningFeeController::class, 'update'])->name('zoning-fees.update')->middleware('can:manage-fee-schedules');
        Route::put('/zoning-fees/cert/{certificationZoningFee}', [ZoningFeeController::class, 'updateCert'])->name('zoning-fees.updateCert')->middleware('can:manage-fee-schedules');
        Route::post('/zoning-fees/{occupancySubGroup}', [ZoningFeeController::class, 'store'])->name('zoning-fees.store')->middleware('can:manage-fee-schedules');
        Route::delete('/zoning-fees/{landUseAndZoningFee}', [ZoningFeeController::class, 'destroy'])->name('zoning-fees.destroy')->middleware('can:manage-fee-schedules');
        Route::put('/zoning-fees/other/{landUseAndZoningOtherFee}', [ZoningFeeController::class, 'updateOther'])->name('zoning-fees.updateOther')->middleware('can:manage-fee-schedules');

        Route::get('/mech-insp-fees', [MechInspFeeController::class, 'index'])->name('mech-insp-fees')->middleware('can:manage-fee-schedules');
        Route::put('/mech-insp-fees/schedule/{feeSchedule}', [MechInspFeeController::class, 'updateSchedule'])->name('mech-insp-fees.schedule.update')->middleware('can:manage-fee-schedules');
        Route::post('/mech-insp-fees/type/{feeType}/schedule', [MechInspFeeController::class, 'storeSchedule'])->name('mech-insp-fees.schedule.store')->middleware('can:manage-fee-schedules');
        Route::delete('/mech-insp-fees/schedule/{feeSchedule}', [MechInspFeeController::class, 'destroySchedule'])->name('mech-insp-fees.schedule.destroy')->middleware('can:manage-fee-schedules');

        Route::get('/electronics-fees', [ElectronicsFeeController::class, 'index'])->name('electronics-fees')->middleware('can:manage-fee-schedules');
        Route::put('/electronics-fees/schedule/{feeSchedule}', [ElectronicsFeeController::class, 'updateSchedule'])->name('electronics-fees.schedule.update')->middleware('can:manage-fee-schedules');
        Route::post('/electronics-fees/type/{feeType}/schedule', [ElectronicsFeeController::class, 'storeSchedule'])->name('electronics-fees.schedule.store')->middleware('can:manage-fee-schedules');
        Route::delete('/electronics-fees/schedule/{feeSchedule}', [ElectronicsFeeController::class, 'destroySchedule'])->name('electronics-fees.schedule.destroy')->middleware('can:manage-fee-schedules');

        Route::get('/accessory-fees', [AccessoryFeeController::class, 'index'])->name('accessory-fees')->middleware('can:manage-fee-schedules');
        Route::put('/accessory-fees/schedule/{feeSchedule}', [AccessoryFeeController::class, 'updateSchedule'])->name('accessory-fees.schedule.update')->middleware('can:manage-fee-schedules');
        Route::post('/accessory-fees/type/{feeType}/schedule', [AccessoryFeeController::class, 'storeSchedule'])->name('accessory-fees.schedule.store')->middleware('can:manage-fee-schedules');
        Route::delete('/accessory-fees/schedule/{feeSchedule}', [AccessoryFeeController::class, 'destroySchedule'])->name('accessory-fees.schedule.destroy')->middleware('can:manage-fee-schedules');

        Route::get('/acc-fees', [AccFeeController::class, 'index'])->name('acc-fees')->middleware('can:manage-fee-schedules');
        Route::put('/acc-fees/schedule/{feeSchedule}', [AccFeeController::class, 'updateSchedule'])->name('acc-fees.schedule.update')->middleware('can:manage-fee-schedules');
        Route::post('/acc-fees/type/{feeType}/schedule', [AccFeeController::class, 'storeSchedule'])->name('acc-fees.schedule.store')->middleware('can:manage-fee-schedules');
        Route::delete('/acc-fees/schedule/{feeSchedule}', [AccFeeController::class, 'destroySchedule'])->name('acc-fees.schedule.destroy')->middleware('can:manage-fee-schedules');

        Route::get('/surcharge-fees', [SurchargeFeeController::class, 'index'])->name('surcharge-fees')->middleware('can:manage-fee-schedules');
        Route::put('/surcharge-fees/schedule/{feeSchedule}', [SurchargeFeeController::class, 'updateSchedule'])->name('surcharge-fees.schedule.update')->middleware('can:manage-fee-schedules');
        Route::post('/surcharge-fees/type/{feeType}/schedule', [SurchargeFeeController::class, 'storeSchedule'])->name('surcharge-fees.schedule.store')->middleware('can:manage-fee-schedules');
        Route::delete('/surcharge-fees/schedule/{feeSchedule}', [SurchargeFeeController::class, 'destroySchedule'])->name('surcharge-fees.schedule.destroy')->middleware('can:manage-fee-schedules');

        Route::get('/plumbing-fees', [PlumbingFeeController::class, 'index'])->name('plumbing-fees')->middleware('can:manage-fee-schedules');
        Route::put('/plumbing-fees/schedule/{feeSchedule}', [PlumbingFeeController::class, 'updateSchedule'])->name('plumbing-fees.schedule.update')->middleware('can:manage-fee-schedules');
        Route::post('/plumbing-fees/type/{feeType}/schedule', [PlumbingFeeController::class, 'storeSchedule'])->name('plumbing-fees.schedule.store')->middleware('can:manage-fee-schedules');
        Route::delete('/plumbing-fees/schedule/{feeSchedule}', [PlumbingFeeController::class, 'destroySchedule'])->name('plumbing-fees.schedule.destroy')->middleware('can:manage-fee-schedules');

        Route::get('/signatories', [SettingsController::class, 'signatories'])->name('signatories')->middleware('can:manage-signatories');
        Route::post('/signatories/{signatory}', [SettingsController::class, 'updateSignatory'])->name('signatories.update')->middleware('can:manage-signatories');
    });

    // Online Application Portal (Clients)
    Route::prefix('online')->name('online.')->middleware('can:online-apply')->group(function () {
        Route::get('/dashboard', [OnlineApplicationController::class, 'dashboard'])->name('dashboard');
        Route::get('/apply', [OnlineApplicationController::class, 'create'])->name('apply');
        Route::post('/apply', [OnlineApplicationController::class, 'store'])->name('store');
        // BP application routes
        Route::get('/application/{application}', [OnlineApplicationController::class, 'show'])->name('show');
        Route::get('/application/{application}/upload', [OnlineApplicationController::class, 'uploadRequirements'])->name('upload');
        Route::post('/application/{application}/upload', [OnlineApplicationController::class, 'storeRequirement'])->name('upload.store');
        Route::get('/application/{application}/track', [OnlineApplicationController::class, 'track'])->name('track');
        Route::get('/application/{application}/download', [OnlineApplicationController::class, 'downloadPermit'])->name('download');
        // OP application routes
        Route::get('/application/op/{occupancyApplication}', [OnlineApplicationController::class, 'showOp'])->name('show.op');
        Route::get('/application/op/{occupancyApplication}/upload', [OnlineApplicationController::class, 'uploadRequirementsOp'])->name('upload.op');
        Route::post('/application/op/{occupancyApplication}/upload', [OnlineApplicationController::class, 'storeRequirementOp'])->name('upload.store.op');
        Route::get('/application/op/{occupancyApplication}/track', [OnlineApplicationController::class, 'trackOp'])->name('track.op');
        Route::get('/application/op/{occupancyApplication}/download', [OnlineApplicationController::class, 'downloadPermitOp'])->name('download.op');
    });
});

// Unknown URL fallback = home if logged in, login otherwise
Route::fallback(fn () => auth()->check()
    ? (auth()->user()->hasRole('client') ? redirect()->route('online.dashboard') : redirect()->route('dashboard'))
    : redirect()->route('login'));
