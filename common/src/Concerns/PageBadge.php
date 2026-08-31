<?php

namespace PHPinnacle\Common\Concerns;

use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin Page
 */
trait PageBadge
{
    public static function getNavigationItems(array $urlParameters = []): array
    {
        $items = parent::getNavigationItems($urlParameters);
        /** @var Model|null $record */
        $record = $urlParameters['record'] ?? null;

        if ($record !== null) {
            foreach ($items as $item) {
                $item->badge((string) $record->{static::$relationship}()->count());
            }
        }

        return $items;
    }
}
