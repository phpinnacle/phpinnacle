<?php

namespace PHPinnacle\Common\Concerns;

use Livewire\Attributes\On;

trait CanBeRefreshed
{
    #[On('refresh-page')]
    public function refresh(): void {}
}
