<?php

namespace PHPinnacle\Jason\JsonQuery;

use Closure;
use InvalidArgumentException;
use JsonException;

/** @internal */
final class Parser
{
    /** @var list<string> */
    private array $lexemes = [];

    private int $position = 0;

    private function __construct(string $path)
    {
        $pattern = <<<'REGEX'
            ~\G\s*("(?:[^"\\]|\\.)*"|'(?:[^'\\]|\\.)*'|/(?:[^/\\\[]|\\.|\[(?:[^\]\\]|\\.)*\])*/[a-z]*|\.\.|==|!=|<=|>=|=\~|&&|\|\||(?:-?[0-9]+(?:\.[0-9]*)?|-\.[0-9]+)(?:[eE][+-]?[0-9]+)?(?![\pL\pN_\$-])|[\pL\pN_\$][\pL\pN_\$-]*|[@.\[\](),:?*!<>])~us
            REGEX;
        $offset = 0;

        while ($offset < strlen($path) && trim(substr($path, $offset)) !== '') {
            $member = in_array(array_slice($this->lexemes, -1)[0] ?? null, ['.', '..'], true);
            $property = '/\\G\\s*([\\pL\\pN_\\$][\\pL\\pN_\\$-]*)/u';

            if (
                !($member
                && preg_match($property, $path, $match, offset: $offset))
                && !preg_match($pattern, $path, $match, offset: $offset)
            ) {
                throw new InvalidArgumentException('Invalid JSONPath token at offset ' . $offset . '.');
            }

            $this->lexemes[] = $match[1];
            $offset += strlen($match[0]);
        }
    }

    /** @return list<Closure(mixed, bool, Node ...): list<Node>> */
    public static function parse(string $path): array
    {
        $parser = new self($path);
        $parser->expect('$');
        $steps = $parser->steps();
        $parser->expect(null);

        return $steps;
    }

    /** @return list<Closure(mixed, bool, Node ...): list<Node>> */
    private function steps(bool $filter = false): array
    {
        $steps = [];

        while (in_array($this->peek(), ['.', '..', '['], true)) {
            $recursive = $this->accept('..');
            $dot = !$recursive && $this->accept('.');

            if ($this->accept('[')) {
                $selector = $this->bracket();
                $this->expect(']');
            } else {
                if (!$recursive && !$dot) {
                    throw new InvalidArgumentException('Expected a JSONPath selector.');
                }

                $name = $this->take();

                if ($name === '*') {
                    $selector = fn (Node $node, mixed $root, bool $create) => $node->children();
                } elseif (preg_match('/^[\pL\pN_\$][\pL\pN_\$-]*$/u', $name)) {
                    $selector = $filter && $name === 'length' && !in_array($this->peek(), ['.', '..', '['], true)
                        ? fn (Node $node, mixed $root, bool $create) => [new Node($node->length(), null)]
                        : fn (Node $node, mixed $root, bool $create) => $node->member($name, $create);
                } else {
                    throw new InvalidArgumentException('Expected a JSONPath property name.');
                }
            }

            $select = function (mixed $root, bool $create, Node ...$nodes) use ($selector) {
                $matches = [];

                foreach ($nodes as $node) {
                    array_push($matches, ...$selector($node, $root, $create));
                }

                $stored = array_filter($matches, fn (Node $node) => $node->path !== null);

                return $stored === [] ? $matches : array_values($stored);
            };

            if ($recursive) {
                $remaining = [$select, ...$this->steps($filter)];
                $steps[] = function (mixed $root, bool $create, Node ...$nodes) use ($remaining) {
                    $matches = [];

                    foreach ($nodes as $node) {
                        foreach ($node->descendants() as $descendant) {
                            array_push($matches, ...$descendant->follow($remaining, $root));
                        }
                    }

                    return $matches;
                };

                return $steps;
            }

            $steps[] = $select;
        }

        return $steps;
    }

    /** @return Closure(Node, mixed, bool): list<Node> */
    private function bracket(): Closure
    {
        if ($this->accept('*')) {
            return fn (Node $node, mixed $root, bool $create) => $node->children();
        }

        if ($this->accept('?')) {
            return $this->filter();
        }

        if (
            $this->peek() === ':'
            || preg_match('/^-?[0-9]+$/', $this->peek() ?? '')
            && ($this->lexemes[$this->position + 1] ?? null) === ':'
        ) {
            return $this->slice();
        }

        $keys = [];

        do {
            $lexeme = $this->peek();

            if ($lexeme !== null && in_array($lexeme[0], ["'", '"'], true)) {
                $keys[] = $this->string();
            } elseif (preg_match('/^-?[0-9]+$/', $lexeme ?? '')) {
                $keys[] = $this->integer();
            } elseif ($lexeme !== null && preg_match('/^[\pL\pN_\$][\pL\pN_\$-]*$/u', $lexeme)) {
                $keys[] = $this->take();
            } else {
                throw new InvalidArgumentException('Expected a JSONPath name or index.');
            }
        } while ($this->accept(','));

        return function (Node $node, mixed $root, bool $create) use ($keys) {
            $nodes = [];

            foreach ($keys as $key) {
                array_push($nodes, ...$node->child($key, $create));
            }

            return $nodes;
        };
    }

    /** @return Closure(Node, mixed, bool): list<Node> */
    private function filter(): Closure
    {
        $this->expect('(');

        if (str_starts_with($this->peek() ?? '', '/')) {
            $pattern = $this->regex();
            $test = fn (Node $node, mixed $root) => (
                preg_match($pattern, (string) (array_slice($node->path ?? [], -1)[0] ?? '')) === 1
            );
        } else {
            $expression = $this->expression();
            $test = fn (Node $node, mixed $root) => (bool) $expression($node->value, $root);
        }

        $this->expect(')');

        return fn (Node $node, mixed $root, bool $create) => array_values(array_filter(
            $node->children(),
            fn (Node $child) => $test($child, $root),
        ));
    }

    /** @return Closure(Node, mixed, bool): list<Node> */
    private function slice(): Closure
    {
        $start = $this->peek() === ':' ? null : $this->integer();
        $this->expect(':');
        $end = in_array($this->peek(), [':', ']'], true) ? null : $this->integer();
        $step = 1;

        if ($this->accept(':') && $this->peek() !== ']') {
            $step = $this->integer();
        }

        if ($step === 0) {
            throw new InvalidArgumentException('A JSONPath slice step cannot be zero.');
        }

        return fn (Node $node, mixed $root, bool $create) => $node->slice($start, $end, $step);
    }

    /** @return Closure(mixed, mixed): mixed */
    private function expression(int $precedence = 0): Closure
    {
        $left = $this->operand();

        while (true) {
            $operator = $this->peek() ?? '';
            $level = match ($operator) {
                'or', '||' => 1,
                'and', '&&' => 2,
                '==', '!=', '<', '>', '<=', '>=', '=~', 'in' => 3,
                default => 0,
            };

            if ($level <= $precedence) {
                return $left;
            }

            $this->take();

            if ($operator === 'in') {
                $this->expect('[');
                $right = $this->values();
                $left = fn (mixed $value, mixed $root) => in_array($left($value, $root), $right($value, $root), true);

                continue;
            }

            $right = $operator === '=~'
                ? $this->literal($this->regex())
                : $this->expression($level);
            $left = match ($operator) {
                'or', '||' => fn (mixed $value, mixed $root) => $left($value, $root) || $right($value, $root),
                'and', '&&' => fn (mixed $value, mixed $root) => $left($value, $root) && $right($value, $root),
                default => fn (mixed $value, mixed $root) => self::compare(
                    $left($value, $root),
                    $operator,
                    $right($value, $root),
                ),
            };
        }
    }

    private static function compare(mixed $left, string $operator, mixed $right): bool
    {
        if (is_bool($left) !== is_bool($right)) {
            return false;
        }

        return match ($operator) {
            '==' => $left === $right,
            '!=' => $left !== $right,
            '<' => $left < $right,
            '>' => $left > $right,
            '<=' => $left <= $right,
            '>=' => $left >= $right,
            default => is_string($left) && is_string($right) && preg_match($right, $left) === 1,
        };
    }

    /** @return Closure(mixed, mixed): mixed */
    private function operand(): Closure
    {
        if ($this->accept('!') || $this->accept('not')) {
            $value = $this->expression(2);

            return fn (mixed $current, mixed $root) => !$value($current, $root);
        }

        if ($this->accept('(')) {
            $value = $this->expression();
            $this->expect(')');

            return $value;
        }

        if ($this->accept('[')) {
            return $this->values();
        }

        if ($this->accept('.')) {
            $fraction = $this->take();

            if (!preg_match('/^[0-9]+(?:[eE][+-]?[0-9]+)?$/', $fraction)) {
                throw new InvalidArgumentException('Invalid JSONPath numeric literal.');
            }

            return $this->literal((float) ('0.' . $fraction));
        }

        $lexeme = $this->peek();

        if ($lexeme === '$' || $lexeme === '@') {
            $this->take();
            $steps = $this->steps(true);

            return function (mixed $current, mixed $root) use ($lexeme, $steps) {
                $nodes = new Node($lexeme === '$' ? $root : $current, [])->follow($steps, $root);

                $value = $nodes === [] ? false : $nodes[0]->value;

                return is_int($value) ? (float) $value : $value;
            };
        }

        if ($lexeme !== null && in_array($lexeme[0], ["'", '"'], true)) {
            return $this->literal($this->string());
        }

        $this->take();

        return $this->literal(match (true) {
            $lexeme === 'true' => true,
            $lexeme === 'false' => false,
            $lexeme === 'null' => null,
            is_numeric($lexeme) => (float) $lexeme,
            default => throw new InvalidArgumentException('Expected a JSONPath filter operand.'),
        });
    }

    /** @return Closure(mixed, mixed): mixed */
    private function literal(mixed $value): Closure
    {
        return fn (mixed $current, mixed $root) => $value;
    }

    /** @return Closure(mixed, mixed): list<mixed> */
    private function values(): Closure
    {
        $values = [];

        if ($this->peek() !== ']') {
            do {
                $values[] = $this->operand();
            } while ($this->accept(','));
        }

        $this->expect(']');

        return fn (mixed $current, mixed $root) => array_map(fn (Closure $value) => $value($current, $root), $values);
    }

    private function string(): string
    {
        $lexeme = $this->take();
        $encoded = $lexeme[0] === '"'
            ? $lexeme
            : '"'
            . preg_replace_callback(
                '/\\\\.|"/s',
                fn (array $match) => match ($match[0]) {
                    "\\'" => "'",
                    '"' => '\\"',
                    default => $match[0],
                },
                substr($lexeme, 1, -1),
            )
            . '"';

        try {
            $value = json_decode($encoded, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Invalid JSONPath string.', previous: $exception);
        }

        if (!is_string($value)) {
            throw new InvalidArgumentException('Expected a JSONPath string.');
        }

        return $value;
    }

    private function regex(): string
    {
        $pattern = $this->take();

        if (!str_starts_with($pattern, '/')) {
            throw new InvalidArgumentException('Invalid JSONPath regular expression.');
        }

        set_error_handler(fn (int $severity, string $message) => throw new InvalidArgumentException($message));

        try {
            preg_match($pattern, '');
        } finally {
            restore_error_handler();
        }

        return $pattern;
    }

    private function integer(): int
    {
        $lexeme = $this->take();
        $digits = ltrim(ltrim($lexeme, '-'), '0');
        $number = $digits === '' ? '0' : ($lexeme[0] === '-' ? '-' : '') . $digits;

        if (
            !preg_match('/^-?[0-9]+$/', $lexeme)
            || filter_var($number, FILTER_VALIDATE_INT) === false
        ) {
            throw new InvalidArgumentException('Invalid JSONPath index.');
        }

        return (int) $number;
    }

    private function peek(): ?string
    {
        return $this->lexemes[$this->position] ?? null;
    }

    private function take(): string
    {
        return $this->lexemes[$this->position++] ?? throw new InvalidArgumentException('Unexpected end of JSONPath.');
    }

    private function accept(string $lexeme): bool
    {
        if ($this->peek() !== $lexeme) {
            return false;
        }

        $this->position++;

        return true;
    }

    private function expect(?string $lexeme): void
    {
        if ($this->peek() !== $lexeme) {
            throw new InvalidArgumentException('Expected ' . ($lexeme ?? 'the end of the JSONPath') . '.');
        }

        $this->position++;
    }
}
