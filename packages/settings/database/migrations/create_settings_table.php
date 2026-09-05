<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->default('00000000-0000-0000-0000-000000000000');
            $table->string('group')->index();
            $table->string('key');
            $table->jsonb('value')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'group', 'key']);
        });

        Schema::create('preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table
                ->foreignIdFor(config('phpinnacle-settings.user.model'), 'user_id')
                ->index()
                ->constrained()
                ->cascadeOnDelete();
            $table->string('group')->index();
            $table->string('key');
            $table->jsonb('value')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'group', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }

    public function getConnection(): ?string
    {
        return config('phpinnacle-settings.connection');
    }
};
