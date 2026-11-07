<?php

use App\Http\Controllers\Dataset\DatasetExportController;
use App\Http\Controllers\Dataset\DatasetHubController;
use App\Http\Controllers\Dataset\DatasetImportController;
use App\Http\Controllers\Dataset\DatasetTemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['role:admin,teacher', 'throttle:60,1'])->group(function () {
    Route::get('/dataset', [DatasetHubController::class, 'index'])->name('dataset.index');
    Route::get('/dataset/preview', [DatasetImportController::class, 'showPreview'])->name('dataset.preview.show');
    Route::post('/dataset/preview', [DatasetImportController::class, 'preview'])->name('dataset.preview');
    Route::post('/dataset/commit', [DatasetImportController::class, 'commit'])->name('dataset.commit');
    Route::post('/dataset/cancel', [DatasetImportController::class, 'cancel'])->name('dataset.cancel');
    Route::get('/dataset/templates/{module}', [DatasetTemplateController::class, 'download'])->name('dataset.template');
    Route::get('/dataset/export/{module}', [DatasetExportController::class, 'download'])->name('dataset.export');
});

