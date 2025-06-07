<?php

namespace App\Http\Controllers;

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use App\Models\ScheduledPost;
use Carbon\Carbon;

class ScheduleUploadController extends Controller
{

public function upload(Request $request)
{
    $file = $request->file('file');
    $userApiKey = $request->input('api_key');

    $data = Excel::toArray([], $file)[0];

    foreach ($data as $index => $row) {
        if ($index === 0) continue; // skip header
        ScheduledPost::create([
            'judul' => $row[0],
            'konten_html' => $row[1],
            'image' => $row[2],
            'tag' => $row[3],
            'tanggal_publish' => Carbon::parse($row[4]),
            'user_api_key' => $userApiKey,
        ]);
    }

    return response()->json(['message' => 'Upload berhasil']);
}

}
