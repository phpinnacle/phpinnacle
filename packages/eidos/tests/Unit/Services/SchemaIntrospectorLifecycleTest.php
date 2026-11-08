<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use PHPinnacle\Eidos\Services\SchemaIntrospector;
use PHPinnacle\Jason\Schema;
use PHPinnacle\Jason\SchemaBuilder;
use Tests\TestCase;

uses(TestCase::class);

enum EidosIntrospectorCountry: string
{
    case Poland = 'PL';
    case Germany = 'DE';
}

class EidosIntrospectorModel extends Model
{
    public $timestamps = false;

    protected $table = 'eidos_introspector_models';

    protected $casts = ['name' => 'string'];
}

beforeEach(function () {
    DatabaseSchema::create('eidos_introspector_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
    });
});

it('applies shared cast registrations to later introspections of model classes and instances', function (bool $useInstance) {
    $introspector = app(SchemaIntrospector::class);
    $model = $useInstance ? new EidosIntrospectorModel : EidosIntrospectorModel::class;
    $schema = $introspector->introspect($model);
    $introspector->registerCast('string', fn () => SchemaBuilder::string()
        ->title('Registered cast')
        ->build());
    $nextSchema = app(SchemaIntrospector::class)->introspect($model);

    expect($schema->properties()['name']->type())
        ->toBe('string')
        ->and($schema->properties()['name']->title())
        ->toBeNull()
        ->and($nextSchema->properties()['name']->type())
        ->toBe('string')
        ->and($nextSchema->properties()['name']->title())
        ->toBe('Registered cast');
})->with(['model class' => false, 'model instance' => true]);

it('reflects column changes on the next request', function () {
    $introspector = app(SchemaIntrospector::class);
    $original = $introspector->introspect(EidosIntrospectorModel::class)->toArray();

    DatabaseSchema::table('eidos_introspector_models', function (Blueprint $table) {
        $table->string('description')->nullable();
    });

    $schema = $introspector->introspect(EidosIntrospectorModel::class)->toArray();

    expect($original['properties'])
        ->not
        ->toHaveKey('description')
        ->and($schema['properties']['description']['type'])
        ->toBe(['string', 'null']);
});

it('uses the supplied model instance table', function () {
    DatabaseSchema::create('eidos_alternative_models', function (Blueprint $table) {
        $table->id();
        $table->string('description');
    });
    $model = new EidosIntrospectorModel;
    $model->setTable('eidos_alternative_models');

    $properties = app(SchemaIntrospector::class)->introspect($model)->toArray()['properties'];

    expect($properties)->toHaveKey('description')->not->toHaveKey('name');
});

it('keeps open model schemas by default and preserves field types when closing a model union explicitly', function (bool $closed) {
    DatabaseSchema::table('eidos_introspector_models', function (Blueprint $table) {
        $table->json('metadata')->nullable();
    });
    DatabaseSchema::create('eidos_alternative_models', function (Blueprint $table) {
        $table->id();
        $table->integer('quantity');
    });
    $model = new EidosIntrospectorModel()->mergeCasts(['metadata' => 'array']);
    $other = new EidosIntrospectorModel()->setTable('eidos_alternative_models');
    $introspector = app(SchemaIntrospector::class);

    $schema = $introspector->introspect($model, closed: $closed);
    $otherSchema = $introspector->introspect($other, closed: $closed);
    $union = Schema::anyOf([$schema, $otherSchema]);

    expect($union->property('name')->types())->toBe($closed ? ['string'] : []);
    expect($union->effectiveRequired())->toBe([]);
    expect($schema->hasKeyword('additionalProperties'))->toBe($closed);
    expect($schema->properties()['metadata']->hasKeyword('additionalProperties'))->toBeFalse();
    expect($schema->atPath(['undeclared']) === null)->toBe($closed);
    expect($introspector->introspect($model)->hasKeyword('additionalProperties'))->toBeFalse();
})->with(['open' => false, 'closed' => true]);

it('closes related model objects without changing relationship depth or arbitrary JSON casts', function () {
    DatabaseSchema::table('eidos_introspector_models', function (Blueprint $table) {
        $table->unsignedBigInteger('parent_id')->nullable();
        $table->json('metadata')->nullable();
    });
    $model = new class extends EidosIntrospectorModel {
        protected $casts = ['metadata' => 'array'];

        public function parent(): BelongsTo
        {
            return $this->belongsTo(self::class, 'parent_id');
        }

        public function children(): HasMany
        {
            return $this->hasMany(self::class, 'parent_id');
        }
    };

    $introspector = app(SchemaIntrospector::class)->registerCast(
        'array',
        fn () => SchemaBuilder::object()->additionalProperties(Schema::number())->build(),
    );

    $schema = $introspector->introspect($model, closed: true);
    $parent = $schema->properties()['parent'];
    $child = $schema->properties()['children']->items();

    expect($parent->additionalProperties()->deniesAny())->toBeTrue();
    expect($parent->types())->toBe(['object', 'null']);
    expect($parent->properties())->not->toHaveKey('parent');
    expect($child->additionalProperties()->deniesAny())->toBeTrue();
    expect($child->properties()['metadata']->additionalProperties()->type())->toBe('number');
});

it('represents a nullable enum column without composition branches', function () {
    DatabaseSchema::table('eidos_introspector_models', function (Blueprint $table) {
        $table->string('country')->nullable();
    });
    $model = new EidosIntrospectorModel()->mergeCasts(['country' => EidosIntrospectorCountry::class]);

    $country = app(SchemaIntrospector::class)->introspect($model)->properties()['country'];

    expect($country->type())->toBe(['string', 'null']);
    expect($country->enum())->toBe(['PL', 'DE', null]);
    expect($country->hasKeyword('anyOf'))->toBeFalse();
});
