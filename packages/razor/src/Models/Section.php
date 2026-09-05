<?php

namespace PHPinnacle\Razor\Models;

use Closure;
use Filament\Support\Concerns\EvaluatesClosures;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** @phpstan-type FormComponents array<\Filament\Schemas\Components\Component|\Filament\Actions\Action|\Filament\Actions\ActionGroup|string|\Illuminate\Contracts\Support\Htmlable> */
class Section implements HasLabel
{
    use EvaluatesClosures;

    /**
     * @param array<array-key, mixed> $blocks
     * @param array<string, mixed> $variables
     * @param Closure|FormComponents $renderForm
     * @param Closure|FormComponents|null $previewForm
     */
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

    public function key(string $value): self
    {
        $this->key = $value;

        return $this;
    }

    /**
     * @param array<array-key, mixed> $blocks
     */
    public function blocks(array $blocks): self
    {
        $this->blocks = [...$this->blocks, ...$blocks];

        return $this;
    }

    /**
     * @param array<string, mixed> $value
     */
    public function variables(array $value): self
    {
        foreach ($value as $key => $item) {
            $this->variables[$key] = $item;
        }

        return $this;
    }

    public function render(Closure $renderer): self
    {
        $this->renderAction = $renderer;

        return $this;
    }

    /**
     * @param Closure|FormComponents $schema
     */
    public function preview(Closure $action, Closure|array $schema): self
    {
        $this->previewAction = $action;
        $this->previewForm = $schema;

        return $this;
    }

    /**
     * @param FormComponents|Closure $value
     */
    public function form(array|Closure $value): self
    {
        $this->renderForm = $value;

        return $this;
    }

    public function hasPreviewAction(): bool
    {
        return $this->previewAction !== null;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
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

    /**
     * @return FormComponents
     */
    public function getPreviewForm(): array
    {
        return $this->evaluate($this->previewForm) ?? [];
    }

    /**
     * @return FormComponents
     */
    public function getRenderForm(Model $record): array
    {
        return $this->evaluate($this->renderForm, [
            'record' => $record,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
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
}
