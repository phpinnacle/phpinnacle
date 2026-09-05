<?php

namespace PHPinnacle\Dendros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use PHPinnacle\Dendros\Database\Path;
use PHPinnacle\Dendros\Relations\Ancestors;
use PHPinnacle\Dendros\Relations\Descendants;

/**
 * @mixin Model
 *
 * @phpstan-require-extends Model
 *
 * @phpstan-require-implements TreeNode
 */
trait AsTree
{
    public function isRoot(): bool
    {
        return $this->getAttribute($this->getParentColumn()) === null;
    }

    public function isAncestorOf(Model $that): bool
    {
        return (
            !$this->is($that)
            && Path::isAncestorOf(
                $this->getAttribute($this->getPathColumn()),
                $that->getAttribute($this->getPathColumn()),
            )
        );
    }

    public function isDescendantOf(Model $that): bool
    {
        return (
            !$this->is($that)
            && Path::isAncestorOf(
                $that->getAttribute($this->getPathColumn()),
                $this->getAttribute($this->getPathColumn()),
            )
        );
    }

    public function getPathColumn(): string
    {
        return 'path';
    }

    public function getParentColumn(): string
    {
        return 'parent_id';
    }

    public function getPathSource(): string
    {
        return Str::afterLast((string) $this->getAttribute($this->getPathColumn()), '.');
    }

    /**
     * @return BelongsTo<static, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, $this->getParentColumn());
    }

    /**
     * @return HasMany<static, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(static::class, $this->getParentColumn());
    }

    public function ancestors(): Ancestors
    {
        return Ancestors::of($this);
    }

    public function descendants(): Descendants
    {
        return Descendants::of($this);
    }
}
