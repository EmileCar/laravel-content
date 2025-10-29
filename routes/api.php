<?php

use Illuminate\Support\Facades\Route;
use Carone\Content\Http\Controllers\PageContentController;

Route::group([], function () {
    // Pages resource routes
    Route::get('/pages', [PageContentController::class, 'index'])->name('content.pages.index');
    Route::post('/pages', [PageContentController::class, 'store'])->name('content.pages.store');
    Route::get('/pages/{page}', [PageContentController::class, 'show'])->name('content.pages.show');
    Route::put('/pages/{page}', [PageContentController::class, 'update'])->name('content.pages.update');
    Route::patch('/pages/{page}', [PageContentController::class, 'update'])->name('content.pages.patch');
    Route::delete('/pages/{page}', [PageContentController::class, 'destroy'])->name('content.pages.destroy');
    
    // Import/Export routes
    Route::get('/pages/export', [PageContentController::class, 'export'])->name('content.pages.export');
    Route::post('/pages/import', [PageContentController::class, 'import'])->name('content.pages.import');
});