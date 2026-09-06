<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use PHPinnacle\Dendros\AsTree;
use PHPinnacle\Dendros\TreeNode;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $url = getenv('DENDROS_PGSQL_URL');
    if (!$url) {
        $this->markTestSkipped('Set DENDROS_PGSQL_URL to a dedicated PostgreSQL test database.');
    }
    config()->set('database.connections.dendros_test', ['driver' => 'pgsql', 'url' => $url]);
    config()->set('database.default', 'dendros_test');
    DB::beginTransaction();
    DB::statement('CREATE EXTENSION IF NOT EXISTS ltree');
    DB::statement(
        'CREATE TEMPORARY TABLE tree_nodes (node_id integer PRIMARY KEY, parent_node integer, node_path ltree)',
    );
    DB::table('tree_nodes')->insert([
        ['node_id' => 1, 'parent_node' => null, 'node_path' => 'root'],
        ['node_id' => 2, 'parent_node' => 1, 'node_path' => 'root.a'],
        ['node_id' => 3, 'parent_node' => 2, 'node_path' => 'root.a.leaf'],
        ['node_id' => 4, 'parent_node' => 1, 'node_path' => 'root.ab'],
        ['node_id' => 5, 'parent_node' => null, 'node_path' => 'other'],
    ]);
});

afterEach(function () {
    if (DB::connection()->transactionLevel() > 0) {
        DB::rollBack();
    }
});

it('returns the same relatives for lazy eager and existence queries', function (string $relation, array $expected) {
    $nodes = TreeRelationNode::query()->orderBy('node_id')->get();
    $eager = TreeRelationNode::query()->with($relation)->orderBy('node_id')->get()->keyBy('node_id');

    foreach ($nodes as $node) {
        expect($node->{$relation}->modelKeys())
            ->toEqualCanonicalizing($expected[$node->getKey()])
            ->and($eager[$node->getKey()]->{$relation}->values()->modelKeys())
            ->toEqualCanonicalizing($expected[$node->getKey()]);

        foreach ($nodes as $candidate) {
            $matches = TreeRelationNode::query()
                ->whereKey($node->getKey())
                ->whereHas($relation, fn ($query) => $query->where('node_id', $candidate->getKey()))
                ->exists();
            expect($matches)->toBe(in_array($candidate->getKey(), $expected[$node->getKey()], true));
        }
    }

    expect(TreeRelationNode::query()->has($relation)->pluck('node_id')->all())
        ->toEqualCanonicalizing(array_keys(array_filter($expected)));
})->with([
    'ancestors' => ['ancestors', [1 => [], 2 => [1], 3 => [1, 2], 4 => [1], 5 => []]],
    'descendants' => ['descendants', [1 => [2, 3, 4], 2 => [3], 3 => [], 4 => [], 5 => []]],
]);

class TreeRelationNode extends Model implements TreeNode
{
    use AsTree;

    public $timestamps = false;

    protected $table = 'tree_nodes';

    protected $primaryKey = 'node_id';

    public function getPathColumn(): string
    {
        return 'node_path';
    }

    public function getParentColumn(): string
    {
        return 'parent_node';
    }
}
