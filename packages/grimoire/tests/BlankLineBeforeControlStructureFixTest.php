<?php

declare(strict_types=1);

$source = file_get_contents(__DIR__ . '/fixtures/blank_line_before_control_structure_before.txt');
$expected = file_get_contents(__DIR__ . '/fixtures/blank_line_before_control_structure_after.txt');
$temporary = tempnam(sys_get_temp_dir(), 'grimoire-control-structure-');

if ($temporary === false) {
    throw new RuntimeException('Unable to create a temporary file.');
}

try {
    foreach (["\n", "\r\n"] as $newline) {
        file_put_contents($temporary, str_replace("\n", $newline, $source));

        foreach (['--fix', '--fix', '--minimum-fail-level warning'] as $options) {
            $command = sprintf(
                'mago --config %s lint --only phpinnacle/blank-line-before-control-structure %s %s',
                escapeshellarg(__DIR__ . '/corpus/mago.toml'),
                $options,
                escapeshellarg($temporary),
            );
            $output = [];
            exec($command, $output, $status);
            $actual = file_get_contents($temporary);

            if ($status !== 0 || $actual !== str_replace("\n", $newline, $expected)) {
                throw new RuntimeException(implode("\n", [
                    "Control structure spacing fix failed with {$options}.",
                    ...$output,
                    'Expected:',
                    $expected,
                    'Actual:',
                    $actual,
                ]));
            }
        }
    }
} finally {
    unlink($temporary);
}
