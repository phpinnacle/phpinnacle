<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPinnacle\Cerber\Enums\UserStatus;
use PHPinnacle\Cerber\Models\Tenant;
use PHPinnacle\Cerber\Models\User;

return new class extends Migration {
    public function up(): void
    {
        /** @see User */
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table
                ->foreignIdFor(Tenant::class)
                ->index()
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('name');
            $table->string('password')->nullable();
            $table->rememberToken();
            $table
                ->string('status')
                ->default(UserStatus::Active)
                ->index();
            $table->boolean('two_factor_email')->default(false);
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->string('avatar', 2048)->nullable();
            $table->jsonb('panels')->default('[]');
            $table->jsonb('settings')->default('{}');
            $table->timestamps();
        });

        Schema::create('logins', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->uuid('provider_id')->nullable();
            $table->string('value');
            $table->boolean('is_default')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->unique(['type', 'provider_id', 'value']);
        });

        $connection = Schema::getConnection();
        $grammar = $connection->getQueryGrammar();
        $connection->statement(sprintf(
            'create unique index %s on %s ("type", "value") where "provider_id" is null',
            $grammar->wrap($connection->getTablePrefix() . 'logins_local_type_value_unique'),
            $grammar->wrapTable('logins'),
        ));

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('logins');
        Schema::dropIfExists('users');
    }

    public function getConnection(): ?string
    {
        return config('phpinnacle-cerber.connection');
    }
};
