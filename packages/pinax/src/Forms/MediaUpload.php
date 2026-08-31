<?php

namespace PHPinnacle\Pinax\Forms;

use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Model;
use League\Flysystem\UnableToCheckFileExistence;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use PHPinnacle\Pinax\Models\Media;

class MediaUpload extends FileUpload
{
    public static function getDefaultName(): string
    {
        return 'media';
    }

    public static function performUpload(BaseFileUpload $component, TemporaryUploadedFile $file): ?string
    {
        try {
            if (!$file->exists()) {
                return null;
            }
        } catch (UnableToCheckFileExistence) {
            return null;
        }

        return $component->shouldMoveFiles()
            ? self::performMove($component, $file)
            : self::performCopy($component, $file);
    }

    private static function performCopy(BaseFileUpload $component, TemporaryUploadedFile $file): string
    {
        $storeMethod = $component->getVisibility() === 'public' ? 'storePubliclyAs' : 'storeAs';

        return $file->{$storeMethod}(
            $component->getDirectory(),
            $component->getUploadedFileNameForStorage($file),
            $component->getDiskName(),
        );
    }

    private static function performMove(BaseFileUpload $component, TemporaryUploadedFile $file): string
    {
        $newPath = trim($component->getDirectory() . '/' . $component->getUploadedFileNameForStorage($file), '/');

        $component->getDisk()->move($file->path(), $newPath);

        return $newPath;
    }

    public function setUp(): void
    {
        parent::setUp();

        $this
            ->disk('public')
            ->image()
            ->imageEditor()
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif'])
            ->dehydrated(false)
            ->beforeStateDehydrated(null)
            ->afterStateHydrated(null)
            ->getUploadedFileUsing(
                static fn (self $component, ?Model $record, string $file) => $record !== null
                    ? Media::display($file)
                    : null,
            )
            ->saveUploadedFileUsing(static function (self $component, ?Model $record, TemporaryUploadedFile $file) {
                if ($record === null) {
                    return null;
                }

                if (!($path = self::performUpload($component, $file))) {
                    return null;
                }

                $media = Media::store($record, $file, $component->getDiskName(), $component->getName(), $path);
                $media->save();

                return $media->getKey();
            })
            ->saveRelationshipsUsing(static function (self $component, ?Model $record) {
                $component->deleteAbandonedFiles($record);
                $component->saveUploadedFiles();
            })
            ->loadStateFromRelationshipsUsing(static function (self $component, ?Model $record) {
                $media = Media::fetch($record, $component->getName());

                if (!$component->isMultiple()) {
                    $media = $media->take(1);
                }

                $component->rawState($media->mapWithKeys(fn (Media $m) => [$m->getKey() => $m->getKey()])->all());
            });
    }

    private function deleteAbandonedFiles(?Model $record): void
    {
        if ($record === null) {
            return;
        }

        Media::clear($record, $this->getName(), array_keys($this->getRawState()));
    }
}
