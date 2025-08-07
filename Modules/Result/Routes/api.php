<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Result\Http\Controllers\ResultController;

Route::middleware('auth:api-staff')->group(function () {
    Route::group(["prefix" => "results"], function () {
        Route::post('/compute', [ResultController::class, 'computeResults'])->middleware('permission:can_compute_results');
        Route::post('/compute_individual', [ResultController::class, 'computeIndividualResult'])->middleware('permission:can_compute_results');
        Route::get('/students_for_computation', [ResultController::class, 'getStudentsForComputation'])->middleware('permission:can_view_results');
        Route::get('/students_with_results', [ResultController::class, 'getStudentsWithResults'])->middleware('permission:can_view_results');
        Route::post('/save_batch', [ResultController::class, 'saveBatchResults'])->middleware('permission:can_input_results');
        Route::post('/bulk_upload', [ResultController::class, 'bulkUploadResults'])->middleware('permission:can_input_results');

        // Enhanced Result Management
        Route::post('/compile-advanced', [ResultController::class, 'compileAdvancedResults'])->middleware('permission:can_compute_results');
        Route::get('/semester-gpa', [ResultController::class, 'getStudentSemesterGpa'])->middleware('permission:can_view_results');
        Route::get('/compilation-logs', [ResultController::class, 'getResultCompilationLogs'])->middleware('permission:can_view_results');

        // Grade Settings Management (temporarily without permission middleware)
        Route::get('/grade-settings', [ResultController::class, 'getGradeSettings']);
        Route::get('/grade-settings/general', [ResultController::class, 'getGeneralGradeSettings']);
        Route::post('/grade-settings', [ResultController::class, 'saveGradeSetting']);
    });
});
