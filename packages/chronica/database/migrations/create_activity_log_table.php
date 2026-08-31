<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }

    public function getConnection(): ?string
    {
        return config('phpinnacle-chronica.connection');
    }

    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('log_name')->nullable()->index();
            $table->text('description');
            $table->string('event')->nullable();
            $table->nullableUuidMorphs('subject', 'subject');
            $table->nullableUuidMorphs('causer', 'causer');
            $table->json('attribute_changes')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();

            $this->addTenancy($table);
        });
    }

    private function addTenancy(Blueprint $table): bool
    {
        $tenancy = config('phpinnacle-chronica.tenancy');

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
