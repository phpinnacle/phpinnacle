<?php

declare(strict_types=1);

namespace PHPinnacle\Grimoire\Linter\Rules;

use Mago\Sdk\Linter\LintContext;
use Mago\Sdk\Linter\Rule;
use Mago\Sdk\Linter\RuleDefinition;
use Mago\Sdk\Reporting\Issue;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Reporting\TextEdit;
use Mago\Sdk\Syntax\Node;
use Mago\Sdk\Syntax\NodeKind;

final class BlankLineBeforeControlStructureRule implements Rule
{
    public function getDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            code: 'phpinnacle/blank-line-before-control-structure',
            name: 'Blank line before control structure',
            description: 'Requires a blank line before control structures following another statement.',
            defaultLevel: Level::Warning,
            defaultEnabled: true,
            targets: [
                NodeKind::Program,
                NodeKind::Block,
                NodeKind::NamespaceImplicitBody,
                NodeKind::IfColonDelimitedBody,
                NodeKind::IfColonDelimitedBodyElseClause,
                NodeKind::IfColonDelimitedBodyElseIfClause,
                NodeKind::ForColonDelimitedBody,
                NodeKind::ForeachColonDelimitedBody,
                NodeKind::WhileColonDelimitedBody,
                NodeKind::DeclareColonDelimitedBody,
                NodeKind::SwitchExpressionCase,
                NodeKind::SwitchDefaultCase,
            ],
        );
    }

    public function lint(LintContext $context): void
    {
        $previous = null;

        foreach ($context->getChildren() as $statement) {
            if ($statement->kind !== NodeKind::Statement) {
                continue;
            }

            $kind = $context->file->getChildren($statement)[0]->kind;

            if (in_array($kind, [NodeKind::OpeningTag, NodeKind::ClosingTag, NodeKind::Inline], true)) {
                $previous = null;

                continue;
            }

            if (
                $previous !== null
                && in_array(
                    $kind,
                    [
                        NodeKind::If,
                        NodeKind::For,
                        NodeKind::Foreach,
                        NodeKind::While,
                        NodeKind::DoWhile,
                        NodeKind::Switch,
                        NodeKind::Try,
                    ],
                    true,
                )
            ) {
                $this->checkSpacing($context, $previous, $statement);
            }

            $previous = $statement;
        }
    }

    private function checkSpacing(LintContext $context, Node $previous, Node $statement): void
    {
        $end = $previous->span->end;
        $start = $statement->span->start;

        foreach ($context->file->getTrivia() as $comment) {
            if ($comment->span->start < $end || $comment->span->end > $start) {
                continue;
            }

            $beforeComment = substr($context->file->contents, $end, $comment->span->start - $end);

            if (str_contains($beforeComment, "\n")) {
                $start = $comment->span->start;

                break;
            }

            $end = $comment->span->end;
        }

        $lineBreaks = substr_count(substr($context->file->contents, $end, $start - $end), "\n");

        if ($lineBreaks >= 2) {
            return;
        }

        $newline = str_contains($context->file->contents, "\r\n") ? "\r\n" : "\n";
        $context->report(Issue::new(
            'Control structures following another statement must be preceded by a blank line.',
            $statement->span,
        )->withEdit(TextEdit::insert($end, str_repeat($newline, 2 - $lineBreaks))));
    }
}
