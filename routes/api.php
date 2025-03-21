<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QrRequestController;


Route::post('request-qr', [QrRequestController::class, 'requestQr']);
