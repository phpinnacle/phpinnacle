<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPinnacle\Cerber\Models\Invitation;
use PHPinnacle\Cerber\Models\Role;
use PHPinnacle\Cerber\Models\Tenant;

return new class extends Migration {
    public function up(): void
    {
        /** @see Invitation */
        Schema::create('invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table
                ->foreignIdFor(Tenant::class)
                ->index()
                ->default(Tenant::DEFAULT)
                ->constrained()
                ->cascadeOnDelete();
            $table
                ->foreignIdFor(Role::class)
                ->index()
                ->constrained()
                ->cascadeOnDelete();
            $table->string('email');
            $table->timestamps();

            $table->unique(['tenant_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }

    public function getConnection(): ?string
    {
        return config('phpinnacle-cerber.connection');
    }
};
