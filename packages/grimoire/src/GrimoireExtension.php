<?php

declare(strict_types=1);

namespace PHPinnacle\Grimoire;

use Mago\Sdk\Extension;
use PHPinnacle\Grimoire\Linter\Rules\ClassMemberOrderRule;
use PHPinnacle\Grimoire\Linter\Rules\ClassMethodOrderRule;
use PHPinnacle\Grimoire\Linter\Rules\CompactEmptyMethodBodyRule;

final class GrimoireExtension
{
    private function __construct() {}

    /**
     * @param array{rules?: array{class-member-order?: list<array{extends?: string, order: list<string>}>, class-method-order?: list<array{extends?: string, order: list<string>}>, compact-empty-method-body?: array{method-pattern?: string}}} $configuration
     */
    public static function create(array $configuration = []): Extension
    {
        return new Extension(
            identifier: 'phpinnacle/grimoire',
            name: 'PHPinnacle Grimoire',
            version: '2.0.0',
            linterRules: [
                new ClassMemberOrderRule($configuration['rules']['class-member-order'] ?? []),
                new ClassMethodOrderRule($configuration['rules']['class-method-order'] ?? []),
                new CompactEmptyMethodBodyRule(
                    $configuration['rules']['compact-empty-method-body']['method-pattern'] ?? '*',
                ),
            ],
        );
    }
}
