<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPinnacle\Tags\Models\Tag;

return new class extends Migration {
    public function up(): void
    {
        /** @see Tag */
        Schema::create('tags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('type');
            $table->char('color', 7);
            $table->timestamps();

            $unique = ['name', 'type'];

            if ($this->addTenancy($table)) {
                array_unshift($unique, 'tenant_id');
            }

            $table->unique($unique);
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table
                ->foreignIdFor(Tag::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->uuid('taggable_id');

            $table->primary(['tag_id', 'taggable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taggables');
        Schema::dropIfExists('tags');
    }

    public function getConnection(): ?string
    {
        return config('phpinnacle-tags.connection');
    }

    private function addTenancy(Blueprint $table): bool
    {
        $tenancy = config('phpinnacle-tags.tenancy');

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
