<?php

use App\Http\Controllers\LabController;
use App\Http\Middleware\VerifyWebhookToken;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/health'));

Route::get('/health', [LabController::class, 'health']);
Route::post('/upload', [LabController::class, 'upload']);
Route::post('/webhook', [LabController::class, 'webhook'])->middleware(VerifyWebhookToken::class);
Route::get('/download', [LabController::class, 'download']);
Route::get('/download/{file}', [LabController::class, 'downloadByUuid']);
