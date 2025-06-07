<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('post_schedules', function (Blueprint $table) {
        $table->id();
        $table->string('judul');
        $table->longText('konten_html')->nullable();
        $table->string('image')->nullable();
        $table->string('tag')->nullable();
        $table->timestamp('tanggal_publish');
        $table->boolean('posted')->default(false);
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_schedules');
    }
};
