<?php

declare(strict_types=1);

$source = file_get_contents(__DIR__ . '/fixtures/no_closure_return_type_before.txt');
$expected = file_get_contents(__DIR__ . '/fixtures/no_closure_return_type_after.txt');
$temporary = tempnam(sys_get_temp_dir(), 'grimoire-closure-return-type-');

if ($temporary === false) {
    throw new RuntimeException('Unable to create a temporary file.');
}

try {
    file_put_contents($temporary, $source);

    foreach ([
        ['--fix',                        $source],
        ['--fix --unsafe',               $expected],
        ['--fix --unsafe',               $expected],
        ['--minimum-fail-level warning', $expected],
    ] as [$options, $expectedContents]) {
        $command = sprintf(
            'mago --config %s lint --only phpinnacle/no-closure-return-type %s %s',
            escapeshellarg(__DIR__ . '/corpus/mago.toml'),
            $options,
            escapeshellarg($temporary),
        );
        $output = [];
        exec($command, $output, $status);
        $actual = file_get_contents($temporary);

        if ($status !== 0 || $actual !== $expectedContents) {
            throw new RuntimeException(implode("\n", [
                "Closure return type fix failed with {$options}.",
                ...$output,
                'Expected:',
                $expectedContents,
                'Actual:',
                $actual,
            ]));
        }
    }
} finally {
    unlink($temporary);
}
