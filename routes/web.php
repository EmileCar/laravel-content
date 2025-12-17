<?php

use Illuminate\Support\Facades\Route;
use Carone\Content\Http\Controllers\ContentEditorController;

Route::group([], function () {
    // Content Editor - Display the editor page
    Route::get('/', [ContentEditorController::class, 'index'])->name('content.editor.index');
});