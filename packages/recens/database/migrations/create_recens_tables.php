<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recent', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table
                ->foreignIdFor(config('phpinnacle-recens.user.model'))
                ->index()
                ->constrained()
                ->cascadeOnDelete();
            $table
                ->string('group')
                ->index()
                ->nullable();
            $table->text('url');
            $table->string('icon');
            $table->string('title');
            $table->timestamp('created_at')->index();

            $this->addTenancy($table);
        });
    }

    public function down(): void
    {
        Schema::drop('recent');
    }

    public function getConnection(): ?string
    {
        return config('phpinnacle-recens.connection');
    }

    private function addTenancy(Blueprint $table): void
    {
        $tenancy = config('phpinnacle-recens.tenancy');

        if (isset($tenancy['model']) && class_exists($tenancy['model'])) {
            $table
                ->foreignIdFor($tenancy['model'], 'tenant_id')
                ->after('id')
                ->index()
                ->default($tenancy['default'] ?? null)
                ->constrained()
                ->cascadeOnDelete();

            return;
        }
    }
};
