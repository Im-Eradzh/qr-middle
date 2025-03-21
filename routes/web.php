<?php

use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;


Route::get('/product/{orderId}', [PaymentController::class, 'pay'])->name('product.page');
Route::get('/generate-qr/{orderId}', [PaymentController::class, 'generateQR'])->name('generate.qr');
Route::get('/check-status/{orderId}', [PaymentController::class, 'checkStatus'])->name('check.status');



