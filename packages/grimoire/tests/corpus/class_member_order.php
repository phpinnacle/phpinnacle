<?php

declare(strict_types=1);

trait MemberTrait {}

abstract class MemberBase {}

/** @mago-expect lint:phpinnacle/class-member-order */
final class MemberOrder extends MemberBase
{
    private function helper(): void {}

    public string $name;

    /** Keeps its documentation when moved. */
    #[Deprecated]
    public const ENABLED = true;

    use MemberTrait;

    private const LIMIT = 10;

    public static array $cache = [];

    private string $secret;

    public function __construct() {}

    public function run(): void {}
}

/** @mago-expect lint:phpinnacle/class-member-order */
enum MemberState
{
    public function label(): string
    {
        return $this->name;
    }

    case Active;

    case Archived;
}
