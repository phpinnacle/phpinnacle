<?php

use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;
use Livewire\Component as LivewireComponent;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use PHPinnacle\Pinax\Forms\MediaGallery;
use PHPinnacle\Pinax\Forms\MediaUpload;
use PHPinnacle\Pinax\Models\Media;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Storage::fake('temporary');
    Storage::fake('destination');
    config()->set('livewire.temporary_file_upload.disk', 'temporary');
    (require __DIR__ . '/../../database/migrations/create_pinax_tables.php')->up();
});

function pinax_temporary_file(): TemporaryUploadedFile
{
    $name = 'upload-meta' . base64_encode('example.txt') . '-.txt';
    Storage::disk('temporary')->put('livewire-tmp/' . $name, 'Uploaded content');

    return new TemporaryUploadedFile($name, 'temporary');
}

function pinax_upload_field(bool $gallery, string $disk, bool $move, string $visibility): FileUpload
{
    $livewire = new class extends LivewireComponent implements HasSchemas {
        use InteractsWithSchemas;

        /** @var array<string, mixed> */
        public array $data = [];
    };
    $livewire->setId('pinax-upload-test');
    $livewire->setName('pinax-upload-test');
    $record = new class extends Model {
        public function gallery(): MorphMany
        {
            return Media::folder($this, 'gallery');
        }
    };
    $record->id = '01900000-0000-7000-8000-000000000001';
    $record->exists = true;

    $component = $gallery
        ? MediaGallery::make('gallery')->disk($disk)->directory('custom')
        : MediaUpload::make('media')->disk($disk)->directory('custom');
    Schema::make($livewire)->model($record)->statePath('data')->components([$component])->getComponents();
    if ($gallery) {
        $component = $component->getChildSchema()->getComponent('path', withHidden: true);
    }
    $component
        ->moveFiles($move)
        ->visibility($visibility)
        ->getUploadedFileNameForStorageUsing(fn () => 'saved.txt');

    return $component;
}

it('stores uploads with matching metadata and preserves each field return contract', function (
    bool $gallery,
    bool $move,
    string $disk,
    string $visibility,
) {
    $file = pinax_temporary_file();
    $field = pinax_upload_field($gallery, $disk, $move, $visibility);
    $field->rawState(['upload' => $file]);
    $field->saveUploadedFiles();
    $result = array_values($field->getRawState())[0];

    expect(Storage::disk($disk)->get('custom/saved.txt'))
        ->toBe('Uploaded content')
        ->and(Storage::disk($disk)->getVisibility('custom/saved.txt'))
        ->toBe($visibility);
    if ($gallery) {
        expect($result)
            ->toBe('custom/saved.txt')
            ->and($field->getContainer()->getRawState()['size'])
            ->toBe(16)
            ->and($field->getContainer()->getRawState()['mime'])
            ->toBe('text/plain')
            ->and(Media::query()->count())
            ->toBe(0);
    } else {
        $media = Media::query()->sole();
        expect($result)
            ->toBe($media->id)
            ->and($media->path)
            ->toBe('custom/saved.txt')
            ->and($media->disk)
            ->toBe($disk)
            ->and($media->size)
            ->toBe(16)
            ->and($media->mime)
            ->toBe('text/plain')
            ->and($media->name)
            ->toBe('example.txt');
    }
})
    ->with([false, true])
    ->with([false, true])
    ->with(['temporary', 'destination'])
    ->with(['public', 'private']);

it('does not persist metadata when a transfer fails', function (bool $gallery, bool $move) {
    $file = pinax_temporary_file();
    $field = pinax_upload_field($gallery, disk: 'destination', move: $move, visibility: 'private');
    $disk = Mockery::mock(Storage::disk('destination'))->makePartial();
    $disk->shouldReceive('put')->andReturnFalse();
    Storage::set('destination', $disk);
    $field->rawState(['upload' => $file]);
    $field->saveUploadedFiles();

    expect($field->getRawState())
        ->toBe([])
        ->and(Media::query()->count())
        ->toBe(0)
        ->and($file->exists())
        ->toBeTrue();
})->with([false, true])->with([false, true]);

it('returns no upload for a missing temporary file', function (bool $move) {
    $file = pinax_temporary_file();
    $file->delete();
    $field = pinax_upload_field(gallery: false, disk: 'destination', move: $move, visibility: 'private');

    expect(MediaUpload::performUpload($field, $file))->toBeNull()->and(Media::query()->count())->toBe(0);
})->with([false, true]);

it('removes the source only for a successful requested move', function (bool $move, string $disk) {
    $file = pinax_temporary_file();
    $field = pinax_upload_field(false, $disk, $move, 'private');

    expect(MediaUpload::performUpload($field, $file))
        ->toBe('custom/saved.txt')
        ->and($file->exists())
        ->toBe(!$move)
        ->and(Storage::disk($disk)->get('custom/saved.txt'))
        ->toBe('Uploaded content');
})->with([false, true])->with(['temporary', 'destination']);
