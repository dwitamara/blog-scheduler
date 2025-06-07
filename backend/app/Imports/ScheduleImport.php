<?php

namespace App\Imports;

use App\Models\PostSchedule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ScheduleImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new PostSchedule([
            'judul' => $row['judul'],
            'konten_html' => $row['konten_html'],
            'image' => $row['image'],
            'tag' => $row['tag'],
            'tanggal_publish' => $row['tanggal_publish'],
        ]);
    }
}
