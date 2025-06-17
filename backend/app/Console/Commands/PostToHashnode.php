<?php

namespace App\Console\Commands;

use League\HTMLToMarkdown\HtmlConverter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class PostToHashnode extends Command
{
    protected $signature = 'post:hashnode';
    protected $description = 'Post queued blog entries to Hashnode';

    public function handle()
    {
        $apiToken = env('HASHNODE_API_TOKEN');
        $publicationId = env('HASHNODE_PUBLICATION_ID');

        $queuePath = storage_path('app/queue');
        $files = glob($queuePath . '/*.json');

        if (empty($files)) {
            $this->info("No posts in queue.");
            return;
        }

        foreach ($files as $file) {
            $post = json_decode(file_get_contents($file), true);

            if (!$post || !isset($post['title'], $post['image'], $post['content'])) {
                $this->error("Invalid post format in: $file");
                continue;
            }

            $this->info("Posting: " . $post['title']);

            // Konten Markdown (menyisipkan gambar dan isi konten)
$converter = new HtmlConverter(['strip_tags' => true,]);

$markdownBody = $converter->convert($post['content'] ?? '');
$contentMarkdown = "![Gambar]({$post['image']})\n\n" . $markdownBody;
$this->line("Converted Markdown:\n" . $contentMarkdown);

            $mutation = <<<GQL
                mutation publishPost(\$input: publishPostInput!) {
                    publishPost(input: \$input) {
                        post {
                            title
                            slug
                        }
                    }
                }
            GQL;

            $variables = [
                'input' => [
                    'title' => $post['title'],
                    'contentMarkdown' => $contentMarkdown,
                    'publicationId' => $publicationId,
                    'tags' => $post['tags'] ?? [],
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => $apiToken,
                'Content-Type' => 'application/json',
            ])->post('https://gql.hashnode.com/', [
                'query' => $mutation,
                'variables' => $variables,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['data']['publishPost']['post'])) {
                    $this->info("✅ Successfully posted: " . $post['title']);
                    unlink($file); // Remove from queue
                } else {
                    $this->error("❌ Failed to post: " . $post['title']);
                    $this->line(print_r($data, true));
                }
            } else {
                $this->error("HTTP Error: " . $response->status());
                $this->line("Response: " . $response->body());
            }
        }
    }
}
