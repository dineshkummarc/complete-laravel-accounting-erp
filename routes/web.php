<?php

declare(strict_types=1);

use App\Http\Controllers\BusinessFilesController;
use App\Http\Controllers\BusinessSelectionController;
use App\Integrations\Contracts\DrutogotiAdapter;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/app');
Route::redirect('/login', '/app/login')->name('login');
Route::middleware(['auth', 'can:platform.console'])->get('/drutogoti', function (DrutogotiAdapter $profiler) {
    return view('performance', ['summary' => $profiler->summary()]);
})->name('performance');

Route::middleware('auth')->group(function (): void {
    Route::get('/business/attachments/{uuid}', [BusinessFilesController::class, 'attachment'])->middleware('signed')->name('business.attachment');
    Route::get('/business/branches.csv', [BusinessFilesController::class, 'branches'])->name('business.branches.export');
    Route::post('/business/branch', [BusinessSelectionController::class, 'branch'])->name('business.branch');
    Route::post('/business/language', [BusinessSelectionController::class, 'language'])->name('business.language');
});
