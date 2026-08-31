<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPinnacle\Signum\Models\Alert;

return new class extends Migration {
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }

    public function getConnection(): ?string
    {
        return config('phpinnacle-signum.connection');
    }

    public function up(): void
    {
        /** @see Alert */
        Schema::create('alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('target');
            $table->string('level');
            $table->string('title');
            $table->longText('message')->nullable();
            $table
                ->foreignIdFor(config('phpinnacle-signum.user.model'), 'created_by')
                ->index()
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->timestamps();

            $this->addTenancy($table);
        });
    }

    private function addTenancy(Blueprint $table): void
    {
        $tenancy = config('phpinnacle-signum.tenancy');

        if (isset($tenancy['model']) && class_exists($tenancy['model'])) {
            $table
                ->foreignIdFor($tenancy['model'], 'tenant_id')
                ->after('id')
                ->index()
                ->default($tenancy['default'] ?? null)
                ->constrained()
                ->cascadeOnDelete();
        }
    }
};
