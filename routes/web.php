<?php

use App\Http\Controllers\QrRedirectController;
use Illuminate\Support\Facades\Route;

Route::get('/go/{code}', [QrRedirectController::class, 'scan'])->name('qr.scan')->middleware('throttle:120,1');
Route::get('/r/{token}', [QrRedirectController::class, 'click'])->name('qr.click')->middleware('throttle:120,1');
