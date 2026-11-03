<?php

use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Livewire;
use PHPinnacle\Common\Forms\TaxNumber;
use PHPinnacle\Common\TaxNumber\Countries\Belarus;
use PHPinnacle\Common\TaxNumber\Countries\Poland;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Http::preventStrayRequests();
    view()->share('errors', new ViewErrorBag);
});

it('hydrates and dehydrates independent tax numbers without contacting the source', function () {
    $first = TaxNumber::make('first')
        ->countries([new Belarus])
        ->country('BY');
    $second = TaxNumber::make('second')
        ->countries([new Belarus])
        ->country('BY')
        ->cache(fn () => 'array', 60);
    $schema = common_tax_number_form([$first, $second]);
    $schema->fill(['first' => '100000007', 'second' => 'AA1234562']);

    expect($schema->getState())->toBe(['first' => '100000007', 'second' => 'AA1234562']);
    expect($first->getCacheStore())->toBeNull();
    expect($second->getCacheStore())->toBe('array');
    expect($first->getCacheTtl())->toBe(3600);
    expect($second->getCacheTtl())->toBe(60);
    expect($first->getSuffixActions()['taxNumberInfo']->isDisabled())->toBeFalse();
    expect($first->toHtml())->toContain('Taxpayer information', 'taxNumberPopup')->not->toContain('@js(');
    Http::assertNothingSent();
});

it('validates format with a translated message at form submission', function (mixed $state) {
    $field = TaxNumber::make('number')
        ->countries([new Belarus])
        ->country('BY');
    $schema = common_tax_number_form([$field]);
    $schema->fill(['number' => $state]);

    try {
        $schema->getState();
        test()->fail('The invalid number was accepted.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toBe([
            'data.number' => ['Invalid tax number format for country BY.'],
        ]);
    }

    Http::assertNothingSent();
})->with(['checksum' => '100000008', 'array' => [['100000007']]]);

it('keeps required validation optional and permits unconfigured country fields', function () {
    $field = TaxNumber::make('number')
        ->countries([new Belarus])
        ->country('BY');
    $schema = common_tax_number_form([$field]);
    $schema->fill(['number' => null]);
    expect($schema->getState())->toBe(['number' => null]);
    expect($field->getSuffixActions()['taxNumberInfo']->isDisabled())->toBeTrue();

    $field->required();
    expect($schema->getState(...))->toThrow(ValidationException::class);
    $field->country(null);
    $schema->fill(['number' => 'unrestricted']);
    expect($schema->getState())->toBe(['number' => 'unrestricted']);
    Http::assertNothingSent();
});

it('reevaluates country and number availability after state updates', function () {
    $field = TaxNumber::make('number')
        ->countries([new Belarus])
        ->country(fn (Get $get) => $get('country'));
    $schema = common_tax_number_form([$field]);
    $schema->fill(['number' => null]);
    $action = $field->getSuffixActions()['taxNumberInfo'];

    expect($action->isDisabled())->toBeTrue();
    expect($action->getTooltip())->toBe('No country is set for this field.');
    $schema->getLivewire()->data['country'] = 'XX';
    expect($action->isDisabled())->toBeTrue();
    expect($action->getTooltip())->toBe('No taxpayer information source is configured for this country.');
    $schema->getLivewire()->data['country'] = 'BY';
    $field->state('100000007');
    expect($action->isDisabled())->toBeFalse();
    $field->state('invalid');
    expect($action->isDisabled())->toBeTrue();
    Http::assertNothingSent();
});

it('uses each field country list and reevaluates dynamic lists independently', function () {
    $first = TaxNumber::make('first')
        ->countries([new Belarus])
        ->country('BY');
    $second = TaxNumber::make('second')
        ->countries(fn (Get $get) => $get('poland_only') ? [new Poland] : [new Belarus])
        ->country('BY');
    $schema = common_tax_number_form([$first, $second]);
    $schema->fill(['first' => '100000007', 'second' => '100000007']);
    expect($schema->getState())->toBe(['first' => '100000007', 'second' => '100000007']);

    $schema->getLivewire()->data['poland_only'] = true;
    expect($first->getSuffixActions()['taxNumberInfo']->isDisabled())->toBeFalse();
    expect($second->lookupForJs())->toBe([
        'message' => 'No taxpayer information source is configured for this country.',
        'lookup' => null,
    ]);
    try {
        $schema->getState();
        test()->fail('The field accepted a country outside its own list.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toBe(['data.second' => ['Invalid tax number format for country BY.']]);
    }

    $second->country('PL')->state('5260250274');
    expect($schema->getState())->toBe(['first' => '100000007', 'second' => '5260250274']);
    Http::assertNothingSent();
});

it('requires countries to be configured explicitly on the field', function () {
    $field = TaxNumber::make('number')->country('BY');
    $schema = common_tax_number_form([$field]);
    $schema->fill(['number' => '100000007']);

    expect($field->getCountries())->toBe([]);
    expect($field->getSuffixActions()['taxNumberInfo']->isDisabled())->toBeTrue();
    expect($schema->getState(...))->toThrow(ValidationException::class);
    Http::assertNothingSent();
});

it('loads taxpayer information only when requested and preserves text for safe client rendering', function () {
    Http::fake(['egr.gov.by/api/v2/egr/getShortInfoByRegNum/100000007' => Http::response([[
        'vn' => '<script>alert(1)</script>',
        'nsi00219' => ['vnsostk' => 'Active'],
    ]])]);
    $field = TaxNumber::make('number')
        ->countries([new Belarus])
        ->country('BY')
        ->cache('array', 60);
    $schema = common_tax_number_form([$field]);
    $schema->fill(['number' => '100000007']);
    expect($field->toHtml())->toContain('taxNumberPopup')->not->toContain('<script>alert(1)</script>');
    Http::assertNothingSent();

    expect($field->lookupForJs())->toBe([
        'message' => null,
        'lookup' => ['name' => '<script>alert(1)</script>', 'status' => 'Active', 'address' => null],
    ]);
    $field->lookupForJs();
    Http::assertSentCount(1);
});

it('does not query the source for disabled or hidden fields', function (string $condition) {
    $field = TaxNumber::make('number')
        ->countries([new Belarus])
        ->country('BY');
    $schema = common_tax_number_form([$field]);
    $schema->fill(['number' => '100000007']);
    $field->{$condition}();

    expect($field->lookupForJs())->toBe(['message' => null, 'lookup' => null]);
    Http::assertNothingSent();
})->with(['disabled', 'hidden']);

it('shows distinct lookup messages and still saves a valid number', function (
    array $payload,
    int $status,
    string $message,
) {
    Http::fake(['egr.gov.by/api/v2/egr/getShortInfoByRegNum/100000007' => Http::response($payload, $status)]);
    $field = TaxNumber::make('number')
        ->countries([new Belarus])
        ->country('BY');
    $schema = common_tax_number_form([$field]);
    $schema->fill(['number' => '100000007']);

    expect($field->lookupForJs())->toBe(['message' => $message, 'lookup' => null]);
    expect($schema->getState())->toBe(['number' => '100000007']);
    Http::assertSentCount(1);
})->with([
    'not found' => [[], 200, 'Record not found.'],
    'unavailable' => [[], 403, 'The source is temporarily unavailable. Please try again later.'],
]);

/** @param array<TaxNumber> $fields */
function common_tax_number_form(array $fields): Schema
{
    $livewire = new class extends Component implements HasSchemas {
        use InteractsWithSchemas;

        /** @var array<string, mixed> */
        public array $data = [];

        /** @param array<TaxNumber> $fields */
        public function taxNumberForm(array $fields): Schema
        {
            return $this->cacheSchema('form', Schema::make($this)->statePath('data')->components($fields));
        }
    };
    $livewire->setId('common-tax-number-test');
    $livewire->setName('common-tax-number-test');

    return $livewire->taxNumberForm($fields);
}

it('returns popup information after Livewire updates and blocks invalid requests', function () {
    Http::fake(['egr.gov.by/api/v2/egr/getShortInfoByRegNum/100000007' => Http::response([[
        'vn' => 'Livewire taxpayer',
    ]])]);
    $page = Livewire::test(CommonTaxNumberHost::class);
    $key = $page->instance()->form->getComponent('number')->getKey();

    $page
        ->set('data.number', 'invalid')
        ->call('callSchemaComponentMethod', $key, 'lookupForJs')
        ->assertReturned(['message' => 'Enter a valid tax number to retrieve the information.', 'lookup' => null]);
    Http::assertNothingSent();
    $page
        ->set('data.number', '100000007')
        ->call('callSchemaComponentMethod', $key, 'lookupForJs')
        ->assertReturned([
            'message' => null,
            'lookup' => ['name' => 'Livewire taxpayer', 'status' => null, 'address' => null],
        ]);
    Http::assertSentCount(1);
    $page->call('save')->assertSet('saved', ['number' => '100000007']);
});

class CommonTaxNumberHost extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    /** @var array<string, mixed> */
    public array $data = [];

    /** @var array<string, mixed>|null */
    public ?array $saved = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                TaxNumber::make('number')
                    ->countries([new Belarus])
                    ->label('Number')
                    ->country('BY'),
            ]);
    }

    public function save(): void
    {
        $this->saved = $this->form->getState();
    }

    public function render(): string
    {
        return '<div><form wire:submit="save">{{ $this->form }}<button type="submit">Save</button></form></div>';
    }
}
