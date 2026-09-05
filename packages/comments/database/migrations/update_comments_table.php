<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table
                ->foreignUuid('parent_id')
                ->nullable()
                ->after('subject_id')
                ->constrained('comments')
                ->nullOnDelete();
            $table->timestamp('edited_at')->nullable()->after('text');
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

        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'edited_at']);
        });
    }

    public function getConnection(): ?string
    {
        return config('phpinnacle-comments.connection');
    }
};
