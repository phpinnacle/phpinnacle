<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function down(): void
    {
        Schema::dropIfExists('sequences');
    }

    public function getConnection(): ?string
    {
        return config('phpinnacle-sequentia.connection');
    }

    public function up(): void
    {
        Schema::create('sequences', function (Blueprint $table) {
            $table->uuid('tenant')->default('00000000-0000-0000-0000-000000000000');
            $table->string('scope');
            $table->char('hash', 40);
            $table->string('key');
            $table->unsignedBigInteger('value')->default(0);

            $table->primary([
                'tenant',
                'scope',
                'hash',
                'key',
            ]);
        });
    }
};
