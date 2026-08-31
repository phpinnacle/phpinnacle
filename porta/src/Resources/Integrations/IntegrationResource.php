<?php

namespace PHPinnacle\Porta\Resources\Integrations;

use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use PHPinnacle\Porta\Models\Integration;

class IntegrationResource extends Resource
{
    protected static ?string $model = Integration::class;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationLabel(): string
    {
        return __('phpinnacle-porta::resources.integration.label');
    }

    public static function getNavigationGroup(): string
    {
        return __('phpinnacle-porta::resources.integration.group');
    }

    public static function getNavigationIcon(): ?string
    {
        return config('phpinnacle-porta.navigation.integration.icon');
    }

    public static function getNavigationSort(): ?int
    {
        return config('phpinnacle-porta.navigation.integration.sort');
    }

    public static function form(Schema $schema): Schema
    {
        return Schemas\IntegrationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\IntegrationsTable::configure($table);
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\EditIntegration::class,
            Pages\ManageWebhooks::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIntegrations::route('/'),
            'create' => Pages\CreateIntegration::route('/create'),
            'edit' => Pages\EditIntegration::route('/{record}/edit'),
            'webhooks' => Pages\ManageWebhooks::route('/{record}/webhooks'),
        ];
    }
}
