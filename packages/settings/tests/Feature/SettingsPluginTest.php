<?php

use Filament\Facades\Filament;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPinnacle\Settings\Definition;
use PHPinnacle\Settings\Services\DefinitionRegistry;
use PHPinnacle\Settings\Services\SettingsStorage;
use PHPinnacle\Settings\SettingsPlugin;
use Tests\TestCase;

class PluginOnlySettings
{
    public string $title = 'Default title';

    public int $limit = 10;
}

class SettingsConsumer
{
    public function __construct(
        public PluginOnlySettings $settings,
    ) {}
}

class ExtraSettings extends PluginOnlySettings {}

class SettingsTestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('settings-test')
            ->default()
            ->plugin(SettingsPlugin::make()->definitions(PluginOnlySettings::class));
    }
}

class SettingsPluginTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), SettingsTestPanelProvider::class];
    }
}

uses(SettingsPluginTestCase::class);

beforeEach(function () {
    Schema::create('settings', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('tenant_id');
        $table->string('group');
        $table->string('key');
        $table->json('value')->nullable();
    });

    app(SettingsStorage::class)->save(PluginOnlySettings::class, ['title' => 'Stored title']);
    app(SettingsStorage::class)->save(ExtraSettings::class, ['title' => 'Extra title']);
    app()->forgetInstance(SettingsStorage::class);
});

it('hydrates injected settings registered only in a panel provider without a current panel', function () {
    expect(config('phpinnacle-settings.definitions'))->toBeNull()->and(Filament::getCurrentPanel())->toBeNull();

    $settings = app(SettingsConsumer::class)->settings;

    expect($settings->title)
        ->toBe('Stored title')
        ->and($settings->limit)
        ->toBe(10)
        ->and(app(PluginOnlySettings::class))
        ->toBe($settings);
});

it('uses the same plugin definitions for hydration and the settings page', function (string $source) {
    $evaluations = 0;
    $definition = Definition::make(ExtraSettings::class)->slug('general')->label('General');
    $plugin = SettingsPlugin::make()->definitions(match ($source) {
        'object' => $definition,
        'class' => ExtraSettings::class,
        default => function () use ($source, $definition, &$evaluations) {
            $evaluations++;

            return match ($source) {
                'closure object' => $definition,
                'closure class' => ExtraSettings::class,
                'closure list' => [$definition, Definition::make(stdClass::class)->slug('extra')],
            };
        },
    });
    $panel = Panel::make()->id('other-settings')->plugin($plugin);

    expect(app(ExtraSettings::class)->title)->toBe('Extra title');

    Filament::setCurrentPanel($panel);
    $registry = app(DefinitionRegistry::class);
    $slug = in_array($source, ['class', 'closure class'], true) ? 'extra' : 'general';

    expect($registry->get($slug)->class)
        ->toBe(ExtraSettings::class)
        ->and($registry->all())
        ->toHaveCount($source === 'closure list' ? 2 : 1)
        ->and($evaluations)
        ->toBe(str_starts_with($source, 'closure') ? 1 : 0);
})->with(['object', 'class', 'closure object', 'closure class', 'closure list']);

it('preserves the default group selected by the first registration', function (bool $batch) {
    $later = Definition::make(PluginOnlySettings::class)->slug('later')->sort(20);
    $earlier = Definition::make(ExtraSettings::class)->slug('earlier')->sort(10);
    $plugin = $batch
        ? SettingsPlugin::make()->definitions(fn () => [$later, $earlier])
        : SettingsPlugin::make()->definitions($later, $earlier);

    Filament::setCurrentPanel(Panel::make()->id('ordered-settings')->plugin($plugin));
    $registry = app(DefinitionRegistry::class);

    expect($registry->all()->keys()->all())
        ->toBe(['earlier', 'later'])
        ->and($registry->default())
        ->toBe($batch ? 'earlier' : 'later');
})->with([true, false]);
