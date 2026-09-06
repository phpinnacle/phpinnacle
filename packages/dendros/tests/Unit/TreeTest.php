<?php

use Illuminate\Database\Eloquent\Model;
use PHPinnacle\Dendros\AsTree;
use PHPinnacle\Dendros\Database\Path;
use PHPinnacle\Dendros\TreeNode;

it('detects ancestor paths', function () {
    expect(Path::isAncestorOf('root', 'root.child'))
        ->toBeTrue()
        ->and(Path::isAncestorOf('other', 'root.child'))
        ->toBeFalse();
});

it('compares tree models', function () {
    $model = new class extends Model implements TreeNode {
        use AsTree;

        protected $guarded = [];
    };

    $root = $model->newInstance(['id' => 1, 'parent_id' => null, 'path' => 'root']);
    $child = $model->newInstance(['id' => 2, 'parent_id' => 1, 'path' => 'root.child']);

    expect($root->isRoot())
        ->toBeTrue()
        ->and($child->isRoot())
        ->toBeFalse()
        ->and($root->isAncestorOf($child))
        ->toBeTrue()
        ->and($child->isDescendantOf($root))
        ->toBeTrue()
        ->and($child->getPathSource())
        ->toBe('child');
});

it('compares complete path labels while preserving inclusive helper equality', function () {
    expect(Path::isAncestorOf('root.a', 'root.ab'))
        ->toBeFalse()
        ->and(Path::isAncestorOf('root.a', 'root.a.leaf'))
        ->toBeTrue()
        ->and(Path::isAncestorOf('root.a', 'root.a'))
        ->toBeTrue();
});
