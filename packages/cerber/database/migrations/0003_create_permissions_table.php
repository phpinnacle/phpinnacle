<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPinnacle\Cerber\Models\Permission;
use PHPinnacle\Cerber\Models\Role;
use PHPinnacle\Cerber\Models\Tenant;
use PHPinnacle\Cerber\Models\User;

return new class extends Migration {
    public function down(): void
    {
        Schema::dropIfExists('users_permissions');
        Schema::dropIfExists('users_roles');
        Schema::dropIfExists('roles_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
    }

    public function getConnection(): ?string
    {
        return config('phpinnacle-cerber.connection');
    }

    public function up(): void
    {
        /** @see Permission */
        Schema::create('permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->default('');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        /** @see Role */
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table
                ->foreignIdFor(Tenant::class)
                ->index()
                ->default(Tenant::DEFAULT)
                ->constrained()
                ->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->default('');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('roles_permissions', function (Blueprint $table) {
            $table
                ->foreignIdFor(Role::class)
                ->constrained()
                ->cascadeOnDelete();
            $table
                ->foreignIdFor(Permission::class)
                ->index()
                ->constrained()
                ->cascadeOnDelete();

            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('users_roles', function (Blueprint $table) {
            $table
                ->foreignIdFor(User::class)
                ->constrained()
                ->cascadeOnDelete();
            $table
                ->foreignIdFor(Role::class)
                ->index()
                ->constrained()
                ->cascadeOnDelete();

            $table->primary(['user_id', 'role_id']);
        });

        Schema::create('users_permissions', function (Blueprint $table) {
            $table
                ->foreignIdFor(User::class)
                ->constrained()
                ->cascadeOnDelete();
            $table
                ->foreignIdFor(Permission::class)
                ->index()
                ->constrained()
                ->cascadeOnDelete();

            $table->primary(['user_id', 'permission_id']);
        });
    }
};
