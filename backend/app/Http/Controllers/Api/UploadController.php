<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class UploadController extends Controller
{
    public function handleUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx',
            'token' => 'required|string',
            'publish_id' => 'required|string',
        ]);

        // ✅ Save token and publication ID to .env
        $envPath = base_path('.env');
        $envContent = File::get($envPath);

        $envContent = preg_replace('/^HASHNODE_API_TOKEN=.*$/m', "HASHNODE_API_TOKEN={$request->token}", $envContent);
        $envContent = preg_replace('/^HASHNODE_PUBLICATION_ID=.*$/m', "HASHNODE_PUBLICATION_ID={$request->publish_id}", $envContent);

        if (!str_contains($envContent, 'HASHNODE_API_TOKEN=')) {
            $envContent .= "\nHASHNODE_API_TOKEN={$request->token}";
        }
        if (!str_contains($envContent, 'HASHNODE_PUBLICATION_ID=')) {
            $envContent .= "\nHASHNODE_PUBLICATION_ID={$request->publish_id}";
        }

        File::put($envPath, $envContent);

        // ✅ Extract data from XLSX manually
        $filePath = $request->file('file')->getPathname();
        $rows = $this->parseXlsxManually($filePath);

        $queuePath = storage_path('app/queue');
        if (!is_dir($queuePath)) {
            mkdir($queuePath, 0755, true);
        }

        foreach ($rows as $i => $row) {
            if ($i === 0) continue; // skip header
            $title = $row[0] ?? '';
            $htmlContent = $row[1] ?? '';
            $imageUrl = $row[2] ?? '';
            $scheduledDate = $row[3] ?? '';

            $filename = $queuePath . "/post_" . uniqid() . ".json";
            file_put_contents($filename, json_encode([
                'title' => $title,
                'content' => $htmlContent,
                'image' => $imageUrl,
                'scheduled_date' => $scheduledDate,
            ], JSON_PRETTY_PRINT));
        }

        return response()->json(['message' => 'Posts queued successfully.']);
    }

    private function parseXlsxManually($filePath)
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
