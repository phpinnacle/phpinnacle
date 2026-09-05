<?php

declare(strict_types=1);

$source = file_get_contents(__DIR__ . '/fixtures/class_member_order_before.txt');
$expected = file_get_contents(__DIR__ . '/fixtures/class_member_order_after.txt');
$temporary = tempnam(sys_get_temp_dir(), 'grimoire-member-order-');

if ($temporary === false) {
    throw new RuntimeException('Unable to create a temporary file.');
}

try {
    file_put_contents($temporary, $source);
    $command = sprintf(
        '%s --config %s lint --only phpinnacle/class-member-order --fix %s',
        'mago',
        escapeshellarg(__DIR__ . '/corpus/mago.toml'),
        escapeshellarg($temporary),
    );
    exec($command, $output, $status);
    $actual = file_get_contents($temporary);

    if ($status !== 0 || $actual !== $expected) {
        throw new RuntimeException(implode("\n", [
            'Class member order fix failed.',
            ...$output,
            'Expected:',
            $expected,
            'Actual:',
            $actual,
        ]));
    }
} finally {
    unlink($temporary);
}
