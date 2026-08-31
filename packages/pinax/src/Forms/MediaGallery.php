<?php

namespace PHPinnacle\Pinax\Forms;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use PHPinnacle\Pinax\Mark;

class MediaGallery extends Repeater
{
    private string $disk = 'public';

    private ?string $directory = null;

    private array $marks = [];

    public static function getDefaultName(): string
    {
        return 'gallery';
    }

    public function directory(string $value): self
    {
        $this->directory = $value;

        return $this;
    }

    public function disk(string $value): self
    {
        $this->disk = $value;

        return $this;
    }

    public function marks(Mark|string ...$marks): self
    {
        $this->marks = [
            ...$this->marks,
            ...$marks,
        ];

        return $this;
    }

    public function setUp(): void
    {
        parent::setUp();

        $this
            ->relationship()
            ->defaultItems(1)
            ->itemLabel(fn (array $state) => $state['name'] ?? null)
            ->addActionAlignment(Alignment::Left)
            ->addAction(function (Action $action) {
                $action
                    ->label(__('phpinnacle-pinax::forms.gallery.actions.add'))
                    ->icon('phosphor-upload');
            })
            ->extraItemActions([
                fn () => $this->getMarks()->map(
                    static fn (Mark $mark) => Action::make($mark->key)
                        ->label($mark->label)
                        ->iconButton()
                        ->color(
                            static fn (
                                array $arguments,
                                Repeater $component,
                            ) => $mark->getColor($component->getRawItemState($arguments['item'])),
                        )
                        ->icon(
                            static fn (
                                array $arguments,
                                Repeater $component,
                            ) => $mark->getIcon($component->getRawItemState($arguments['item'])),
                        )
                        ->action(static function (array $arguments, Repeater $component) use ($mark) {
                            $component->state($mark->toggle($arguments['item'], $component->getState()));
                            $component->callAfterStateUpdated();
                        }),
                )->all(),
            ])
            ->schema(fn () => [
                Hidden::make('id'),
                Hidden::make('size'),
                Hidden::make('disk'),
                Hidden::make('mime'),
                Hidden::make('marks')
                    ->default(function (Hidden $component) {
                        $state = $component->getParentRepeater()->getState();
                        $state = Arr::except($state, [array_key_last($state)]);

                        $default = $this->getMarks()->filter(function (Mark $mark) use ($state) {
                            return $mark->default && (!$mark->unique || !$mark->exists($state));
                        });

                        return $default->map(fn (Mark $mark) => $mark->key)->all();
                    }),
                FileUpload::make('path')
                    ->hiddenLabel()
                    ->disk($this->disk)
                    ->directory($this->directory)
                    ->image()
                    ->imageEditor()
                    ->required()
                    ->storeFileNamesIn('name')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif'])
                    ->automaticallyResizeImagesMode('contain')
                    ->automaticallyResizeImagesToWidth('4096')
                    ->automaticallyResizeImagesToHeight('4096')
                    ->saveUploadedFileUsing(static function (
                        Set $set,
                        FileUpload $component,
                        TemporaryUploadedFile $file,
                    ) {
                        if (!($path = MediaUpload::performUpload($component, $file))) {
                            return null;
                        }

                        $set('size', $file->getSize());
                        $set('mime', $file->getMimeType());
                        $set('disk', $component->getDiskName());

                        return $path;
                    }),
            ]);
    }

    private function getMarks(): Collection
    {
        return collect($this->marks)->map(static fn (Mark|string $m) => is_string($m) ? Mark::make($m) : $m);
    }
}
