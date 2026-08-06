<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FindingController;
use App\Http\Controllers\Api\RepositoryController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ScanController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:6,1');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:6,1');
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware('signed:relative')
    ->name('verification.verify');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/email/verification-notification', [AuthController::class, 'sendVerificationNotification'])
        ->middleware('throttle:6,1');
});

Route::middleware(['auth:sanctum', 'verified'])->group(function (): void {

    Route::apiResource('repositories', RepositoryController::class);
    Route::put('/repositories/{repository}/schedule', [RepositoryController::class, 'updateSchedule']);
    Route::post('/repositories/{repository}/scans', [ScanController::class, 'store']);
    Route::get('/repositories/{repository}/scans', [ScanController::class, 'indexByRepository']);
    Route::get('/repositories/{repository}/analytics', [AnalyticsController::class, 'showRepositoryAnalytics']);
    Route::get('/repositories/{repository}/security-trend', [AnalyticsController::class, 'securityTrend']);
    Route::get('/repositories/{repository}/secret-trend', [AnalyticsController::class, 'secretTrend']);
    Route::get('/repositories/{repository}/dependency-trend', [AnalyticsController::class, 'dependencyTrend']);
    Route::get('/repositories/{repository}/quality-trend', [AnalyticsController::class, 'qualityTrend']);

    Route::get('/scans/{scan}', [ScanController::class, 'show']);
    Route::get('/scans/{scan}/findings', [ScanController::class, 'findings']);
    Route::get('/scans/{scan}/report', [ReportController::class, 'show']);
    Route::post('/scans/{scan}/report', [ReportController::class, 'store']);

    Route::get('/findings/{finding}', [FindingController::class, 'show']);
    Route::get('/findings/{finding}/recommendation', [FindingController::class, 'recommendation']);
    Route::post('/findings/{finding}/generate-recommendation', [FindingController::class, 'generateRecommendation']);

    Route::get('/reports', [ReportController::class, 'index']);
    Route::get('/reports/{report}/download', [ReportController::class, 'download']);
});
