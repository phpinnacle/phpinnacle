<?php

namespace PHPinnacle\Settings\Forms;

use Filament\Schemas\Components\Group;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use PHPinnacle\Settings\Models\Preference;

class PreferenceGroup extends Group
{
    public function setUp(): void
    {
        parent::setUp();

        $this
            ->statePath('preferences')
            ->loadStateFromRelationshipsUsing(function (Group $component, Model $record) {
                if (!$record instanceof Authenticatable) {
                    throw new LogicException('Preferences can only be loaded for authenticatable models.');
                }

                $component->state(Preference::retrieve($record));
            })
            ->saveRelationshipsUsing(function (Group $component, Model $record) {
                if (!$record instanceof Authenticatable) {
                    throw new LogicException('Preferences can only be saved for authenticatable models.');
                }

                Preference::store($record, $component->getState());
            });
    }
}
