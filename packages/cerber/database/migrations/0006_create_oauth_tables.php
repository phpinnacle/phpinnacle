<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPinnacle\Cerber\Models\Provider;
use PHPinnacle\Cerber\Models\SocialAccount;
use PHPinnacle\Cerber\Models\User;

return new class extends Migration {
    public function up(): void
    {
        /** @see Provider */
        Schema::create('oauth_providers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('type');
            $table->text('config');
            $table
                ->unsignedInteger('sort')
                ->index()
                ->default(0);
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $this->addTenancy($table);
        });

        /** @see SocialAccount */
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table
                ->foreignIdFor(User::class)
                ->index()
                ->constrained()
                ->cascadeOnDelete();
            $table
                ->foreignIdFor(Provider::class)
                ->index()
                ->constrained()
                ->cascadeOnDelete();
            $table->string('external_id');
            $table->string('email')->nullable();
            $table->json('profile')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'provider_id']);
            $table->unique(['provider_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
        Schema::dropIfExists('oauth_providers');
    }

    public function getConnection(): ?string
    {
        return config('phpinnacle-cerber.connection');
    }

    private function addTenancy(Blueprint $table): bool
    {
        $tenancy = (array) config('phpinnacle-cerber.tenancy');

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
