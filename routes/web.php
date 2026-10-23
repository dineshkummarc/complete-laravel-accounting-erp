<?php

declare(strict_types=1);

use App\Integrations\Contracts\DrutogotiAdapter;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/app');
Route::redirect('/login', '/app/login')->name('login');
Route::middleware(['auth', 'can:platform.console'])->get('/drutogoti', function (DrutogotiAdapter $profiler) {
    return view('performance', ['summary' => $profiler->summary()]);
})->name('performance');
