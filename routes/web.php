<?php

use App\Http\Controllers\Auth\UniversalLoginController;
use Illuminate\Support\Facades\Route;

// Universal smart login — single entry point for all roles
Route::get('/', [UniversalLoginController::class, 'showLogin'])->name('login.show');
Route::get('/login', fn () => redirect('/'))->name('login.redirect');
Route::post('/login', [UniversalLoginController::class, 'login'])->name('login');
Route::post('/logout', [UniversalLoginController::class, 'logout'])->name('logout');
