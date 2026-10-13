<?php

namespace PHPinnacle\Common\Concerns;

use Closure;

trait HasJsonSchemaConstraints
{
    /** @var bool|Closure(): bool */
    private bool|Closure $hasFormat = false;

    /** @var bool|Closure(): bool */
    private bool|Closure $hasProperties = true;

    /** @var bool|Closure(): bool */
    private bool|Closure $hasRequiredMarker = false;

    /** @var bool|Closure(): bool */
    private bool|Closure $hasConstraints = false;

    /** @var bool|Closure(): bool */
    private bool|Closure $isCompact = false;

    /** @var bool|Closure(): bool */
    private bool|Closure $isSearchable = false;

    /** @var bool|Closure(): bool */
    private bool|Closure $hasIcons = true;

    /** @var bool|Closure(): bool */
    private bool|Closure $hasColors = true;

    /** @var bool|Closure(): bool */
    private bool|Closure $hasDescriptions = true;

    /** @param bool|Closure(): bool $condition */
    public function format(bool|Closure $condition = true): static
    {
        $this->hasFormat = $condition;

        return $this;
    }

    public function isFormatVisible(): bool
    {
        return $this->evaluate($this->hasFormat);
    }

    /** @param bool|Closure(): bool $condition */
    public function properties(bool|Closure $condition = true): static
    {
        $this->hasProperties = $condition;

        return $this;
    }

    public function arePropertiesVisible(): bool
    {
        return $this->evaluate($this->hasProperties);
    }

    /** @param bool|Closure(): bool $condition */
    public function requiredMarker(bool|Closure $condition = true): static
    {
        $this->hasRequiredMarker = $condition;

        return $this;
    }

    public function isRequiredMarkerVisible(): bool
    {
        return $this->evaluate($this->hasRequiredMarker);
    }

    /** @param bool|Closure(): bool $condition */
    public function constraints(bool|Closure $condition = true): static
    {
        $this->hasConstraints = $condition;

        return $this;
    }

    public function areConstraintsVisible(): bool
    {
        return $this->evaluate($this->hasConstraints);
    }

    /** @param bool|Closure(): bool $condition */
    public function compact(bool|Closure $condition = true): static
    {
        $this->isCompact = $condition;

        return $this;
    }

    public function isCompact(): bool
    {
        return $this->evaluate($this->isCompact);
    }

    /** @param bool|Closure(): bool $condition */
    public function searchable(bool|Closure $condition = true): static
    {
        $this->isSearchable = $condition;

        return $this;
    }

    public function isSearchable(): bool
    {
        return $this->evaluate($this->isSearchable);
    }

    /** @param bool|Closure(): bool $condition */
    public function icons(bool|Closure $condition = true): static
    {
        $this->hasIcons = $condition;

        return $this;
    }

    public function areIconsVisible(): bool
    {
        return $this->evaluate($this->hasIcons);
    }

    /** @param bool|Closure(): bool $condition */
    public function colors(bool|Closure $condition = true): static
    {
        $this->hasColors = $condition;

        return $this;
    }

    public function areColorsVisible(): bool
    {
        return $this->evaluate($this->hasColors);
    }

    /** @param bool|Closure(): bool $condition */
    public function descriptions(bool|Closure $condition = true): static
    {
        $this->hasDescriptions = $condition;

        return $this;
    }

    public function areDescriptionsVisible(): bool
    {
        return $this->evaluate($this->hasDescriptions);
    }
}
