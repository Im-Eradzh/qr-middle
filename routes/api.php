<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QrRequestController;
use App\Http\Middleware\LogRequestsMiddleware;


Route::middleware([LogRequestsMiddleware::class])->group(function () {
    Route::post('/request-qr', [QrRequestController::class, 'requestQr']);
});