<?php

namespace PHPinnacle\Dendros;

use Illuminate\Database\Eloquent\Model;

interface TreeNode
{
    public function isRoot(): bool;

    public function isAncestorOf(Model $that): bool;

    public function isDescendantOf(Model $that): bool;

    public function getPathColumn(): string;

    public function getParentColumn(): string;

    public function getPathSource(): string;
}
