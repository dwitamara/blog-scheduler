<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\XlsxController;

Route::get('/', [XlsxController::class, 'showForm']);
Route::post('/upload', [XlsxController::class, 'handleUpload']);
