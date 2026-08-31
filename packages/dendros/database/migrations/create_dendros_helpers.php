<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS ltree;');
        DB::statement('CREATE EXTENSION IF NOT EXISTS pgcrypto;');

        DB::statement('
            CREATE OR REPLACE FUNCTION uuid_to_ltree(u uuid)
            RETURNS ltree
            LANGUAGE plpgsql
            IMMUTABLE
            AS $$
                DECLARE
                    alphabet text := \'0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz\';
                    num numeric := 0;
                    b bytea;
                    i int;
                    remainder int;
                    result text := \'\';
                BEGIN
                    b := uuid_send(u);

                    FOR i IN 0..15 LOOP
                        num := num * 256 + get_byte(b, i);
                    END LOOP;
    
                    WHILE num > 0 LOOP
                        remainder := mod(num, 62);
                        result := substr(alphabet, remainder + 1, 1) || result;
                        num := floor(num / 62);
                    END LOOP;

                    RETURN result::ltree;
                END;
            $$;
        ');

        DB::statement('
            CREATE OR REPLACE FUNCTION ltree_to_uuid(s text)
            RETURNS uuid
            LANGUAGE plpgsql
            IMMUTABLE
            AS $$
                DECLARE
                    alphabet text := \'0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz\';
                    num numeric := 0;
                    i int;
                    c char;
                    b bytea := \'\';
                BEGIN
                    FOR i IN 1..length(s) LOOP
                        c := substr(s, i, 1);
                        num := num * 62 + position(c in alphabet) - 1;
                    END LOOP;

                    FOR i IN REVERSE 0..15 LOOP
                        b := set_byte(b, i, mod(num, 256)) || b;
                        num := floor(num / 256);
                    END LOOP;

                    RETURN uuid_recv(b);
                END;
            $$;
        ');

        DB::statement('
            CREATE OR REPLACE FUNCTION rebuild_path()
            RETURNS trigger
            LANGUAGE plpgsql
            AS $$
                DECLARE
                    parent_path ltree;
                BEGIN
                    IF NEW.parent_id IS NULL THEN
                        NEW.path := uuid_to_ltree(NEW.id);

                        RETURN NEW;
                    END IF;

                    EXECUTE format(\'
                        SELECT path FROM %I.%I WHERE id = $1
                    \', TG_TABLE_SCHEMA, TG_TABLE_NAME)
                    INTO parent_path
                    USING NEW.parent_id;

                    NEW.path := parent_path || uuid_to_ltree(NEW.id);

                    RETURN NEW;
                END;
            $$;
        ');

        DB::statement('
            CREATE OR REPLACE FUNCTION move_path()
            RETURNS trigger
            LANGUAGE plpgsql
            AS $$
                BEGIN
                    EXECUTE format(\'
                        UPDATE %I.%I SET path = $1 || subpath(path, nlevel($2))
                        WHERE path <@ $2 AND id <> $3;
                    \', TG_TABLE_SCHEMA, TG_TABLE_NAME)
                    USING NEW.path, OLD.path, NEW.id;
    
                    RETURN NEW;
                END;
            $$;
        ');
    }
};
