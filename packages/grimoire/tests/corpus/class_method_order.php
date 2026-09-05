<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

/** @mago-expect lint:phpinnacle/class-method-order */
return new class extends Migration {
    public function up(): void {}

    public function down(): void {}

    public function getConnection(): ?string
    {
        return null;
    }

    public function seed(): void {}

    public static function boot(): void {}

    protected function helper(): void {}

    private function internal(): void {}
};

abstract class FrameworkHandler {}

/** @mago-expect lint:phpinnacle/class-method-order */
final class Handler extends FrameworkHandler
{
    public function stop(): void {}

    public static function boot(): void {}

    public function start(): void {}
}
