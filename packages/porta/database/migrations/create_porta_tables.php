<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPinnacle\Porta\Enums\IntegrationAuth;
use PHPinnacle\Porta\Enums\IntegrationFormat;
use PHPinnacle\Porta\Enums\IntegrationResponse;
use PHPinnacle\Porta\Models\Integration;
use PHPinnacle\Porta\Models\Webhook;

return new class extends Migration {
    public function up(): void
    {
        /** @see Integration */
        Schema::create('integrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('type');
            $table->string('format')->default(IntegrationFormat::JSON->value);
            $table->string('auth')->default(IntegrationAuth::None->value);
            $table->string('auth_key')->nullable();
            $table->string('auth_secret')->nullable();
            $table->string('response_kind')->default(IntegrationResponse::Empty->value);
            $table->integer('response_code')->default(200);
            $table
                ->boolean('is_active')
                ->default(true)
                ->index();
            $table->jsonb('transforms')->default('[]');
            $table->timestamps();

            $this->addTenancy($table);
        });

        /** @see Webhook */
        Schema::create('webhooks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table
                ->foreignIdFor(Integration::class)
                ->index()
                ->constrained()
                ->cascadeOnDelete();
            $table->string('origin');
            $table->string('status')->index();
            $table->longText('payload');
            $table->jsonb('headers');
            $table->jsonb('error')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhooks');
        Schema::dropIfExists('integrations');
    }

    private function addTenancy(Blueprint $table): void
    {
        $tenancy = config('phpinnacle-porta.tenancy');

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
