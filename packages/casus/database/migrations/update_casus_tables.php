<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function down(): void
    {
        Schema::table('exceptions', function (Blueprint $table) {
            $table->dropUnique(['fingerprint']);
            $table->dropColumn(['fingerprint', 'occurrences', 'status', 'note']);
        });
    }

    public function getConnection(): ?string
    {
        return config('phpinnacle-casus.connection');
    }

    public function up(): void
    {
        Schema::table('exceptions', function (Blueprint $table) {
            $table->string('fingerprint', 64)->nullable()->unique()->after('id');
            $table->unsignedBigInteger('occurrences')->default(1)->after('trace');
            $table->string('status')->default('open')->index()->after('occurrences');
            $table->text('note')->nullable()->after('status');
        });
    }
};
