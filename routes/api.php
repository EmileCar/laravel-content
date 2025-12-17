<?php

use Illuminate\Support\Facades\Route;
use Carone\Content\Http\Controllers\ContentEditorController;

Route::group([], function () {
    // Content Editor API routes
    Route::get('/page/{pageId}', [ContentEditorController::class, 'getPageContent'])->name('content.editor.page');
    Route::post('/content', [ContentEditorController::class, 'store'])->name('content.editor.store');
    Route::delete('/content/{id}', [ContentEditorController::class, 'destroy'])->name('content.editor.destroy');
    Route::delete('/page/{pageId}', [ContentEditorController::class, 'destroyPage'])->name('content.editor.destroyPage');
    Route::get('/routes', [ContentEditorController::class, 'getWebRoutes'])->name('content.editor.routes');
});