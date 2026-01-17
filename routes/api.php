<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Customer\QueueController as CustomerQueueController;
use App\Http\Controllers\Api\Customer\InfoController;
use App\Http\Controllers\Api\Staff\QueueController as StaffQueueController;
use App\Http\Controllers\Api\Staff\DashboardController as StaffDashboardController;
use App\Http\Controllers\Api\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\Admin\PolyController;
use App\Http\Controllers\Api\Admin\DoctorController;
use App\Http\Controllers\Api\Admin\ScheduleController;
use App\Http\Controllers\Api\Admin\QueueTypeController;
use App\Http\Controllers\Api\Admin\StaffController;
use App\Http\Controllers\Api\Admin\ReportController;
use App\Http\Controllers\Api\Admin\PolyServiceHourController;
use App\Http\Controllers\Api\Admin\SystemSettingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes (no authentication required)
Route::prefix('v1')->group(function () {

    Route::get('/test-ai-prediction', function () {
        $predictor = app(\App\Services\AI\QueueWaitTimePredictor::class);

        return $predictor->predict(
            queueTypeId: 1,
            currentQueueNumber: 10,
            targetQueueNumber: 45,
            serviceDate: today()
        );
    });

    // Authentication
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
    });

    // Customer - Public Queue & Info
    Route::prefix('customer')->group(function () {
        // Queue management
        Route::post('queue/take', [CustomerQueueController::class, 'takeQueue']);
        Route::get('queue/status/{token}', [CustomerQueueController::class, 'getStatus']);
        Route::post('queue/cancel/{token}', [CustomerQueueController::class, 'cancelQueue']);

        // Information
        Route::get('info/polys', [InfoController::class, 'getPolys']);
        Route::get('info/doctors', [InfoController::class, 'getDoctorSchedules']);
        Route::get('info/queue-types', [InfoController::class, 'getQueueTypes']);
    });
});

// Protected routes (requires authentication)
Route::prefix('v1')->middleware(['jwt.auth'])->group(function () {

    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });

    // Staff routes
    Route::prefix('staff')->middleware(['role:staff'])->group(function () {
        Route::get('dashboard', [StaffDashboardController::class, 'index']);

        Route::prefix('queue')->group(function () {
            Route::get('today', [StaffQueueController::class, 'getTodayQueues']);
            Route::get('skipped', [StaffQueueController::class, 'getSkippedQueues']);
            Route::post('call-next', [StaffQueueController::class, 'callNext']);
            Route::post('{id}/recall', [StaffQueueController::class, 'recall']);
            Route::post('{id}/recall-skipped', [StaffQueueController::class, 'recallSkipped']);
            Route::post('{id}/skip', [StaffQueueController::class, 'skip']);
            Route::post('{id}/start-service', [StaffQueueController::class, 'startService']);
            Route::post('{id}/finish-service', [StaffQueueController::class, 'finishService']);
        });
    });

    // Admin routes
    Route::prefix('admin')->middleware(['role:admin'])->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index']);

        // Master data management
        Route::apiResource('polys', PolyController::class);
        Route::apiResource('doctors', DoctorController::class);
        Route::apiResource('queue-types', QueueTypeController::class);
        Route::apiResource('staff', StaffController::class);

        // Schedules
        Route::prefix('schedules')->group(function () {
            Route::post('/', [ScheduleController::class, 'store']);
            Route::put('{id}', [ScheduleController::class, 'update']);
            Route::delete('{id}', [ScheduleController::class, 'destroy']);
        });

        // Poly Service Hours
        Route::prefix('poly-service-hours')->group(function () {
            Route::post('/', [PolyServiceHourController::class, 'store']);
            Route::put('{id}', [PolyServiceHourController::class, 'update']);
            Route::delete('{id}', [PolyServiceHourController::class, 'destroy']);
        });

        // Reports
        Route::prefix('reports')->group(function () {
            Route::get('statistics', [ReportController::class, 'queueStatistics']);
            Route::get('busiest-polys', [ReportController::class, 'busiestPolys']);
            Route::get('busiest-hours', [ReportController::class, 'busiestHours']);
            Route::get('daily-count', [ReportController::class, 'dailyQueueCount']);
            Route::get('waiting-time-trend', [ReportController::class, 'waitingTimeTrend']);
        });

        // System Settings
        Route::prefix('system-settings')->group(function () {
            Route::get('/', [SystemSettingController::class, 'index']);
            Route::get('{key}', [SystemSettingController::class, 'show']);
            Route::put('/', [SystemSettingController::class, 'update']);
        });
    });
});