<?php

declare(strict_types=1);

final class CompactEmptyMethodBody
{
    /** @mago-expect lint:phpinnacle/compact-empty-method-body */
    public function multiline(): void
    {
    }

    public function compact(): void {}

    public function ignored(): void
    {
    }

    public function nonEmpty(): void
    {
        return;
    }

    public function commented(): void
    {
        // Intentionally empty.
    }
}
