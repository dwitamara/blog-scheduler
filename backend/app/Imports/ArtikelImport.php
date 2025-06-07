<?php

namespace App\Imports;

use App\Models\Artikel;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ArtikelImport implements ToModel, WithHeadingRow
{
    protected $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function model(array $row)
{
    if (!isset($row['title']) || !isset($row['content'])) {
        // Log error or skip row
        return null;
    }

    return new Artikel([
        'title'   => $row['title'],
        'content' => $row['content'],
        'token'   => $this->token,
    ]);
}

}
