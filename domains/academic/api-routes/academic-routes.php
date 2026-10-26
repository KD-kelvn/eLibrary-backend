<?php

use Illuminate\Support\Facades\Route;
use Modules\Academic\Http\Controllers\CourseController;
use Modules\Academic\Http\Controllers\DepartmentController;
use Modules\Academic\Http\Controllers\FacultyController;
use Modules\Academic\Http\Controllers\ProgrammeController;
use Modules\Authentication\Http\Middleware\EnsureUserIsNotBlocked;

Route::middleware(['auth:sanctum', EnsureUserIsNotBlocked::class, 'permission'])->group(function () {
    Route::apiResource('faculties', FacultyController::class);
    Route::apiResource('departments', DepartmentController::class);
    Route::apiResource('programmes', ProgrammeController::class);
    Route::apiResource('courses', CourseController::class);
});
