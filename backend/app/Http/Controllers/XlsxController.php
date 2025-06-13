<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class XlsxController extends Controller
{
    public function showForm()
    {
        return view('upload'); // Bisa dihapus kalau tidak dipakai
    }

    public function handleUpload(Request $request)
    {
        // Validasi input
        $request->validate([
            'xlsx_file' => 'required|file|mimes:xlsx,xls',
            'token' => 'required|string',
            'publish_id' => 'nullable|string',
        ]);

        try {
            $file = $request->file('xlsx_file')->getPathname();
            $token = $request->input('token');
            $publishId = $request->input('publish_id');

            $rows = $this->readSimpleXlsx($file);

            if (empty($rows)) {
                return response()->json(['message' => '❌ Gagal membaca isi file Excel.'], 400);
            }

            // Pastikan folder queue ada
            $queuePath = storage_path('app/queue');
            if (!is_dir($queuePath)) {
                mkdir($queuePath, 0777, true);
            }

            foreach ($rows as $index => $row) {
                if ($index === 0) continue; // Skip header

                // Validasi jumlah kolom
                if (count($row) < 4) {
                    Log::warning("❗ Baris ke-$index tidak lengkap. Data: " . json_encode($row));
                    continue;
                }

                [$title, $htmlContent, $imageUrl, $scheduledDate] = $row;

                $filename = $queuePath . "/post_{$index}.json";
                file_put_contents($filename, json_encode([
                    'title' => $title,
                    'content' => $htmlContent,
                    'image' => $imageUrl,
                    'scheduled_date' => $scheduledDate,
                    'token' => $token,
                    'publish_id' => $publishId,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

            return response()->json(['message' => '✅ Upload & queue successful']);
        } catch (\Exception $e) {
            // Tangani error agar tidak error 500 misterius
            Log::error('❌ Upload error: ' . $e->getMessage());
            return response()->json(['message' => 'Server error: ' . $e->getMessage()], 500);
        }
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
