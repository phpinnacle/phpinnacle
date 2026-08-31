<?php

namespace PHPinnacle\Stylus\Twig;

use InvalidArgumentException;
use RuntimeException;

class TwigDocumentSerializer
{
    private const string IDENTIFIER_PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*$/';

    /** @param array<string, mixed> $document */
    public function serialize(array $document): string
    {
        if (($document['type'] ?? null) !== 'doc') {
            throw new InvalidArgumentException('Twig document root must be a doc node.');
        }

        return $this->serializeBlockContent($document['content'] ?? []);
    }

    /**
     * @param  array<string, mixed>  $expected
     * @param  array<string, mixed>  $actual
     */
    private function assertSameCellLoop(array $expected, array $actual): void
    {
        foreach (['twigLoopItem', 'twigLoopKey', 'twigLoopIterable', 'twigLoopTransforms'] as $attribute) {
            if (($expected[$attribute] ?? null) !== ($actual[$attribute] ?? null)) {
                throw new InvalidArgumentException(
                    'Cells in a Twig table cell loop group must use identical loop attributes.',
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $expected
     * @param  array<string, mixed>  $actual
     */
    private function assertSameRowCondition(array $expected, array $actual): void
    {
        if (($expected['twigCondition'] ?? null) !== ($actual['twigCondition'] ?? null)) {
            throw new InvalidArgumentException('Rows in a Twig table condition group must use identical conditions.');
        }
    }

    /**
     * @param  array<string, mixed>  $candidate
     * @param  array<string, mixed>  $condition
     */
    private function belongsToInlineCondition(array $candidate, array $condition, string $conditionId): bool
    {
        if ($this->inlineConditionId($candidate) !== $conditionId) {
            return false;
        }

        $candidateAttributes = $candidate['attrs'] ?? [];
        $conditionAttributes = $condition['attrs'] ?? [];

        return (
            ($candidateAttributes['condition'] ?? null) === ($conditionAttributes['condition'] ?? null)
            && ($candidateAttributes['conditionAst'] ?? null) === ($conditionAttributes['conditionAst'] ?? null)
        );
    }

    private function escapeAttribute(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** @param array<string, mixed> $attributes */
    private function hasLoopAttributes(array $attributes): bool
    {
        return (
            filled($attributes['twigLoopItem'] ?? null)
            || filled($attributes['twigLoopKey'] ?? null)
            || filled($attributes['twigLoopIterable'] ?? null)
            || filled($attributes['twigLoopId'] ?? null)
        );
    }

    /** @param list<mixed> $rows */
    private function hasMergedCells(array $rows): bool
    {
        foreach ($rows as $row) {
            foreach ($this->requireList($this->requireNode($row)['content'] ?? []) as $cell) {
                $attributes = $this->requireNode($cell)['attrs'] ?? [];

                if (($attributes['colspan'] ?? 1) !== 1 || ($attributes['rowspan'] ?? 1) !== 1) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @param array<string, mixed> $attributes */
    private function hasTableConditionAttributes(array $attributes): bool
    {
        return filled($attributes['twigCondition'] ?? null) || filled($attributes['twigConditionId'] ?? null);
    }

    /** @param array<string, mixed> $node */
    private function hasTableLoop(array $node): bool
    {
        if ($this->hasLoopAttributes($node['attrs'] ?? [])) {
            return true;
        }

        foreach ($this->requireList($node['content'] ?? []) as $cell) {
            if ($this->hasLoopAttributes($this->requireNode($cell)['attrs'] ?? [])) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, mixed> $mark */
    private function inlineConditionBranch(array $mark): string
    {
        $branch = $mark['attrs']['branch'] ?? null;

        if (!in_array($branch, ['then', 'else'], true)) {
            throw new InvalidArgumentException('Twig inline condition branch must be then or else.');
        }

        return $branch;
    }

    /** @param array<string, mixed> $mark */
    private function inlineConditionId(array $mark): string
    {
        $conditionId = $this->requireString(
            $mark['attrs']['conditionId'] ?? null,
            'Twig inline condition ID',
        );

        if ($conditionId === '') {
            throw new InvalidArgumentException('Twig inline condition ID must not be empty.');
        }

        return $conditionId;
    }

    /**
     * @param  array<string, mixed>  $node
     * @return list<array<string, mixed>>
     */
    private function inlineConditionMarks(array $node): array
    {
        $conditions = [];

        foreach ($this->requireList($node['marks'] ?? []) as $mark) {
            $mark = $this->requireNode($mark, 'mark');

            if (($mark['type'] ?? null) === 'twigInlineIf') {
                $conditions[] = $mark;
            }
        }

        return $conditions;
    }

    private function requireExpression(mixed $value, string $label): string
    {
        $expression = trim($this->requireString($value, $label));

        if ($expression === '') {
            throw new InvalidArgumentException("{$label} cannot be empty.");
        }

        return $expression;
    }

    private function requireIdentifier(mixed $value, string $label): string
    {
        $identifier = trim($this->requireString($value, $label));

        if (preg_match(self::IDENTIFIER_PATTERN, $identifier) !== 1) {
            throw new InvalidArgumentException("{$label} must be a valid Twig identifier.");
        }

        return $identifier;
    }

    /** @return list<mixed> */
    private function requireList(mixed $value): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw new InvalidArgumentException('Twig node content must be a list.');
        }

        return $value;
    }

    /** @return array<string, mixed> */
    private function requireNode(mixed $value, string $label = 'node'): array
    {
        if (!is_array($value) || !is_string($value['type'] ?? null)) {
            throw new InvalidArgumentException("Twig editor {$label} must contain a type.");
        }

        return $value;
    }

    /** @return array<string, mixed> */
    private function requireRecord(mixed $value, string $label): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw new InvalidArgumentException("Twig editor {$label} must be an object.");
        }

        return $value;
    }

    private function requireString(mixed $value, string $label): string
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException("{$label} must be a string.");
        }

        return $value;
    }

    /** @param array<string, mixed> $node */
    private function serializeApply(array $node): string
    {
        $filters = $this->requireList($node['attrs']['filters'] ?? []);

        if ($filters === []) {
            throw new InvalidArgumentException('Twig apply must contain at least one filter.');
        }

        $filterChain = '';

        foreach ($filters as $filter) {
            $filterChain .= $this->serializeFilter($this->requireRecord($filter, 'apply filter'));
        }

        return implode("\n", [
            '{% apply ' . substr($filterChain, 1) . ' %}',
            $this->serializeBlockContent($node['content'] ?? []),
            '{% endapply %}',
        ]);
    }

    private function serializeBlockContent(mixed $content): string
    {
        return $this->serializeNodes($this->requireList($content));
    }

    /** @param array<string, mixed> $filter */
    private function serializeFilter(array $filter): string
    {
        $name = $filter['name'] ?? null;

        if (!is_string($name) || preg_match(self::IDENTIFIER_PATTERN, $name) !== 1) {
            throw new InvalidArgumentException('Invalid Twig filter name: ' . var_export($name, true) . '.');
        }

        $arguments = [];

        foreach ($this->requireList($filter['arguments'] ?? []) as $index => $argument) {
            $arguments[] = $this->requireExpression($argument, 'Argument ' . ($index + 1) . " for Twig filter {$name}");
        }

        return $arguments === []
            ? "|{$name}"
            : "|{$name}(" . implode(', ', $arguments) . ')';
    }

    /** @param array<string, mixed> $node */
    private function serializeFor(array $node): string
    {
        $content = $this->requireList($node['content'] ?? []);
        $firstChild = isset($content[0]) ? $this->requireNode($content[0]) : null;
        $elseBranch = isset($content[1]) ? $this->requireNode($content[1]) : null;

        if (
            ($firstChild['type'] ?? null) !== 'twigForBody'
            || count($content) > 2
            || $elseBranch !== null
            && ($elseBranch['type'] ?? null) !== 'twigForElse'
        ) {
            throw new InvalidArgumentException('twigFor must contain twigForBody followed by an optional twigForElse.');
        }

        return $this->wrapFor(
            $node['attrs'] ?? [],
            $this->serializeBlockContent($firstChild['content'] ?? []),
            'Twig loop',
            $elseBranch === null
                ? null
                : $this->serializeBlockContent($elseBranch['content'] ?? []),
        );
    }

    /** @param array<string, mixed> $node */
    private function serializeHeading(array $node): string
    {
        $level = $node['attrs']['level'] ?? null;

        if (!is_int($level) || $level < 1 || $level > 6) {
            throw new InvalidArgumentException('Heading level must be an integer between 1 and 6.');
        }

        return "<h{$level}>" . $this->serializeInlineContent($node['content'] ?? []) . "</h{$level}>";
    }

    /** @param array<string, mixed> $node */
    private function serializeIf(array $node): string
    {
        $condition = $this->requireExpression($node['attrs']['condition'] ?? null, 'Twig if condition');
        $branches = $this->requireList($node['content'] ?? []);
        $thenBranch = isset($branches[0]) ? $this->requireNode($branches[0]) : null;
        $elseBranch = isset($branches[1]) ? $this->requireNode($branches[1]) : null;

        if (
            count($branches) < 1
            || count($branches) > 2
            || ($thenBranch['type'] ?? null) !== 'twigThen'
            || $elseBranch !== null
            && ($elseBranch['type'] ?? null) !== 'twigElse'
        ) {
            throw new InvalidArgumentException('twigIf must contain twigThen followed by an optional twigElse.');
        }

        $output = [
            "{% if {$condition} %}",
            $this->serializeBlockContent($thenBranch['content'] ?? []),
        ];

        if ($elseBranch !== null) {
            $output[] = '{% else %}';
            $output[] = $this->serializeBlockContent($elseBranch['content'] ?? []);
        }

        $output[] = '{% endif %}';

        return implode("\n", array_filter($output, fn (string $line) => $line !== ''));
    }

    /**
     * @param  list<mixed>  $nodes
     * @param  array<string, true>  $completedConditionIds
     */
    private function serializeInlineConditionLevel(array $nodes, int $depth, array &$completedConditionIds): string
    {
        $output = '';

        for ($index = 0, $count = count($nodes); $index < $count; $index++) {
            $node = $this->requireNode($nodes[$index]);
            $conditionMarks = $this->inlineConditionMarks($node);
            $condition = $conditionMarks[$depth] ?? null;

            if ($condition === null) {
                $output .= $this->serializeNode($this->withoutMark($node, 'twigInlineIf'));

                continue;
            }

            $conditionId = $this->inlineConditionId($condition);

            if ($this->inlineConditionBranch($condition) !== 'then') {
                throw new InvalidArgumentException('Twig inline condition must start with an if branch.');
            }

            if (isset($completedConditionIds[$conditionId])) {
                throw new InvalidArgumentException('Twig inline condition branches must be contiguous.');
            }

            $thenContent = [];
            $elseContent = [];
            $hasElse = false;

            do {
                $currentCondition = $this->inlineConditionMarks($node)[$depth] ?? null;

                if (
                    $currentCondition === null
                    || !$this->belongsToInlineCondition($currentCondition, $condition, $conditionId)
                ) {
                    break;
                }

                $branch = $this->inlineConditionBranch($currentCondition);

                if ($branch === 'then' && $hasElse) {
                    throw new InvalidArgumentException(
                        'Twig inline condition must contain its if branch before its else branch.',
                    );
                }

                if ($branch === 'else') {
                    $hasElse = true;
                    $elseContent[] = $node;
                } else {
                    $thenContent[] = $node;
                }

                $index++;

                if ($index >= $count) {
                    break;
                }

                $node = $this->requireNode($nodes[$index]);
            } while (true);

            $index--;

            $completedConditionIds[$conditionId] = true;

            $output .= $this->serializeInlineIf(
                $condition,
                $this->serializeInlineConditionLevel($thenContent, $depth + 1, $completedConditionIds),
                $hasElse
                    ? str_replace(
                        "\u{200B}",
                        '',
                        $this->serializeInlineConditionLevel($elseContent, $depth + 1, $completedConditionIds),
                    )
                    : null,
            );
        }

        return $output;
    }

    private function serializeInlineContent(mixed $content): string
    {
        $nodes = $this->requireList($content);

        foreach ($nodes as $node) {
            if (!in_array($this->requireNode($node)['type'] ?? null, ['text', 'hardBreak', 'twigVariable'], true)) {
                throw new InvalidArgumentException(
                    'Node '
                    . var_export($this->requireNode($node)['type'] ?? null, true)
                    . ' is not allowed in inline content.',
                );
            }
        }

        return $this->serializeInlineNodes($nodes);
    }

    /** @param array<string, mixed> $mark */
    private function serializeInlineIf(array $mark, string $content, ?string $elseContent = null): string
    {
        $condition = $this->requireExpression($mark['attrs']['condition'] ?? null, 'Twig inline if condition');

        $else = $elseContent === null ? '' : "{% else %}{$elseContent}";

        return "{% if {$condition} %}{$content}{$else}{% endif %}";
    }

    /** @param list<mixed> $nodes */
    private function serializeInlineNodes(array $nodes): string
    {
        $completedConditionIds = [];

        return $this->serializeInlineConditionLevel($nodes, 0, $completedConditionIds);
    }

    /** @param array<string, mixed> $mark */
    private function serializeLink(array $mark, string $content): string
    {
        $attributes = [
            'href="' . $this->escapeAttribute($this->requireString($mark['attrs']['href'] ?? null, 'Link href')) . '"',
        ];

        foreach (['target', 'rel'] as $name) {
            $value = $mark['attrs'][$name] ?? null;

            if ($value !== null) {
                $attributes[] =
                    $name . '="' . $this->escapeAttribute($this->requireString($value, "Link {$name}")) . '"';
            }
        }

        return '<a ' . implode(' ', $attributes) . ">{$content}</a>";
    }

    /**
     * @param  array<string, mixed>  $mark
     */
    private function serializeMark(array $mark, string $content): string
    {
        return match ($mark['type'] ?? null) {
            'bold' => "<strong>{$content}</strong>",
            'italic' => "<em>{$content}</em>",
            'link' => $this->serializeLink($mark, $content),
            'twigInlineIf' => $this->serializeInlineIf($mark, $content),
            default => throw new RuntimeException(
                'Unsupported Twig editor mark: ' . var_export($mark['type'] ?? null, true) . '.',
            ),
        };
    }

    /** @param array<string, mixed> $node */
    private function serializeNode(array $node): string
    {
        return match ($node['type'] ?? null) {
            'paragraph' => '<p>' . $this->serializeInlineContent($node['content'] ?? []) . '</p>',
            'heading' => $this->serializeHeading($node),
            'text' => $this->serializeText($node),
            'hardBreak' => '<br>',
            'horizontalRule' => '<hr>',
            'blockquote' => "<blockquote>\n" . $this->serializeBlockContent($node['content'] ?? []) . "\n</blockquote>",
            'bulletList' => "<ul>\n" . $this->serializeBlockContent($node['content'] ?? []) . "\n</ul>",
            'orderedList' => $this->serializeOrderedList($node),
            'listItem' => '<li>' . $this->serializeBlockContent($node['content'] ?? []) . '</li>',
            'table' => $this->serializeTable($node),
            'tableRow' => $this->serializeTableRow($node),
            'tableHeader' => '<th>' . $this->serializeBlockContent($node['content'] ?? []) . '</th>',
            'tableCell' => '<td>' . $this->serializeBlockContent($node['content'] ?? []) . '</td>',
            'twigVariable' => $this->serializeVariable($node),
            'twigApply' => $this->serializeApply($node),
            'twigIf' => $this->serializeIf($node),
            'twigFor' => $this->serializeFor($node),
            default => throw new RuntimeException(
                'Unsupported Twig editor node: ' . var_export($node['type'] ?? null, true) . '.',
            ),
        };
    }

    /** @param list<mixed> $nodes */
    private function serializeNodes(array $nodes, string $separator = "\n"): string
    {
        return implode($separator, array_map(
            fn (mixed $node) => $this->serializeNode($this->requireNode($node)),
            $nodes,
        ));
    }

    /** @param array<string, mixed> $node */
    private function serializeOrderedList(array $node): string
    {
        $start = $node['attrs']['start'] ?? 1;

        if (!is_int($start) || $start < 1) {
            throw new InvalidArgumentException('Ordered list start must be a positive integer.');
        }

        $startAttribute = $start === 1 ? '' : " start=\"{$start}\"";

        return "<ol{$startAttribute}>\n" . $this->serializeBlockContent($node['content'] ?? []) . "\n</ol>";
    }

    /** @param array<string, mixed> $node */
    private function serializeTable(array $node): string
    {
        $rows = $this->requireList($node['content'] ?? []);
        $hasLoops = false;

        foreach ($rows as $row) {
            $row = $this->requireNode($row);

            if (($row['type'] ?? null) !== 'tableRow') {
                throw new InvalidArgumentException('table must contain only tableRow nodes.');
            }

            $hasLoops = $hasLoops || $this->hasTableLoop($row);
        }

        if ($hasLoops && $this->hasMergedCells($rows)) {
            throw new InvalidArgumentException('Twig table loops cannot be used in tables with merged cells.');
        }

        return "<table>\n" . $this->serializeTableRows($rows) . "\n</table>";
    }

    /** @param list<mixed> $cells */
    private function serializeTableCells(array $cells): string
    {
        $output = [];
        $completedLoopIds = [];

        for ($index = 0, $count = count($cells); $index < $count; $index++) {
            $cell = $this->requireNode($cells[$index]);
            $attributes = $cell['attrs'] ?? [];

            if (!$this->hasLoopAttributes($attributes)) {
                $output[] = $this->serializeNode($cell);

                continue;
            }

            $loopId = $this->requireExpression($attributes['twigLoopId'] ?? null, 'Twig table cell loop ID');

            if (isset($completedLoopIds[$loopId])) {
                throw new InvalidArgumentException('Twig table cell loop groups must be contiguous within a row.');
            }

            $group = [$cell];

            while (($index + 1) < $count) {
                $nextCell = $this->requireNode($cells[$index + 1]);

                if (($nextCell['attrs']['twigLoopId'] ?? null) !== $loopId) {
                    break;
                }

                $this->assertSameCellLoop($attributes, $nextCell['attrs'] ?? []);
                $group[] = $nextCell;
                $index++;
            }

            $completedLoopIds[$loopId] = true;
            $output[] = $this->wrapFor($attributes, $this->serializeNodes($group), 'Twig table cell loop');
        }

        return implode("\n", $output);
    }

    /** @param array<string, mixed> $node */
    private function serializeTableRow(array $node): string
    {
        $row = $this->serializeTableRowMarkup($node);
        $attributes = $node['attrs'] ?? [];

        if ($this->hasTableConditionAttributes($attributes)) {
            $row = $this->wrapIf($attributes, $row, 'Twig table row condition');
        }

        return $this->hasLoopAttributes($attributes)
            ? $this->wrapFor($attributes, $row, 'Twig table row loop')
            : $row;
    }

    /** @param array<string, mixed> $node */
    private function serializeTableRowMarkup(array $node): string
    {
        $cells = $this->requireList($node['content'] ?? []);

        foreach ($cells as $cell) {
            if (!in_array($this->requireNode($cell)['type'] ?? null, ['tableHeader', 'tableCell'], true)) {
                throw new InvalidArgumentException('tableRow must contain only tableHeader or tableCell nodes.');
            }
        }

        return "<tr>\n" . $this->serializeTableCells($cells) . "\n</tr>";
    }

    /** @param list<mixed> $rows */
    private function serializeTableRows(array $rows): string
    {
        $output = [];
        $completedConditionIds = [];

        for ($index = 0, $count = count($rows); $index < $count; $index++) {
            $row = $this->requireNode($rows[$index]);
            $attributes = $row['attrs'] ?? [];

            if (!$this->hasTableConditionAttributes($attributes)) {
                $output[] = $this->serializeTableRow($row);

                continue;
            }

            $conditionId = $this->requireString(
                $attributes['twigConditionId'] ?? null,
                'Twig table row condition ID',
            );

            if (isset($completedConditionIds[$conditionId])) {
                throw new InvalidArgumentException(
                    'Twig table row condition groups must be contiguous within a table.',
                );
            }

            $group = [$row];

            while (($index + 1) < $count) {
                $nextRow = $this->requireNode($rows[$index + 1]);

                if (($nextRow['attrs']['twigConditionId'] ?? null) !== $conditionId) {
                    break;
                }

                $this->assertSameRowCondition($attributes, $nextRow['attrs'] ?? []);
                $group[] = $nextRow;
                $index++;
            }

            $completedConditionIds[$conditionId] = true;

            if (count($group) === 1) {
                $output[] = $this->serializeTableRow($row);

                continue;
            }

            foreach ($group as $conditionedRow) {
                if ($this->hasLoopAttributes($conditionedRow['attrs'] ?? [])) {
                    throw new InvalidArgumentException(
                        'A multi-row Twig table condition cannot contain repeated rows.',
                    );
                }
            }

            $content = implode("\n", array_map(
                fn (array $conditionedRow) => $this->serializeTableRowMarkup($conditionedRow),
                $group,
            ));
            $output[] = $this->wrapIf($attributes, $content, 'Twig table row condition');
        }

        return implode("\n", $output);
    }

    /** @param array<string, mixed> $node */
    private function serializeText(array $node): string
    {
        $text = $node['text'] ?? null;

        if (!is_string($text)) {
            throw new InvalidArgumentException('Text node content must be a string.');
        }

        $content = htmlspecialchars($text, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');

        foreach ($this->requireList($node['marks'] ?? []) as $mark) {
            $content = $this->serializeMark($this->requireNode($mark, 'mark'), $content);
        }

        return $content;
    }

    /** @param array<string, mixed> $node */
    private function serializeVariable(array $node): string
    {
        $expression = $this->requireExpression($node['attrs']['expression'] ?? null, 'Twig variable expression');
        $filters = $this->requireList($node['attrs']['filters'] ?? []);
        $serializedFilters = array_map(fn (mixed $filter) => $this->serializeFilter($this->requireRecord(
            $filter,
            'filter',
        )), $filters);

        return '{{ ' . $expression . implode('', $serializedFilters) . ' }}';
    }

    /** @param array<string, mixed> $node */
    private function withoutMark(array $node, string $type): array
    {
        $node['marks'] = array_values(array_filter(
            $this->requireList($node['marks'] ?? []),
            fn (mixed $mark) => ($this->requireNode($mark, 'mark')['type'] ?? null) !== $type,
        ));

        return $node;
    }

    /** @param array<string, mixed> $attributes */
    private function wrapFor(
        array $attributes,
        string $content,
        string $label,
        ?string $elseContent = null,
    ): string {
        $itemAttribute = array_key_exists('twigLoopItem', $attributes) ? 'twigLoopItem' : 'item';
        $keyAttribute = array_key_exists('twigLoopKey', $attributes) ? 'twigLoopKey' : 'key';
        $iterableAttribute = array_key_exists('twigLoopIterable', $attributes) ? 'twigLoopIterable' : 'iterable';
        $transformsAttribute = array_key_exists('twigLoopTransforms', $attributes)
            ? 'twigLoopTransforms'
            : 'transforms';
        $item = $this->requireIdentifier($attributes[$itemAttribute] ?? null, "{$label} item");
        $iterable = $this->requireExpression($attributes[$iterableAttribute] ?? null, "{$label} iterable");

        foreach ($this->requireList($attributes[$transformsAttribute] ?? []) as $transform) {
            $iterable .= $this->serializeFilter($this->requireRecord($transform, 'collection transform'));
        }

        $key = $attributes[$keyAttribute] ?? null;
        $variables = blank($key)
            ? $item
            : $this->requireIdentifier($key, "{$label} key") . ", {$item}";

        return implode("\n", array_filter(
            [
                "{% for {$variables} in {$iterable} %}",
                $content,
                ...($elseContent === null ? [] : ['{% else %}', $elseContent]),
                '{% endfor %}',
            ],
            fn (string $line) => $line !== '',
        ));
    }

    /** @param array<string, mixed> $attributes */
    private function wrapIf(array $attributes, string $content, string $label): string
    {
        $condition = $this->requireExpression($attributes['twigCondition'] ?? null, "{$label} expression");

        return implode("\n", [
            "{% if {$condition} %}",
            $content,
            '{% endif %}',
        ]);
    }
}
