<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\ArtikelImport;
use Maatwebsite\Excel\Facades\Excel;

class ArtikelController extends Controller
{

public function uploadExcel(Request $request)
{
    $request->validate([
        'file' => 'required|file|mimes:xlsx,xls|max:10240',
        'token' => 'required|string',
    ]);

    $file = $request->file('file');
    $token = $request->input('token');

    try {
        Excel::import(new ArtikelImport($token), $file);
        return response()->json(['message' => '✅ File berhasil diupload dan diproses!']);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

}