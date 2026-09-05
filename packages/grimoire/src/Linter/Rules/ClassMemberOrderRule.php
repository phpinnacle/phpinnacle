<?php

declare(strict_types=1);

namespace PHPinnacle\Grimoire\Linter\Rules;

use LogicException;
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

final class ClassMemberOrderRule implements Rule
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
            code: 'phpinnacle/class-member-order',
            name: 'Class member order',
            description: 'Orders class-like members according to the first matching configuration.',
            defaultLevel: Level::Warning,
            defaultEnabled: true,
            targets: [
                NodeKind::AnonymousClass,
                NodeKind::Class_,
                NodeKind::Enum,
                NodeKind::Interface,
                NodeKind::Trait,
            ],
        );
    }

    public function lint(LintContext $context): void
    {
        $order = $this->configuredOrder($context);

        if ($order === null) {
            return;
        }

        $members = [];
        $classMembers = array_values(array_filter(
            $context->getChildren(),
            static fn (Node $member) => $member->kind === NodeKind::ClassLikeMember,
        ));
        $previousMemberEnd = $context->node->span->start;

        foreach ($classMembers as $index => $member) {
            $details = $this->details($context->file, $member);
            $span = $this->spanWithComments(
                $context->file,
                $member,
                new Span(
                    $previousMemberEnd,
                    $classMembers[$index + 1]->span->start ?? $context->node->span->end,
                ),
            );
            $members[] = [
                ...$details,
                'index' => $index,
                'rank' => $this->rank($details, $order),
                'span' => $span,
                'text' => $context->file->getText($span),
            ];
            $previousMemberEnd = $member->span->end;
        }

        $ordered = $members;
        usort(
            $ordered,
            static fn (array $left, array $right) => (
                [$left['rank'], $left['index']] <=> [$right['rank'], $right['index']]
            ),
        );

        if (!array_any(
            $members,
            static fn (array $member, int $index) => $member['node'] !== $ordered[$index]['node'],
        )) {
            return;
        }

        $issue = Issue::new(
            'Class members are not in the configured order.',
            $context->node->span,
        );

        foreach ($members as $index => $member) {
            if ($member['node'] === $ordered[$index]['node']) {
                continue;
            }

            $issue = $issue->withEdit(TextEdit::replace($member['span'], $ordered[$index]['text']));
        }

        $context->report($issue);
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
     * @return array{node: Node, type: string, names: list<string>, modifiers: list<string>}
     */
    private function details(SourceFile $file, Node $member): array
    {
        $node = array_find(
            $file->getChildren($member),
            static fn (Node $node) => in_array(
                $node->kind,
                [
                    NodeKind::ClassLikeConstant,
                    NodeKind::EnumCase,
                    NodeKind::Method,
                    NodeKind::Property,
                    NodeKind::TraitUse,
                ],
                true,
            ),
        );
        $type = match ($node->kind) {
            NodeKind::ClassLikeConstant => 'constant',
            NodeKind::EnumCase => 'enum-case',
            NodeKind::Method => 'method',
            NodeKind::Property => 'property',
            NodeKind::TraitUse => 'trait-use',
            default => throw new LogicException("Unsupported class member node kind: {$node->kind->value}"),
        };

        return [
            'node' => $member,
            'type' => $type,
            'names' => $this->names($file, $node),
            'modifiers' => $this->modifiers($file, $node, $type),
        ];
    }

    /** @return list<string> */
    private function modifiers(SourceFile $file, Node $node, string $type): array
    {
        $owner = $type === 'property' ? $file->getChildren($node)[0] : $node;
        $modifiers = array_map(
            static fn (Node $modifier) => strtolower($file->getText($modifier)),
            array_filter(
                $file->getChildren($owner),
                static fn (Node $child) => $child->kind === NodeKind::Modifier,
            ),
        );

        if (
            in_array($type, ['constant', 'method', 'property'], true)
            && !array_intersect($modifiers, ['private', 'protected', 'public'])
        ) {
            $modifiers[] = 'public';
        }

        return array_values($modifiers);
    }

    /** @return list<string> */
    private function names(SourceFile $file, Node $node): array
    {
        $names = match ($node->kind) {
            NodeKind::ClassLikeConstant => array_map(
                static fn (Node $item) => $file->getFirstDescendant($item, NodeKind::LocalIdentifier),
                $file->getDescendants($node, NodeKind::ClassLikeConstantItem),
            ),
            NodeKind::EnumCase => [$file->getFirstDescendant($node, NodeKind::LocalIdentifier)],
            NodeKind::Method => [array_find(
                $file->getChildren($node),
                static fn (Node $child) => $child->kind === NodeKind::LocalIdentifier,
            )],
            NodeKind::Property => array_map(
                static fn (Node $item) => $file->getFirstDescendant($item, NodeKind::DirectVariable),
                $file->getDescendants($node, NodeKind::PropertyItem),
            ),
            NodeKind::TraitUse => array_filter(
                $file->getChildren($node),
                static fn (Node $child) => $child->kind === NodeKind::Identifier,
            ),
            default => throw new LogicException("Unsupported class member node kind: {$node->kind->value}"),
        };

        return array_map(
            static fn (Node $name) => ltrim($file->getText($name), '$'),
            $names,
        );
    }

    /**
     * @param array{type: string, names: list<string>, modifiers: list<string>} $member
     * @param list<string> $order
     */
    private function rank(array $member, array $order): int
    {
        foreach ($order as $rank => $selector) {
            $parts = explode(':', $selector);
            $pattern = array_pop($parts);
            $type = array_find(
                $parts,
                static fn (string $part) => in_array(
                    $part,
                    ['constant', 'enum-case', 'method', 'property', 'trait-use'],
                    true,
                ),
            );

            if ($type !== $member['type']) {
                continue;
            }

            $modifiers = array_values(array_filter($parts, static fn (string $part) => $part !== $type));

            if (
                array_all($modifiers, static fn (string $modifier) => in_array(
                    $modifier,
                    $member['modifiers'],
                    true,
                ))
                && array_any($member['names'], static fn (string $name) => fnmatch(
                    $pattern,
                    $name,
                    FNM_NOESCAPE,
                ))
            ) {
                return $rank;
            }
        }

        return count($order);
    }

    private function spanWithComments(SourceFile $file, Node $member, Span $bounds): Span
    {
        $start = $member->span->start;
        $end = $member->span->end;

        foreach ($file->getDescendants($member, NodeKind::AttributeList) as $attributes) {
            $start = min($start, $attributes->span->start);
        }

        foreach (array_reverse($file->getTrivia()) as $comment) {
            if ($comment->span->end > $start || $comment->span->start < $bounds->start) {
                continue;
            }

            if (
                !str_contains(substr($file->contents, $bounds->start, $comment->span->start - $bounds->start), "\n")
                || trim(substr($file->contents, $comment->span->end, $start - $comment->span->end)) !== ''
            ) {
                break;
            }

            $start = $comment->span->start;
        }

        foreach ($file->getTrivia() as $comment) {
            if ($comment->span->start < $end) {
                continue;
            }

            if ($comment->span->start >= $bounds->end) {
                break;
            }

            $between = substr($file->contents, $end, $comment->span->start - $end);

            if (str_contains($between, "\n") || trim($between) !== '') {
                break;
            }

            $end = $comment->span->end;
        }

        return new Span($start, $end);
    }
}
