<?php

use App\Http\Controllers\Auth\UniversalLoginController;
use Illuminate\Support\Facades\Route;

// Universal smart login — single entry point for all roles
Route::get('/', [UniversalLoginController::class, 'showLogin'])->name('login.show');
Route::get('/login', fn () => redirect('/'))->name('login.redirect');
Route::post('/login', [UniversalLoginController::class, 'login'])->name('login');
Route::post('/logout', [UniversalLoginController::class, 'logout'])->name('logout');

// Balloon Dispatch image download — requires any authenticated user
Route::get('/balloon-dispatch/{dispatch}/image/download', function (\App\Models\BalloonDispatch $dispatch) {
    $media = $dispatch->getFirstMedia('balloon-dispatch-images');

    abort_if(is_null($media), 404, 'No image attached to this dispatch.');

    return response()->download(
        $media->getPath(),
        $media->file_name,
        ['Content-Type' => $media->mime_type]
    );
})->middleware(['auth'])->name('balloon-dispatch.image.download');

