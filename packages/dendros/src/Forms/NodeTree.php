<?php

namespace PHPinnacle\Dendros\Forms;

use CodeWithDennis\FilamentSelectTree\SelectTree;

class NodeTree extends SelectTree
{
    public static function getDefaultName(): string
    {
        return 'parent_id';
    }

    public function setUp(): void
    {
        parent::setUp();

        $this
            ->enableBranchNode()
            ->withCount();
    }
}
