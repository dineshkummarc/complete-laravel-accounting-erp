<?php

use ImranDevBd\AiCatalog\Http\Controllers\CatalogStudioController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogStudioController::class, 'index'])->name('studio');
Route::get('llms.txt', [CatalogStudioController::class, 'llmsTxt'])->name('llms');
