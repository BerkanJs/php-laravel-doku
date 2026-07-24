<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\LogRequests;
use Illuminate\Support\Facades\Route;

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
});

// CRM route'lari - artik auth middleware ile korunuyor (Gun 23'te ertelenmisti, Gun 24'te Breeze gelince eklendi)
Route::middleware('auth')->group(function () {
    Route::resource('companies', CompanyController::class)->middleware(LogRequests::class);
    Route::resource('contacts', ContactController::class)->only(['create', 'store']);
    Route::resource('deals', DealController::class)->only(['create', 'store']);
});

require __DIR__.'/auth.php';
