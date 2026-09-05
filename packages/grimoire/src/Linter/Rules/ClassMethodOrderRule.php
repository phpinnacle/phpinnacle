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
use Mago\Sdk\Syntax\ResolvedName;
use Mago\Sdk\Syntax\SourceFile;

final class ClassMethodOrderRule implements Rule
{
    /**
     * @param list<array{extends?: string, order: list<string>}> $configurations
     */
    public function __construct(
        private readonly array $configurations = [],
    ) {}

    public function getDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            code: 'phpinnacle/class-method-order',
            name: 'Class method order',
            description: 'Orders class methods according to the first matching configuration.',
            defaultLevel: Level::Warning,
            defaultEnabled: true,
            targets: [NodeKind::AnonymousClass, NodeKind::Class_],
        );
    }

    public function lint(LintContext $context): void
    {
        $order = $this->configuredOrder($context);

        if ($order === null) {
            return;
        }

        $methods = [];
        $previousMemberEnd = $context->node->span->start;

        foreach ($context->getChildren() as $member) {
            if ($member->kind !== NodeKind::ClassLikeMember) {
                continue;
            }

            $method = array_find(
                $context->file->getChildren($member),
                static fn (Node $node) => $node->kind === NodeKind::Method,
            );

            if ($method === null) {
                $previousMemberEnd = $member->span->end;

                continue;
            }

            $name = $context->file->getText(array_find(
                $context->file->getChildren($method),
                static fn (Node $node) => $node->kind === NodeKind::LocalIdentifier,
            ));
            $span = $this->spanWithLeadingComments($context->file, $member, $previousMemberEnd);
            $methods[] = [
                'method' => $method,
                'rank' => $this->rank($context->file, $method, $name, $order),
                'span' => $span,
                'text' => $context->file->getText($span),
            ];
            $previousMemberEnd = $member->span->end;
        }

        $ordered = $methods;
        usort($ordered, static fn (array $left, array $right) => $left['rank'] <=> $right['rank']);

        if (array_any(
            $methods,
            static fn (array $method, int $index) => $method['method'] !== $ordered[$index]['method'],
        )) {
            $issue = Issue::new(
                'Class methods are not in the configured order.',
                $context->node->span,
            );

            foreach ($methods as $index => $method) {
                if ($method['method'] === $ordered[$index]['method']) {
                    continue;
                }

                $issue = $issue->withEdit(TextEdit::replace($method['span'], $ordered[$index]['text']));
            }

            $context->report($issue);
        }
    }

    /**
     * @return null|list<string>
     */
    private function configuredOrder(LintContext $context): ?array
    {
        $extends = array_find(
            $context->getChildren(),
            static fn (Node $node) => $node->kind === NodeKind::Extends,
        );

        foreach ($this->configurations as $configuration) {
            if (!array_key_exists('extends', $configuration)) {
                return $configuration['order'];
            }

            if (
                $extends !== null
                && array_any(
                    $context->file->getResolvedNames($extends),
                    static fn (ResolvedName $name) => fnmatch(
                        $configuration['extends'],
                        $name->name,
                        FNM_NOESCAPE,
                    ),
                )
            ) {
                return $configuration['order'];
            }
        }

        return null;
    }

    /**
     * @param list<string> $order
     */
    private function rank(SourceFile $file, Node $method, string $name, array $order): int
    {
        $modifiers = array_map(
            static fn (Node $node) => strtolower($file->getText($node)),
            array_filter(
                $file->getChildren($method),
                static fn (Node $node) => $node->kind === NodeKind::Modifier,
            ),
        );

        if (!array_intersect($modifiers, ['private', 'protected'])) {
            $modifiers[] = 'public';
        }

        foreach ($order as $rank => $selector) {
            $parts = explode(':', $selector);
            $pattern = array_pop($parts);

            if (
                fnmatch($pattern, $name)
                && array_all($parts, static fn (string $modifier) => in_array($modifier, $modifiers, strict: true))
            ) {
                return $rank;
            }
        }

        return count($order);
    }

    private function spanWithLeadingComments(SourceFile $file, Node $member, int $lowerBound): Span
    {
        $start = $member->span->start;

        foreach (array_reverse($file->getTrivia()) as $comment) {
            if ($comment->span->end > $start || $comment->span->start < $lowerBound) {
                continue;
            }

            if (trim(substr($file->contents, $comment->span->end, $start - $comment->span->end)) !== '') {
                break;
            }

            $start = $comment->span->start;
        }

        return new Span($start, $member->span->end);
    }
}
