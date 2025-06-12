<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class XlsxController extends Controller
{
    public function showForm()
    {
        return view('upload'); // Optional legacy form view
    }

    public function handleUpload(Request $request)
    {
        $request->validate([
            'xlsx_file' => 'required|mimes:xlsx',
        ]);

        $file = $request->file('xlsx_file')->getPathname();
        $rows = $this->readSimpleXlsx($file);

        // Ensure queue folder exists
        $queuePath = storage_path('app/queue');
        if (!is_dir($queuePath)) mkdir($queuePath, 0777, true);

        foreach ($rows as $index => $row) {
            if ($index === 0) continue; // skip header

            [$title, $htmlContent, $imageUrl, $scheduledDate] = $row;

            $filename = $queuePath . "/post_{$index}.json";
            file_put_contents($filename, json_encode([
                'title' => $title,
                'content' => $htmlContent,
                'image' => $imageUrl,
                'scheduled_date' => $scheduledDate
            ], JSON_PRETTY_PRINT));
        }

        return response()->json(['message' => 'Upload & Queue Successful']);
    }

    private function readSimpleXlsx($filePath)
    {
        $zip = new \ZipArchive;
        if ($zip->open($filePath) === TRUE) {
            $xml = $zip->getFromName('xl/sharedStrings.xml');
            preg_match_all('/<t[^>]*>(.*?)<\/t>/', $xml, $matches);
            $strings = $matches[1];

            $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
            preg_match_all('/<row[^>]*>(.*?)<\/row>/s', $sheet, $rows);
            $data = [];

            foreach ($rows[1] as $row) {
                preg_match_all('/<c[^>]*>(.*?)<\/c>/', $row, $cells);
                $rowData = [];
                foreach ($cells[1] as $cell) {
                    if (preg_match('/<v>(\d+)<\/v>/', $cell, $v)) {
                        $rowData[] = $strings[(int)$v[1]] ?? '';
                    } else {
                        $rowData[] = '';
                    }
                }
                $data[] = $rowData;
            }

            $zip->close();
            return $data;
        }
        return [];
    }
}
