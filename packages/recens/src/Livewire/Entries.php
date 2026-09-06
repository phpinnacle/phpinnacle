<?php

namespace PHPinnacle\Recens\Livewire;

use Filament\Facades\Filament;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use PHPinnacle\Recens\Models\Recent;
use PHPinnacle\Recens\RecensPlugin;

class Entries extends Component
{
    public function togglePin(string $id): void
    {
        $this->entry($id)->togglePin();
    }

    public function remove(string $id): void
    {
        $this->entry($id)->delete();
    }

    public function clear(): void
    {
        abort_unless(Filament::auth()->check(), 403);

        Recent::clearHistory();
    }

    public function render(): View
    {
        $plugin = RecensPlugin::get();

        return view('phpinnacle-recens::entries', [
            'entries' => Recent::list($plugin->getLimit()),
            'color' => $plugin->getColor(),
            'icon' => $plugin->getIcon(),
        ]);
    }

    private function entry(string $id): Recent
    {
        abort_unless(Filament::auth()->check(), 403);

        Validator::make(['id' => $id], ['id' => ['required', 'uuid']])->validate();

        return Recent::forCurrentContext()->findOrFail($id);
    }
}
