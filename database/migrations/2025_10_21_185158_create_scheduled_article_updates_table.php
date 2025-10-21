<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('scheduled_article_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->timestamp('run_at')->index();
            $table->boolean('processed')->default(false)->index();
            $table->timestamps();

            $table->index(['processed', 'run_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_article_updates');
    }
};
