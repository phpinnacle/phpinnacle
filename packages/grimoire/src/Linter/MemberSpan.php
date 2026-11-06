<?php

declare(strict_types=1);

namespace PHPinnacle\Grimoire\Linter;

use Mago\Sdk\Span;
use Mago\Sdk\Syntax\Node;
use Mago\Sdk\Syntax\NodeKind;
use Mago\Sdk\Syntax\SourceFile;

/** @internal */
final class MemberSpan
{
    public static function withComments(SourceFile $file, Node $member, Span $bounds): Span
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
