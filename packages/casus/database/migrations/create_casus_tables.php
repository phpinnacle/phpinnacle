<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPinnacle\Casus\Models\Exception;

return new class extends Migration {
    public function down(): void
    {
        Schema::dropIfExists('exceptions');
    }

    public function getConnection(): ?string
    {
        return config('phpinnacle-casus.connection');
    }

    public function up(): void
    {
        /** @see Exception */
        Schema::create('exceptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sapi');
            $table->string('type');
            $table->string('code');
            $table->string('file');
            $table->integer('line');
            $table->longText('message');
            $table->text('trace');
            $table->json('context')->default('{}');
            $table->string('method')->nullable();
            $table->string('path')->nullable();
            $table->json('query')->nullable();
            $table->json('cookies')->nullable();
            $table->json('headers')->nullable();
            $table->text('body')->nullable();
            $table->string('ip')->nullable();
            $table->dateTime('occurred_at');

            $this->addTenancy($table);
        });
    }

    private function addTenancy(Blueprint $table): void
    {
        $tenancy = config('phpinnacle-casus.tenancy');

        if (isset($tenancy['model']) && class_exists($tenancy['model'])) {
            $table
                ->foreignIdFor($tenancy['model'], 'tenant_id')
                ->after('id')
                ->index()
                ->default($tenancy['default'])
                ->constrained()
                ->cascadeOnDelete();
        }
    }
};
