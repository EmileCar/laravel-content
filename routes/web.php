<?php

use Illuminate\Support\Facades\Route;
use Carone\Content\Http\Controllers\ContentEditorController;

Route::group([], function () {
    // Content Editor routes
    Route::get('/', [ContentEditorController::class, 'index'])->name('content.editor.index');
    Route::get('/page/{pageId}', [ContentEditorController::class, 'getPageContent'])->name('content.editor.page');
    Route::post('/content', [ContentEditorController::class, 'store'])->name('content.editor.store');
    Route::delete('/content/{id}', [ContentEditorController::class, 'destroy'])->name('content.editor.destroy');
});