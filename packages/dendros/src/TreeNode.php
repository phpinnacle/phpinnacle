<?php

namespace PHPinnacle\Dendros;

use Illuminate\Database\Eloquent\Model;

interface TreeNode
{
    public function getParentColumn(): string;

    public function getPathColumn(): string;

    public function getPathSource(): string;

    public function isAncestorOf(Model $that): bool;

    public function isDescendantOf(Model $that): bool;

    public function isRoot(): bool;
}
