<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ArtikelController;

Route::post('/upload-excel', [ArtikelController::class, 'uploadExcel']);
