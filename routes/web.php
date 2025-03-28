<?php

use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;


Route::get('/product/{token}', [PaymentController::class, 'pay'])->name('order.page');
Route::get('/generate-qr/{token}', [PaymentController::class, 'generateQR'])->name('generate-qr');
Route::get('/show-qr/{token}', [PaymentController::class, 'showQR'])->name('show-qr');
Route::get('/check-status/{orderId}', [PaymentController::class, 'checkStatus'])->name('check.status');



