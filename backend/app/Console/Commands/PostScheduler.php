<?php

namespace App\Console\Commands;

use App\Models\ScheduledPost;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class PostScheduler extends Command
{
    protected $signature = 'post:schedule';
    protected $description = 'Publish scheduled posts';

    public function handle()
    {
        $now = Carbon::now();

        $posts = ScheduledPost::where('status', 'scheduled')
            ->where('tanggal_publish', '<=', $now)
            ->get();

        foreach ($posts as $post) {
            // Kirim posting ke Hasnote API
            $response = Http::post('https://api.hasnote.com/post', [
                'api_key' => $post->user_api_key,
                'judul' => $post->judul,
                'konten_html' => $post->konten_html,
                'image' => $post->image,
                'tag' => $post->tag,
            ]);

            if ($response->successful()) {
                $post->update(['status' => 'posted']);
                $this->info("✅ Posted: " . $post->judul);
            } else {
                $this->error("❌ Failed to post: " . $post->judul);
            }
        }
    }
}
