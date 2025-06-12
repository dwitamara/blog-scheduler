<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class PostToHashnode extends Command
{
    protected $signature = 'post:hashnode';
    protected $description = 'Post queued blog entries to Hashnode';

    public function handle()
    {
        $queuePath = storage_path('app/queue');
        if (!is_dir($queuePath)) {
            $this->error('Queue folder does not exist.');
            return;
        }

        $files = glob("$queuePath/*.json");
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);

            $this->info("Posting: " . $data['title']);

            $mutation = <<<GQL
mutation CreateDraft(\$input: CreateDraftInput!) {
  createDraft(input: \$input) {
    post {
      id
      title
      slug
    }
  }
}
GQL;

            $variables = [
                'input' => [
                    'title' => $data['title'],
                    'contentMarkdown' => strip_tags($data['content']),
                    'coverImageOptions' => [
                        'coverImageURL' => $data['image'] ?? null
                    ],
                    'publicationId' => env('HASHNODE_PUBLICATION_ID'),
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => env('HASHNODE_API_TOKEN'),
                'Content-Type' => 'application/json'
            ])->post('https://gql.hashnode.com', [
                'query' => $mutation,
                'variables' => $variables
            ]);

            $body = $response->json();

            if (isset($body['errors'])) {
                $this->error("Failed: " . $data['title']);
                $this->error(json_encode($body, JSON_PRETTY_PRINT));
            } else {
                $this->info("✅ Successfully posted draft: " . $data['title']);
                unlink($file); // delete file after successful posting
            }
        }
    }
}
