<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\XlsxController;

Route::post('/upload-excel', [XlsxController::class, 'handleUpload']);
