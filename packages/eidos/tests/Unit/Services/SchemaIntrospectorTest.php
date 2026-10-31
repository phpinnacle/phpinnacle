<?php

namespace PHPinnacle\Eidos\Tests\Unit\Services;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Processors\MySqlProcessor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Grammars\MySqlGrammar;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use PHPinnacle\Eidos\Services\SchemaIntrospector;
use PHPinnacle\Eidos\Validation\RuleFactory;
use PHPinnacle\Jason\Schema as JsonSchema;
use PHPinnacle\Jason\SchemaBuilder;
use Tests\TestCase;

uses(TestCase::class);

class GeneratedSchemaProduct extends Model
{
    public $timestamps = false;

    protected $table = 'eidos_schema_products';

    protected $attributes = ['stored_total' => 99];

    protected $guarded = ['id'];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(GeneratedSchemaSupplier::class, 'supplier_id');
    }

    public function purchasingPartner(): BelongsTo
    {
        return $this->belongsTo(GeneratedSchemaSupplier::class, 'supplier_id');
    }
}

class GeneratedSchemaSupplier extends Model
{
    public $timestamps = false;

    protected $table = 'eidos_schema_suppliers';

    protected $attributes = ['name' => 'Model supplier'];

    protected $fillable = ['name', 'code'];

    public function products(): HasMany
    {
        return $this->hasMany(GeneratedSchemaProduct::class, 'supplier_id');
    }
}

class PolymorphicSchemaProduct extends Model
{
    public $timestamps = false;

    protected $table = 'eidos_polymorphic_products';
}

class PolymorphicSchemaOwner extends Model
{
    public $timestamps = false;

    protected $table = 'eidos_polymorphic_owners';

    /** @return MorphMany<PolymorphicSchemaProduct, $this> */
    public function relatedProducts(): MorphMany
    {
        return $this->morphMany(PolymorphicSchemaProduct::class, 'owner');
    }

    /** @return MorphMany<PolymorphicSchemaProduct, $this> */
    public function products(): MorphMany
    {
        return $this->morphMany(PolymorphicSchemaProduct::class, 'owner');
    }

    /** @return MorphOne<PolymorphicSchemaProduct, $this> */
    public function product(): MorphOne
    {
        return $this->morphOne(PolymorphicSchemaProduct::class, 'owner');
    }
}

class CustomSchemaCast extends AsArrayObject {}

class CustomSchemaRecord extends Model
{
    public $timestamps = false;

    protected $table = 'eidos_cast_records';

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(GeneratedSchemaSupplier::class, 'supplier_id');
    }
}

class DepthSchemaProduct extends Model
{
    protected $table = 'eidos_depth_products';

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(DepthSchemaSupplier::class, 'supplier_id');
    }
}

class DepthSchemaSupplier extends Model
{
    protected $table = 'eidos_depth_suppliers';

    public function products(): HasMany
    {
        return $this->hasMany(DepthSchemaProduct::class, 'supplier_id');
    }
}

class UnavailableRelationshipProduct extends DepthSchemaProduct
{
    public function optionalSupplier(): BelongsTo
    {
        throw new \LogicException('Optional relation is unavailable.');
    }
}

class DefaultSchemaRecord extends Model
{
    public $timestamps = false;

    protected $table = 'eidos_default_records';

    protected $attributes = [
        'quantity' => '0',
        'enabled' => '0',
        'label' => '',
        'optional_label' => null,
        'metadata' => '{"source":"model"}',
    ];

    protected $casts = ['enabled' => 'boolean', 'metadata' => 'array'];
}

class MassAssignableSchemaRecord extends Model
{
    public $timestamps = false;

    protected $table = 'eidos_mass_assignable_records';

    protected $fillable = ['name', 'computed_name'];
}

class StructuredDefaultSchemaRecord extends DefaultSchemaRecord
{
    protected $attributes = ['metadata' => ['source' => 'model']];
}

class RequiredSchemaRecord extends DefaultSchemaRecord
{
    protected $guarded = [];
}

it('describes polymorphic collections as arrays and singular relations as nullable objects', function () {
    Schema::create('eidos_polymorphic_owners', function (Blueprint $table) {
        $table->id();
    });
    Schema::create('eidos_polymorphic_products', function (Blueprint $table) {
        $table->id();
        $table->nullableMorphs('owner');
        $table->string('sku');
    });

    $properties = app(SchemaIntrospector::class)->introspect(PolymorphicSchemaOwner::class)->toArray()['properties'];

    expect($properties['related_products']['x-relationship'])
        ->toBe('relatedProducts')
        ->and($properties['products']['x-relationship'])
        ->toBe('products')
        ->and($properties['products']['type'])
        ->toBe('array')
        ->and($properties['products']['readOnly'])
        ->toBeTrue()
        ->and($properties['products']['items']['type'])
        ->toBe('object')
        ->and($properties['products']['items']['properties']['sku']['type'])
        ->toBe('string')
        ->and($properties['product']['type'])
        ->toBe(['object', 'null'])
        ->and($properties['product']['readOnly'])
        ->toBeTrue();
});

it('requires writable nonnullable columns without database or declared model defaults', function (bool $useInstance) {
    Schema::create('eidos_default_records', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('description')->nullable();
        $table->integer('quantity');
        $table->boolean('enabled');
        $table->string('label');
        $table->string('optional_label');
        $table->text('metadata');
        $table->integer('database_default')->default(0);
        $table->timestamp('created_at')->useCurrent();
        $table->string('computed_name')->storedAs('upper(name)');
    });
    $model = new RequiredSchemaRecord;
    $model->name = 'Current name';
    $schema = app(SchemaIntrospector::class)->introspect($useInstance ? $model : RequiredSchemaRecord::class);
    $definition = $schema->toArray();

    expect($definition['required'])
        ->toBe(['name'])
        ->and($definition['properties']['id'])
        ->not->toHaveKey('readOnly')
        ->not->toHaveKey('default')->and($definition['properties']['created_at'])
        ->not->toHaveKey('default')->and(JsonSchema::fromArray($definition)->toArray())->toEqual($definition);
})->with(['model class' => false, 'model instance' => true]);

it('does not require columns marked read only by registered cast schemas', function () {
    Schema::create('eidos_mass_assignable_records', function (Blueprint $table) {
        $table->string('name');
        $table->string('editable');
    });
    $model = new MassAssignableSchemaRecord;
    $model->fillable(['name', 'editable'])->mergeCasts(['name' => 'string']);
    $introspector = new SchemaIntrospector()->registerCast('string', fn () => JsonSchema::fromArray([
        'type' => 'string',
        'readOnly' => true,
    ]));
    $definition = $introspector->introspect($model)->toArray();

    expect($definition['properties']['name']['readOnly'])->toBeTrue()->and($definition['required'])->toBe(['editable']);
});

it('marks columns excluded from mass assignment as read only while retaining defaults', function (
    array $fillable,
    array $guarded,
    array $readOnly,
    array $required,
) {
    Schema::create('eidos_mass_assignable_records', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->boolean('is_admin');
        $table->integer('quantity')->default(7);
        $table->string('computed_name')->storedAs('upper(name)');
    });
    $model = new MassAssignableSchemaRecord;
    $model->fillable($fillable)->guard($guarded);
    $schema = app(SchemaIntrospector::class)->introspect($model)->toArray();
    $properties = $schema['properties'];

    expect($schema['required'] ?? [])->toBe($required);

    foreach ($properties as $name => $definition) {
        if (in_array($name, $readOnly, true)) {
            expect($definition['readOnly'])->toBeTrue();
        } else {
            expect($definition)->not->toHaveKey('readOnly');
        }
    }

    expect($properties['quantity']['default'])->toBe(7);

    $declared = app(SchemaIntrospector::class)->introspect(MassAssignableSchemaRecord::class)->toArray()['properties'];

    expect($declared['name'])
        ->not
        ->toHaveKey('readOnly')
        ->and($declared['is_admin']['readOnly'])
        ->toBeTrue()
        ->and($declared['computed_name']['readOnly'])
        ->toBeTrue();
})->with([
    'fillable allowlist' => [
        ['name', 'computed_name'],
        ['*'],
        ['id', 'is_admin', 'quantity', 'computed_name'],
        ['name'],
    ],
    'guarded denylist' => [[], ['is_admin'], ['is_admin', 'computed_name'], ['name']],
    'fully guarded' => [[], ['*'], ['id', 'name', 'is_admin', 'quantity', 'computed_name'], []],
    'unguarded model' => [[], [], ['computed_name'], ['name', 'is_admin']],
    'fillable auto-increment' => [['id', 'name'], ['*'], ['is_admin', 'quantity', 'computed_name'], ['name']],
    'fillable takes precedence over guarded' => [
        ['name', 'is_admin'],
        ['is_admin'],
        ['id', 'quantity', 'computed_name'],
        ['name', 'is_admin'],
    ],
]);

it('uses model attribute defaults before database literals without reading instance values', function () {
    Schema::create('eidos_default_records', function (Blueprint $table) {
        $table->id();
        $table->integer('quantity')->default(5);
        $table->boolean('enabled')->default(true);
        $table->string('label')->default('database');
        $table->string('optional_label')->nullable()->default('database');
        $table->text('metadata')->default('{}');
        $table->decimal('price')->default(12.5);
        $table->string('quoted_label')->default("supplier's label");
        $table->string('no_default')->nullable();
        $table->string('explicit_null')->nullable()->default(new Expression('NULL'));
        $table->timestamp('created_at')->useCurrent();
    });
    $model = new DefaultSchemaRecord;
    $model->quantity = 99;
    $model->no_default = 'Current value';
    $attributes = $model->getAttributes();
    $introspector = app(SchemaIntrospector::class);
    $definition = $introspector->introspect($model)->toArray();
    $properties = $definition['properties'];

    expect($properties['quantity']['default'])
        ->toBe(0)
        ->and($properties['enabled']['default'])
        ->toBeFalse()
        ->and($properties['label']['default'])
        ->toBe('')
        ->and($properties['optional_label']['default'])
        ->toBeNull()
        ->and($properties['metadata']['default'])
        ->toEqual((object) ['source' => 'model'])
        ->and($properties['price']['default'])
        ->toBe(12.5)
        ->and($properties['quoted_label']['default'])
        ->toBe("supplier's label")
        ->and($properties['explicit_null']['default'])
        ->toBeNull()
        ->and($properties['no_default'])
        ->not->toHaveKey('default')->and($properties['created_at'])
        ->not->toHaveKey('default')->and($properties['id'])
        ->not->toHaveKey('default')->and($model->getAttributes())->toBe($attributes)->and(
            $introspector->introspect(DefaultSchemaRecord::class)->toArray(),
        )->toEqual($definition)->and(JsonSchema::fromArray($definition)->toArray())->toEqual($definition);
});

it('preserves structured model defaults for JSON columns', function () {
    Schema::create('eidos_default_records', function (Blueprint $table) {
        $table->text('metadata');
    });

    $definition = new SchemaIntrospector()
        ->introspect(StructuredDefaultSchemaRecord::class)
        ->toArray();

    expect($definition['properties']['metadata']['default'])->toBe(['source' => 'model']);
});

it('reads database defaults using the connection dialect', function (
    string $driver,
    string $default,
    mixed $expected,
    string $type,
    string $extra = '',
) {
    $connection = \Mockery::mock(Connection::class);
    $builder = \Mockery::mock(Builder::class);
    $model = \Mockery::mock(DefaultSchemaRecord::class)->makePartial();
    $model->guard([]);
    $model->shouldReceive('getConnection')->andReturn($connection);
    $connection->shouldReceive('getSchemaBuilder')->andReturn($builder);
    $connection->shouldReceive('getDriverName')->andReturn($driver);
    if ($driver === 'mysql') {
        $grammar = \Mockery::mock(MySqlGrammar::class);
        $connection->shouldReceive('getSchemaGrammar')->andReturn($grammar);
        $connection->shouldReceive('getTablePrefix')->andReturn('prefix_');
        $connection->shouldReceive('getPostProcessor')->andReturn(new MySqlProcessor);
        $builder
            ->shouldReceive('parseSchemaAndTable')
            ->with('eidos_default_records')
            ->andReturn(['database', 'eidos_default_records']);
        $grammar
            ->shouldReceive('compileColumns')
            ->with('database', 'prefix_eidos_default_records')
            ->andReturn('columns query');
        $connection
            ->shouldReceive('selectFromWriteConnection')
            ->with('columns query')
            ->andReturn([(object) [
                'name' => 'value',
                'type_name' => $type,
                'type' => $type,
                'collation' => null,
                'nullable' => 'NO',
                'default' => $default,
                'extra' => $extra,
                'comment' => '',
                'expression' => '',
            ]]);
    } else {
        $builder
            ->shouldReceive('getColumns')
            ->with('eidos_default_records')
            ->andReturn([[
                'name' => 'value',
                'type_name' => $type,
                'nullable' => false,
                'auto_increment' => false,
                'generation' => null,
                'default' => $default,
            ]]);
    }

    $definition = new SchemaIntrospector()
        ->introspect($model)
        ->toArray();
    $property = $definition['properties']['value'];

    expect($definition)->not->toHaveKey('required');

    if ($expected === null) {
        expect($property)->not->toHaveKey('default');
    } else {
        expect($property['default'])->toBe($expected);
    }
})->with([
    'MySQL string' => ['mysql', "supplier's label", "supplier's label", 'varchar'],
    'MySQL empty string' => ['mysql', '', '', 'varchar'],
    'MySQL parenthesized literal' => ['mysql', '(draft)', '(draft)', 'varchar'],
    'MySQL expression' => ['mysql', 'uuid()', null, 'varchar', 'DEFAULT_GENERATED'],
    'MySQL current timestamp' => ['mysql', 'CURRENT_TIMESTAMP', null, 'timestamp'],
    'MariaDB string' => ['mariadb', "'draft'", 'draft', 'varchar'],
    'PostgreSQL string cast' => ['pgsql', "'supplier''s label'::character varying", "supplier's label", 'varchar'],
    'PostgreSQL number cast' => ['pgsql', "'12.5'::numeric", 12.5, 'numeric'],
    'PostgreSQL boolean' => ['pgsql', 'false', false, 'bool'],
    'SQL Server string' => ['sqlsrv', "(N'supplier''s label')", "supplier's label", 'nvarchar'],
    'SQL Server number' => ['sqlsrv', '((0))', 0, 'int'],
    'SQLite numeric expression' => ['sqlite', '-1.5e2', -150.0, 'real'],
    'PostgreSQL sequence' => ['pgsql', "nextval('records_id_seq'::regclass)", null, 'int8'],
    'PostgreSQL function' => ['pgsql', 'now()', null, 'timestamp'],
    'SQL Server function' => ['sqlsrv', '(getdate())', null, 'datetime'],
    'SQLite expression' => ['sqlite', '(1 + 2)', null, 'integer'],
]);

it('marks stored and virtual generated columns as read only across relations', function () {
    Schema::create('eidos_schema_suppliers', function (Blueprint $table) {
        $table->id();
        $table->string('name')->default('Database supplier');
        $table->string('code');
        $table->string('display_name')->storedAs('upper(name)');
    });
    Schema::create('eidos_schema_products', function (Blueprint $table) {
        $table->id();
        $table->string('sku');
        $table->unsignedBigInteger('supplier_id')->nullable();
        $table->integer('quantity')->default(2);
        $table->integer('stored_total')->storedAs('quantity * 2');
        $table->string('virtual_label')->nullable()->virtualAs("'item-' || quantity");
    });

    $introspector = app(SchemaIntrospector::class);
    $schema = $introspector->introspect(new GeneratedSchemaProduct)->toArray();

    expect($schema['properties']['supplier']['x-foreign-key'])
        ->toBe('supplier_id')
        ->and($schema['properties']['purchasing_partner']['x-foreign-key'])
        ->toBe('supplier_id')
        ->and($schema['required'])
        ->toBe(['sku'])
        ->and($schema['properties']['supplier']['required'])
        ->toBe(['code'])
        ->and($schema['properties']['supplier']['readOnly'])
        ->toBeTrue()
        ->and($schema['properties']['stored_total'])
        ->toBe(['type' => ['integer', 'null'], 'readOnly' => true])
        ->and($schema['properties']['virtual_label'])
        ->toBe(['type' => ['string', 'null'], 'readOnly' => true])
        ->and($schema['properties']['quantity'])
        ->not
        ->toHaveKey('readOnly')
        ->and($schema['properties']['supplier']['properties']['display_name']['readOnly'])
        ->toBeTrue()
        ->and($schema['properties']['supplier']['properties']['name']['default'])
        ->toBe('Model supplier')
        ->and($schema['properties']['supplier']['properties']['id']['readOnly'])
        ->toBeTrue()
        ->and($introspector->introspect(GeneratedSchemaProduct::class)->toArray())
        ->toBe($schema);

    $supplierProperties = $introspector->introspect(new GeneratedSchemaSupplier)->toArray()['properties'];

    expect($supplierProperties)
        ->toHaveKeys(['name', 'display_name'])
        ->not->toHaveKey('quantity');

    expect($supplierProperties['products']['type'])
        ->toBe('array')
        ->and($supplierProperties['products']['readOnly'])
        ->toBeTrue()
        ->and($supplierProperties['products']['items']['required'])
        ->toBe(['sku'])
        ->and($supplierProperties['products']['items']['properties']['stored_total']['readOnly'])
        ->toBeTrue()
        ->and($supplierProperties['products']['items']['properties']['quantity']['default'])
        ->toBe(2)
        ->and($supplierProperties['products']['items']['properties']['id']['readOnly'])
        ->toBeTrue()
        ->and($supplierProperties['products']['items']['properties']['quantity'])
        ->not->toHaveKey('readOnly')->and($supplierProperties['products']['items']['properties'])
        ->not->toHaveKey('supplier');
});

it('uses registered cast schemas and preserves annotations, nullability and generated fields', function () {
    Schema::create('eidos_schema_suppliers', function (Blueprint $table) {
        $table->id();
        $table->string('name');
    });
    Schema::create('eidos_cast_records', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('supplier_id')->nullable();
        $table->text('metadata')->nullable();
        $table->string('label');
        $table->string('generated_label')->storedAs('upper(label)');
    });
    $model = new CustomSchemaRecord;
    $model->mergeCasts(['metadata' => CustomSchemaCast::class, 'generated_label' => CustomSchemaCast::class]);
    $introspector = new SchemaIntrospector;
    $introspector->registerCast(CustomSchemaCast::class, fn () => JsonSchema::fromArray([
        'type' => 'object',
        'properties' => ['name' => ['type' => 'string']],
        'x-icon' => 'heroicon-o-user',
    ]));

    $properties = $introspector
        ->introspect($model)
        ->toArray()['properties'];

    expect($properties['metadata']['type'])
        ->toBe(['object', 'null'])
        ->and($properties['metadata']['properties']['name']['type'])
        ->toBe('string')
        ->and($properties['generated_label']['type'])
        ->toBe(['object', 'null'])
        ->and($properties['generated_label']['readOnly'])
        ->toBeTrue()
        ->and($properties['label']['type'])
        ->toBe('string');

    expect($properties['metadata']['x-icon'])->toBe('heroicon-o-user');
});

it('preserves boolean cast schemas when adding column annotations and defaults', function (bool $accepts) {
    Schema::create('eidos_default_records', function (Blueprint $table) {
        $table->integer('quantity');
    });

    $schema = new SchemaIntrospector()
        ->registerCast('integer', fn () => JsonSchema::fromArray($accepts))
        ->introspect(new DefaultSchemaRecord()->mergeCasts(['quantity' => 'integer']));
    $quantity = $schema->properties()['quantity'];

    expect($quantity->keyword('readOnly'))
        ->toBeTrue()
        ->and($quantity->keyword('default'))
        ->toBe('0')
        ->and(Validator::make(['quantity' => 1], new RuleFactory()->make($schema))->passes())
        ->toBe($accepts);
})->with([true, false]);

it('passes model, attribute and cast arguments to handlers for root and related fields', function () {
    Schema::create('eidos_schema_suppliers', function (Blueprint $table) {
        $table->id();
        $table->string('name');
    });
    Schema::create('eidos_cast_records', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('supplier_id')->nullable();
        $table->string('label');
        $table->string('plain_label');
    });
    $model = new CustomSchemaRecord;
    $model->mergeCasts(['label' => CustomSchemaCast::class . ':10,20', 'plain_label' => CustomSchemaCast::class]);
    $introspector = new SchemaIntrospector;
    $introspector->registerCast(CustomSchemaCast::class, fn (
        Model $model,
        string $attribute,
        array $arguments,
    ) => SchemaBuilder::make()
        ->type('string')
        ->title($model->getTable() . '.' . $attribute)
        ->enum($arguments === [] ? ['plain'] : $arguments)
        ->build());
    $definition = $introspector->introspect($model)->toArray();

    expect($definition['properties']['label']['title'])
        ->toBe('eidos_cast_records.label')
        ->and($definition['properties']['label']['enum'])
        ->toBe(['10', '20'])
        ->and($definition['properties']['plain_label']['enum'])
        ->toBe(['plain']);

    $introspector->registerCast('int', fn (Model $model, string $attribute, array $arguments) => SchemaBuilder::make()
        ->type('integer')
        ->title($model->getTable() . '.' . $attribute)
        ->build());
    $definition = $introspector->introspect($model)->toArray();

    expect($definition['properties']['supplier']['properties']['id']['title'])->toBe('eidos_schema_suppliers.id');
});

it('keeps default cast and database types when no handler is registered', function () {
    Schema::create('eidos_schema_suppliers', function (Blueprint $table) {
        $table->id();
        $table->string('name');
    });
    Schema::create('eidos_cast_records', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('supplier_id')->nullable();
        $table->text('metadata');
        $table->integer('quantity');
    });
    $model = new CustomSchemaRecord;
    $model->mergeCasts(['metadata' => CustomSchemaCast::class]);

    $properties = new SchemaIntrospector()
        ->introspect($model)
        ->toArray()['properties'];

    expect($properties['metadata']['type'])
        ->toBe(['object', 'array'])
        ->and($properties['quantity']['type'])
        ->toBe('integer');
});

it('expands relationships to the requested depth and bounds cyclic paths', function () {
    Schema::create('eidos_depth_suppliers', function (Blueprint $table) {
        $table->id();
        $table->string('name');
    });
    Schema::create('eidos_depth_products', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('supplier_id');
        $table->string('sku');
    });
    $introspector = app(SchemaIntrospector::class);
    $columns = $introspector->introspect(DepthSchemaProduct::class, relationshipDepth: 0)->toArray();
    $default = $introspector->introspect(DepthSchemaProduct::class)->toArray();
    $deep = $introspector->introspect(DepthSchemaProduct::class, relationshipDepth: 3)->toArray();

    expect($columns['properties'])
        ->toHaveKey('sku')
        ->not->toHaveKey('supplier')->and($default['properties']['supplier']['properties'])->toHaveKey('name')
        ->not->toHaveKey('products');
    $products = $deep['properties']['supplier']['properties']['products'];
    expect($products['type'])
        ->toBe('array')
        ->and($products['x-relationship'])
        ->toBe('products')
        ->and($products['items']['properties']['supplier']['x-foreign-key'])
        ->toBe('supplier_id')
        ->and($products['items']['properties']['supplier']['properties'])
        ->toHaveKey('name')
        ->not->toHaveKey('products')->and($products['items']['properties']['supplier']['type'])->toBe([
            'object',
            'null',
        ])->and($products['items']['properties']['supplier'])
        ->not->toHaveKey('title');
    expect(fn () => $introspector->introspect(UnavailableRelationshipProduct::class, relationshipDepth: 3))
        ->toThrow(\LogicException::class, 'Optional relation is unavailable.');
    $introspector->excludeRelationship(UnavailableRelationshipProduct::class, 'optionalSupplier');
    $filtered = $introspector->introspect(UnavailableRelationshipProduct::class, relationshipDepth: 3)->toArray();
    expect($filtered['properties'])->toHaveKey('supplier')->not->toHaveKey('optional_supplier');
});
