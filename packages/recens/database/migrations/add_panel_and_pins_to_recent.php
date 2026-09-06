<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Ramsey\Uuid\Uuid;

return new class extends Migration {
    public function up(): void
    {
        $schema = Schema::connection($this->getConnection());
        $schema->table('recent', function (Blueprint $table) {
            $table->string('panel_id')->nullable()->index();
            $table->timestamp('pinned_at')->nullable()->index();
        });

        $connection = DB::connection($this->getConnection());
        $hasTenant = $schema->hasColumn('recent', 'tenant_id');

        foreach ($connection->table('recent')->cursor() as $entry) {
            $id = Uuid::uuid5(Uuid::NAMESPACE_URL, serialize([
                'user' => (string) $entry->user_id,
                'panel' => null,
                'tenant' => $hasTenant && $entry->tenant_id !== null ? (string) $entry->tenant_id : null,
                'url' => $entry->url,
            ]))->toString();

            $connection->table('recent')->where('id', $entry->id)->update(['id' => $id]);
        }
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->table('recent', function (Blueprint $table) {
            $table->dropIndex(['panel_id']);
            $table->dropIndex(['pinned_at']);
            $table->dropColumn(['panel_id', 'pinned_at']);
        });
    }

    public function getConnection(): ?string
    {
        return config('phpinnacle-recens.connection');
    }
};
