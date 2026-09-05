<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPinnacle\Comments\Models\Comment;

return new class extends Migration {
    public function up(): void
    {
        /** @see Comment */
        Schema::create('comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table
                ->foreignIdFor(config('phpinnacle-comments.user.model'), 'author_id')
                ->index()
                ->constrained()
                ->cascadeOnDelete();
            $table->uuidMorphs('subject');
            $table->longText('text');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }

    public function getConnection(): ?string
    {
        return config('phpinnacle-comments.connection');
    }
};
