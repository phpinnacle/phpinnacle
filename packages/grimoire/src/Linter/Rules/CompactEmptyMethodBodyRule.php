<?php

declare(strict_types=1);

namespace PHPinnacle\Grimoire\Linter\Rules;

use Mago\Sdk\Linter\LintContext;
use Mago\Sdk\Linter\Rule;
use Mago\Sdk\Linter\RuleDefinition;
use Mago\Sdk\Reporting\Issue;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Reporting\TextEdit;
use Mago\Sdk\Span;
use Mago\Sdk\Syntax\Node;
use Mago\Sdk\Syntax\NodeKind;

final class CompactEmptyMethodBodyRule implements Rule
{
    public function __construct(
        private readonly string $methodPattern = '*',
    ) {}

    public function getDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            code: 'phpinnacle/compact-empty-method-body',
            name: 'Compact empty method body',
            description: 'Requires empty method bodies to use compact braces.',
            defaultLevel: Level::Warning,
            defaultEnabled: true,
            targets: [NodeKind::Method],
        );
    }

    public function lint(LintContext $context): void
    {
        $children = $context->getChildren();
        $name = array_find(
            $children,
            static fn (Node $node) => $node->kind === NodeKind::LocalIdentifier,
        );
        $body = array_find(
            $children,
            static fn (Node $node) => $node->kind === NodeKind::MethodBody,
        );

        if (
            $name === null
            || $body === null
            || !fnmatch($this->methodPattern, $context->file->getText($name))
            || preg_match('/^\{\s+\}$/D', $context->file->getText($body)) !== 1
        ) {
            return;
        }

        $precedingEnd = $context->node->span->start;

        foreach ($children as $child) {
            if ($child->span->end <= $body->span->start) {
                $precedingEnd = $child->span->end;
            }
        }

        $editSpan = new Span($precedingEnd, $body->span->end);
        $between = substr(
            $context->file->contents,
            $precedingEnd,
            $body->span->start - $precedingEnd,
        );
        $issue = Issue::new('Empty method bodies must use compact braces.', $body->span);

        if (trim($between) === '') {
            $issue = $issue->withEdit(TextEdit::replace($editSpan, ' {}'));
        }

        $context->report($issue);
    }
}
