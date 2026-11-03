<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPinnacle\Cerber\Models\Provider;

return new class extends Migration {
    public function up(): void
    {
        /** @see Provider */
        Schema::create('oauth_providers', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('name');
            $table->string('type');
            $table->text('config');
            $table->string('context')->index();
            $table
                ->unsignedInteger('sort')
                ->index()
                ->default(0);
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $this->addTenancy($table);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oauth_providers');
    }

    public function getConnection(): ?string
    {
        return config('phpinnacle-cerber.connection');
    }

    private function addTenancy(Blueprint $table): bool
    {
        $tenancy = (array) config('phpinnacle-cerber.tenancy');

        if (($tenancy['model'] ?? null) !== null && class_exists($tenancy['model'])) {
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
