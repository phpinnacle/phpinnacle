<?php

use PHPinnacle\Skeleton\SkeletonPlugin;

it('exposes a stable Filament plugin identifier', function (): void {
    expect((new SkeletonPlugin)->getId())->toBe('phpinnacle/skeleton');
});
