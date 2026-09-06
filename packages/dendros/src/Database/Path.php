<?php

namespace PHPinnacle\Dendros\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Path
{
    public static function isAncestorOf(string $left, string $right): bool
    {
        return $left === $right || str_starts_with($right, $left . '.');
    }

    public static function setup(
        string $table,
        string $parent = 'parent_id',
        string $path = 'path',
        string $depth = 'depth',
    ): void {
        Schema::table($table, function (Blueprint $schema) use ($table, $parent, $path, $depth) {
            $schema->rawColumn($path, 'ltree');
            $schema->unsignedInteger($depth)->storedAs(sprintf('nlevel(%s) - 1', $path));

            $schema
                ->foreign($parent)
                ->references('id')
                ->on($table)
                ->cascadeOnDelete();
        });

        DB::statement(sprintf('
            CREATE TRIGGER %1$s_trigger_rebuild_path
            BEFORE INSERT OR UPDATE OF %2$s
            ON %1$s
            FOR EACH ROW
            EXECUTE FUNCTION rebuild_path();
        ', $table, $parent));

        DB::statement(sprintf('
            CREATE TRIGGER %1$s_trigger_move_path
            AFTER UPDATE OF %2$s
            ON %1$s
            FOR EACH ROW
            EXECUTE FUNCTION move_path();
        ', $table, $path));
    }
}
