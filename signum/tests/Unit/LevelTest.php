<?php

use PHPinnacle\Signum\Enums\Level;

it('provides presentation metadata for alert levels', function (Level $level, string $color, string $icon): void {
    expect($level->getColor())->toBe($color)
        ->and($level->getIcon())->toBe($icon);
})->with([
    'note' => [Level::Note, 'gray', 'phosphor-note'],
    'info' => [Level::Info, 'info', 'phosphor-info'],
    'success' => [Level::Success, 'success', 'phosphor-check-circle'],
    'warning' => [Level::Warning, 'warning', 'phosphor-warning-circle'],
    'danger' => [Level::Danger, 'danger', 'phosphor-biohazard'],
]);
