<?php

declare(strict_types=1);

namespace PHPinnacle\Grimoire\Linter\Rules;

use Mago\Sdk\Linter\LintContext;
use Mago\Sdk\Linter\Rule;
use Mago\Sdk\Linter\RuleDefinition;
use Mago\Sdk\Reporting\Issue;
use Mago\Sdk\Reporting\Level;
use Mago\Sdk\Reporting\Safety;
use Mago\Sdk\Reporting\TextEdit;
use Mago\Sdk\Syntax\Node;
use Mago\Sdk\Syntax\NodeKind;
use Mago\Sdk\Syntax\TriviaKind;

final class NoClosureReturnTypeRule implements Rule
{
    public function getDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            code: 'phpinnacle/no-closure-return-type',
            name: 'No closure return type',
            description: 'Disallows return type declarations on anonymous functions and arrow functions.',
            defaultLevel: Level::Warning,
            defaultEnabled: true,
            targets: [NodeKind::Closure, NodeKind::ArrowFunction],
        );
    }

    public function lint(LintContext $context): void
    {
        $returnType = array_find(
            $context->getChildren(),
            static fn (Node $node) => $node->kind === NodeKind::FunctionLikeReturnTypeHint,
        );

        if ($returnType === null) {
            return;
        }

        $replacement = '';

        foreach ($context->file->getTrivia() as $comment) {
            if (
                $comment->span->start < $returnType->span->start
                || $comment->span->end > $returnType->span->end
            ) {
                continue;
            }

            $replacement .= ' ' . $context->file->getText($comment->span);

            if (in_array($comment->kind, [TriviaKind::SingleLineComment, TriviaKind::HashComment], true)) {
                $replacement .= "\n";
            }
        }

        $context->report(Issue::new(
            'Anonymous functions and arrow functions must not declare a return type.',
            $returnType->span,
        )->withEdit(TextEdit::replace($returnType->span, $replacement)->withSafety(Safety::Unsafe)));
    }
}
