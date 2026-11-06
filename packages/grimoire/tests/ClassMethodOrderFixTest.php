<?php

declare(strict_types=1);

$source = file_get_contents(__DIR__ . '/fixtures/class_method_order_before.txt');
$expected = file_get_contents(__DIR__ . '/fixtures/class_method_order_after.txt');
$temporary = tempnam(sys_get_temp_dir(), 'grimoire-method-order-');

if ($temporary === false) {
    throw new RuntimeException('Unable to create a temporary file.');
}

try {
    file_put_contents($temporary, $source);
    $command = sprintf(
        '%s --config %s lint --only phpinnacle/class-method-order --fix %s',
        'mago',
        escapeshellarg(__DIR__ . '/corpus/mago.toml'),
        escapeshellarg($temporary),
    );

    for ($pass = 0; $pass < 2; $pass++) {
        exec($command, $output, $status);
        $actual = file_get_contents($temporary);

        if ($status !== 0 || $actual !== $expected) {
            throw new RuntimeException(implode("\n", [
                'Class method order fix failed.',
                ...$output,
                'Expected:',
                $expected,
                'Actual:',
                $actual,
            ]));
        }
    }
} finally {
    unlink($temporary);
}
