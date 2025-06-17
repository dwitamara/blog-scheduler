<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class XlsxController extends Controller
{
    public function showForm()
    {
        return view('upload');
    }

    public function handleUpload(Request $request)
    {
        $request->validate([
            'xlsx_file' => 'required|file|mimes:xlsx,xls',
            'token' => 'required|string',
            'publish_id' => 'required|string',
        ]);

        try {
            // === Tambahkan log token dan publish_id dari FE ===
            Log::info('Token dari FE:', ['token' => $request->input('token')]);
            Log::info('Publication ID dari FE:', ['publish_id' => $request->input('publish_id')]);

            $file = $request->file('xlsx_file')->getPathname();
            $token = $request->input('token');
            $publicationId = $request->input('publish_id');

            $rows = $this->readSimpleXlsx($file);

            if (empty($rows)) {
                return response()->json(['message' => 'Gagal membaca isi file Excel.'], 400);
            }

            $successCount = 0;
            $failCount = 0;

            foreach ($rows as $index => $row) {
                if ($index === 0) continue; // Skip header

                if (count($row) < 4) {
                    Log::warning("Baris ke-$index tidak lengkap. Data: " . json_encode($row));
                    continue;
                }

                [$title, $htmlContent, $imageUrl, $scheduledDate] = $row;
                
                $htmlContent = html_entity_decode($htmlContent, ENT_QUOTES | ENT_HTML5);

                $convertedImage = $this->convertGoogleDriveLink($imageUrl);

$contentWithImage = "### {$title}\n\n{$htmlContent}";
if (!empty($convertedImage)) {
    $contentWithImage .= "\n\n![Gambar]($convertedImage)";
}

// Tambahkan log isi konten yang dikirim
Log::info("Konten Markdown yang dikirim ke Hashnode:", [
    'title' => $title,
    'markdown' => $contentWithImage
]);

$mutation = <<<GQL
mutation CreateDraft(\$input: CreateDraftInput!) {
    createDraft(input: \$input) {
        draft {
            title
            slug
        }
    }
}
GQL;

$variables = [
    'input' => [
        'title' => $title,
        'contentMarkdown' => $contentWithImage,
        'publicationId' => $publicationId,
    ]
];

Log::info('Mengirim request ke Hashnode dengan header:', [
    'Authorization' => 'Bearer ' . $token
]);

$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $token,
])->post('https://gql.hashnode.com/', [
    'query' => $mutation,
    'variables' => $variables,
]);

$responseBody = $response->json();
Log::info("Response dari Hashnode draft ke-$index:", $responseBody);

if (
    $response->successful()
    && isset($responseBody['data']['createDraft']['draft']['slug']) // pastikan slug terbentuk
) {
    $successCount++;
} else {
    Log::error("Gagal posting judul [$title]. Response: " . $response->body());
    $failCount++;
}
            }

            return response()->json([
                'message' => "Selesai. Berhasil: $successCount, Gagal: $failCount"
            ]);

        } catch (\Exception $e) {
            Log::error('Upload error: ' . $e->getMessage());
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

    private function convertGoogleDriveLink($url)
    {
        if (preg_match('/drive\.google\.com\/file\/d\/(.*?)\/view/', $url, $matches)) {
            $fileId = $matches[1];
            return "https://drive.google.com/uc?export=view&id=" . $fileId;
        }
        return $url;
    }
}
