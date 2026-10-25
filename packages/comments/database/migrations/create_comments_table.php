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
            $table->uuid('id');
            $table->primary('id');
            $table
                ->foreignIdFor(config('phpinnacle-comments.user.model'), 'author_id')
                ->index()
                ->constrained()
                ->cascadeOnDelete();
            $table->uuidMorphs('subject');
            $table->foreignUuid('parent_id')->nullable()->constrained('comments')->nullOnDelete();
            $table->longText('text');
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
        });

        Schema::create('comment_subscriptions', function (Blueprint $table) {
            $table
                ->foreignIdFor(config('phpinnacle-comments.user.model'), 'user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->uuidMorphs('subject');
            $table->unique(['user_id', 'subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_subscriptions');
        Schema::dropIfExists('comments');
    }

    public function getConnection(): ?string
    {
        return config('phpinnacle-comments.connection');
    }
};
