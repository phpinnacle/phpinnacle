<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPinnacle\Razor\Enums\Engine;
use PHPinnacle\Razor\Enums\Format;
use PHPinnacle\Razor\Models\Document;
use PHPinnacle\Razor\Models\Template;

return new class extends Migration {
    public function up(): void
    {
        /** @see Template */
        Schema::create('templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table
                ->foreignUuid('parent_id')
                ->index()
                ->nullable();
            $table->string('name');
            $table->string('numeration')->nullable();
            $table->string('section')->index();
            $table->text('content')->default('');
            $table->string('format')->default(Format::HTML->value);
            $table->string('engine')->default(Engine::Handlebars->value);
            $table->jsonb('context')->default('{}');
            $table->integer('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_history')->default(false);
            $table
                ->foreignIdFor(config('phpinnacle-razor.auth.model'), 'created_by')
                ->index()
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->timestamps();

            $this->addTenancy($table);
        });

        /** @see Document */
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table
                ->uuid('parent_id')
                ->index()
                ->nullable();
            $table->uuidMorphs('holder');
            $table->uuidMorphs('entity');
            $table
                ->foreignIdFor(Template::class)
                ->index()
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('number');
            $table->longText('content')->nullable();
            $table->json('context')->nullable();
            $table
                ->foreignIdFor(config('phpinnacle-razor.auth.model'), 'created_by')
                ->index()
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->date('issued_at')->nullable();
            $table->date('signed_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
        Schema::dropIfExists('templates');
    }

    private function addTenancy(Blueprint $table): void
    {
        $tenancy = config('phpinnacle-razor.tenancy');

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
