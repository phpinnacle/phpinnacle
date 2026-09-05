<?php

declare(strict_types=1);

/** @mago-expect lint:phpinnacle/no-closure-return-type */
$typedClosure = function (string $value): string {
    return $value;
};

/** @mago-expect lint:phpinnacle/no-closure-return-type */
$staticClosure = static function (): void {};

/** @mago-expect lint:phpinnacle/no-closure-return-type */
$typedArrow = fn (int $value): int => $value;

/** @mago-expect lint:phpinnacle/no-closure-return-type */
$staticArrow = static fn (): ?string => null;

$captured = 'value';

/** @mago-expect lint:phpinnacle/no-closure-return-type */
$capturingClosure = function () use ($captured): string|int {
    return $captured;
};

/** @mago-expect lint:phpinnacle/no-closure-return-type */
$intersectionArrow = fn (Countable&Iterator $value): Countable&Iterator => $value;

/** @mago-expect lint:phpinnacle/no-closure-return-type */
$dnfArrow = fn ((Countable&Iterator)|null $value): (Countable&Iterator)|null => $value;

$untypedClosure = function (string $value) {
    return $value;
};
$untypedStaticClosure = static function () {};
$untypedArrow = fn (int $value) => $value;
$untypedStaticArrow = static fn () => null;

$outerClosure = function () {
    /** @mago-expect lint:phpinnacle/no-closure-return-type */
    return fn (): int => 1;
};

function namedFunction(): string
{
    return 'value';
}

final class TypedMethods
{
    public function typedMethod(): string
    {
        return 'value';
    }
}
