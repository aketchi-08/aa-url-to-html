<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DomainController;
use App\Http\Controllers\ExtractSelectorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RemoveSelectorController;
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
    Route::post('/urls/{url}/reload', [UrlController::class, 'reload'])->name('urls.reload');

    Route::resource('domains', DomainController::class);

    // 抽出セレクタ
    Route::resource('extract-selectors', ExtractSelectorController::class)->only(['store','update','destroy']);

    // 削除セレクタ
    Route::resource('remove-selectors', RemoveSelectorController::class)->only(['store','update','destroy']);
});

require __DIR__.'/auth.php';
