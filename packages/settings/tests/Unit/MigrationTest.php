<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

class SettingsMigrationUser extends Model {}

it('rolls back both storage tables and can recreate them', function () {
    config()->set('phpinnacle-settings.user.model', SettingsMigrationUser::class);
    Schema::create('settings_migration_users', function (Blueprint $table) {
        $table->id();
    });
    $migration = require __DIR__ . '/../../database/migrations/create_settings_table.php';
    $migration->up();

    expect(Schema::hasTable('settings'))->toBeTrue();
    expect(Schema::hasTable('preferences'))->toBeTrue();

    $migration->down();

    expect(Schema::hasTable('settings'))->toBeFalse();
    expect(Schema::hasTable('preferences'))->toBeFalse();
    expect(Schema::hasTable('settings_migration_users'))->toBeTrue();

    $migration->up();

    expect(Schema::hasTable('settings'))->toBeTrue();
    expect(Schema::hasTable('preferences'))->toBeTrue();
});
