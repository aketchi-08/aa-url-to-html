<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DomainController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UrlController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('urls', UrlController::class);
    Route::get('urls/{id}/download', [UrlController::class, 'download'])->name('urls.download');
    // リロード用ルート
    Route::post('/urls/{url}/reload', [UrlController::class, 'reload'])->name('urls.reload');

    Route::resource('domains', DomainController::class);
});

require __DIR__.'/auth.php';
