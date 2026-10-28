<?php

use ImranDevBd\AiCatalog\Http\Controllers\CatalogApiController;
use Illuminate\Support\Facades\Route;

Route::post('generate', [CatalogApiController::class, 'generate'])->name('generate');
Route::get('generations/{uuid}', [CatalogApiController::class, 'show'])->name('show');
Route::post('generations/{uuid}/review', [CatalogApiController::class, 'review'])->name('review');
Route::post('generations/{uuid}/approve', [CatalogApiController::class, 'approve'])->name('approve');
Route::post('generations/{uuid}/reject', [CatalogApiController::class, 'reject'])->name('reject');
Route::post('generations/{uuid}/regenerate/{field}', [CatalogApiController::class, 'regenerate'])->name('regenerate');
Route::post('bulk', [CatalogApiController::class, 'bulk'])->name('bulk');
Route::get('bulk/{uuid}', [CatalogApiController::class, 'bulkStatus'])->name('bulk.status');
Route::get('costs', [CatalogApiController::class, 'costs'])->name('costs');
Route::get('feeds/google', [CatalogApiController::class, 'feed'])->name('feeds.google');
Route::get('feeds/agentic', [CatalogApiController::class, 'agenticFeed'])->name('feeds.agentic');
Route::get('feeds/dpp', [CatalogApiController::class, 'dppFeed'])->name('feeds.dpp');
Route::get('audit', [CatalogApiController::class, 'audit'])->name('audit');
