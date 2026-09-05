<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPinnacle\Pinax\Models\Media;

return new class extends Migration {
    public function up(): void
    {
        /** @see Media */
        Schema::create('media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('holder');
            $table->string('name');
            $table->string('mime');
            $table->string('path');
            $table->unsignedInteger('size');
            $table->string('disk');
            $table->string('folder');
            $table->jsonb('marks')->default('[]');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $this->addTenancy($table);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }

    public function getConnection(): ?string
    {
        return config('phpinnacle-pinax.connection');
    }

    private function addTenancy(Blueprint $table): bool
    {
        $tenancy = (array) config('phpinnacle-pinax.tenancy');

        if (isset($tenancy['model']) && class_exists($tenancy['model'])) {
            $table
                ->foreignIdFor($tenancy['model'], 'tenant_id')
                ->after('id')
                ->index()
                ->default($tenancy['default'])
                ->constrained()
                ->cascadeOnDelete();

            return true;
        }

        return false;
    }
};
