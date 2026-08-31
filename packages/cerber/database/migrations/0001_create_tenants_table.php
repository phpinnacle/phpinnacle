<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPinnacle\Cerber\Enums\TenantStatus;
use PHPinnacle\Cerber\Models\Tenant;

return new class extends Migration {
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }

    public function getConnection(): ?string
    {
        return config('phpinnacle-cerber.connection');
    }

    public function up(): void
    {
        /** @see Tenant */
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('domain')->unique();
            $table
                ->string('status')
                ->index()
                ->default(TenantStatus::Active);
            $table->string('name')->nullable();
            $table->string('logo')->nullable();
            $table->timestamps();
        });
    }
};
