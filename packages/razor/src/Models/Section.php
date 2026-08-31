<?php

namespace PHPinnacle\Razor\Models;

use Closure;
use Filament\Support\Concerns\EvaluatesClosures;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Section implements HasLabel
{
    use EvaluatesClosures;

    public function __construct(
        public string $key,
        public string $label,
        public array $blocks = [],
        public array $variables = [],
        private ?Closure $renderAction = null,
        private Closure|array $renderForm = [],
        private ?Closure $previewAction = null,
        private Closure|array|null $previewForm = null,
    ) {}

    public static function make(string $label): self
    {
        if (class_exists($label)) {
            $name = Str::of($label)->afterLast('\\');

            return new self($name->slug(), $name->headline());
        }

        return new self(Str::slug($label), $label);
    }

    public function blocks(array $blocks): self
    {
        $this->blocks = [...$this->blocks, ...$blocks];

        return $this;
    }

    public function form(array|Closure $value): self
    {
        $this->renderForm = $value;

        return $this;
    }

    public function getDocument(Model $record, array $data): Document
    {
        return $this->evaluate($this->renderAction, [
            'record' => $record,
            'data' => $data,
        ]);
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getPreviewData(array $data): array
    {
        if ($this->previewAction === null) {
            return [];
        }

        $result = $this->evaluate($this->previewAction, [
            'data' => $data,
        ]);

        if ($result instanceof Arrayable) {
            $result = $result->toArray();
        }

        return is_array($result) ? $result : [];
    }

    public function getPreviewForm(): array
    {
        return $this->evaluate($this->previewForm) ?? [];
    }

    public function getRenderForm(Model $record): array
    {
        return $this->evaluate($this->renderForm, [
            'record' => $record,
        ]);
    }

    public function hasPreviewAction(): bool
    {
        return $this->previewAction !== null;
    }

    public function key(string $value): self
    {
        $this->key = $value;

        return $this;
    }

    public function preview(Closure $action, Closure|array $schema): self
    {
        $this->previewAction = $action;
        $this->previewForm = $schema;

        return $this;
    }

    public function render(Closure $renderer): self
    {
        $this->renderAction = $renderer;

        return $this;
    }

    public function variables(array $value): self
    {
        foreach ($value as $key => $item) {
            $this->variables[$key] = $item;
        }

        return $this;
    }
}
