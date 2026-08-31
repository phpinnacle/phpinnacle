<?php

namespace PHPinnacle\Stylus\Twig;

use InvalidArgumentException;
use PHPinnacle\Stylus\Contracts\Condition;
use PHPinnacle\Stylus\Contracts\Filter;
use PHPinnacle\Stylus\Enums\ConditionKind;
use PHPinnacle\Stylus\Enums\FilterOutput;
use PHPinnacle\Stylus\VariableScope;

class ConditionExpressionSerializer
{
    /**
     * @param  array<string, Condition>  $conditions
     * @param  array<string, Filter>  $filters
     */
    public function __construct(
        private readonly VariableScope $scope,
        private readonly array $conditions,
        private readonly array $filters = [],
    ) {}

    /** @param array<string, mixed> $expression */
    public function serialize(array $expression): string
    {
        return $this->serializeExpression($expression, 0);
    }

    private function getVariableOperandType(mixed $operand): ?string
    {
        if (!is_array($operand) || ($operand['type'] ?? null) !== 'variable') {
            return null;
        }

        $name = $operand['name'] ?? null;

        return is_string($name) ? $this->scope->getVariable($name)?->getType() : null;
    }

    /** @param array<string, mixed> $expression */
    private function negateIfNeeded(string $serialized, array $expression): string
    {
        return ($expression['negated'] ?? false) === true
            ? "not ({$serialized})"
            : $serialized;
    }

    private function serializeBooleanLiteral(mixed $value): string
    {
        if (!is_bool($value)) {
            throw new InvalidArgumentException('Twig condition boolean literal must be a boolean.');
        }

        return $value ? 'true' : 'false';
    }

    /** @param array<string, mixed> $expression */
    private function serializeComparison(array $expression): string
    {
        $operatorName = $expression['operator'] ?? null;
        $operator = is_string($operatorName)
            ? $this->conditions[ConditionKind::Comparison->key($operatorName)] ?? null
            : null;

        if (!$operator) {
            throw new InvalidArgumentException('Twig condition comparison operator is not registered.');
        }

        $left = $expression['left'] ?? null;
        $right = $expression['right'] ?? null;

        $serialized =
            $this->serializeOperand($left, $operator->getTypes())
            . ' '
            . $operator->getExpression()
            . ' '
            . $this->serializeOperand($right, $operator->getTypes());

        $leftType = $this->getVariableOperandType($left);
        $rightType = $this->getVariableOperandType($right);

        if (
            $operator->matchesVariableTypes()
            && $leftType !== null
            && $rightType !== null
            && $leftType !== $rightType
        ) {
            throw new InvalidArgumentException('Twig condition comparison variables must have matching types.');
        }

        return $this->negateIfNeeded($serialized, $expression);
    }

    /** @param array<string, mixed> $expression */
    private function serializeExpression(array $expression, int $depth): string
    {
        if ($depth > 10) {
            throw new InvalidArgumentException('Twig condition nesting cannot exceed 10 levels.');
        }

        return match ($expression['type'] ?? null) {
            'group' => $this->serializeGroup($expression, $depth),
            'comparison' => $this->serializeComparison($expression),
            'test' => $this->serializeTest($expression),
            'truthy' => $this->serializeTruthy($expression),
            default => throw new InvalidArgumentException('Unsupported Twig condition expression type.'),
        };
    }

    private function serializeFilter(mixed $filterState, string $variableType): string
    {
        $filterName = is_array($filterState) ? $filterState['name'] ?? null : null;
        $filter = is_string($filterName) ? $this->filters[$filterName] ?? null : null;

        if (!$filter || !$filter->supports($variableType) || $filter->getOutput() !== FilterOutput::Same) {
            throw new InvalidArgumentException('Twig condition filter is not available for this variable.');
        }

        $arguments = $filterState['arguments'] ?? [];

        if (!is_array($arguments) || !array_is_list($arguments)) {
            throw new InvalidArgumentException('Twig condition filter arguments must be a list.');
        }

        foreach ($arguments as $argument) {
            if (!is_string($argument) || trim($argument) === '') {
                throw new InvalidArgumentException(
                    'Twig condition filter arguments must be non-empty Twig expressions.',
                );
            }
        }

        return $arguments === []
            ? "|{$filterName}"
            : "|{$filterName}(" . implode(', ', $arguments) . ')';
    }

    /** @param array<string, mixed> $expression */
    private function serializeGroup(array $expression, int $depth): string
    {
        $operator = $expression['operator'] ?? null;
        $children = $expression['children'] ?? null;

        if (
            !in_array($operator, ['and', 'or'], true)
            || !is_array($children)
            || $children === []
            || count($children) > 50
        ) {
            throw new InvalidArgumentException(
                'Twig condition group must contain between 1 and 50 expressions joined by and or or.',
            );
        }

        $serializedChildren = [];

        foreach ($children as $child) {
            if (!is_array($child)) {
                throw new InvalidArgumentException('Twig condition group children must be expressions.');
            }

            $serializedChildren[] = '(' . $this->serializeExpression($child, $depth + 1) . ')';
        }

        return $this->negateIfNeeded(implode(" {$operator} ", $serializedChildren), $expression);
    }

    private function serializeLiteral(mixed $type, mixed $value): string
    {
        return match ($type) {
            'string' => $this->serializeStringLiteral($value),
            'number' => $this->serializeNumberLiteral($value),
            'boolean' => $this->serializeBooleanLiteral($value),
            default => throw new InvalidArgumentException('Unsupported Twig condition literal type.'),
        };
    }

    private function serializeNumberLiteral(mixed $value): string
    {
        if (
            !is_int($value)
            && !is_float($value)
            && (!is_string($value)
            || preg_match('/^-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?$/', $value) !== 1)
        ) {
            throw new InvalidArgumentException('Twig condition number literal must be an integer or decimal.');
        }

        return (string) $value;
    }

    /** @param list<string> $types */
    private function serializeOperand(mixed $operand, array $types = []): string
    {
        if (!is_array($operand)) {
            throw new InvalidArgumentException('Twig condition operand must be an object.');
        }

        return match ($operand['type'] ?? null) {
            'variable' => $this->serializeVariable($operand, $types),
            'literal' => $this->serializeLiteral($operand['valueType'] ?? null, $operand['value'] ?? null),
            default => throw new InvalidArgumentException('Unsupported Twig condition operand type.'),
        };
    }

    private function serializeStringLiteral(mixed $value): string
    {
        if (!is_string($value) || mb_strlen($value) > 500) {
            throw new InvalidArgumentException(
                'Twig condition string literal must be a string of at most 500 characters.',
            );
        }

        return "'" . str_replace(['\\', "'"], ['\\\\', "\\'"], $value) . "'";
    }

    /** @param array<string, mixed> $expression */
    private function serializeTest(array $expression): string
    {
        $testName = $expression['test'] ?? null;
        $test = is_string($testName)
            ? $this->conditions[ConditionKind::Test->key($testName)] ?? null
            : null;

        if (!$test) {
            throw new InvalidArgumentException('Twig condition test is not registered.');
        }

        $serialized =
            $this->serializeOperand($expression['subject'] ?? null, $test->getTypes())
            . ' is '
            . $test->getExpression();

        return $this->negateIfNeeded($serialized, $expression);
    }

    /** @param array<string, mixed> $expression */
    private function serializeTruthy(array $expression): string
    {
        $condition = $this->conditions[ConditionKind::Truthy->key('truthy')] ?? null;

        if (!$condition) {
            throw new InvalidArgumentException('Twig truthy condition is not registered.');
        }

        return $this->negateIfNeeded(
            $this->serializeOperand($expression['subject'] ?? null, $condition->getTypes()),
            $expression,
        );
    }

    /** @param list<string> $types */
    private function serializeVariable(array $operand, array $types): string
    {
        $name = $operand['name'] ?? null;
        $variable = is_string($name) ? $this->scope->getVariable($name) : null;

        if (!$variable) {
            throw new InvalidArgumentException('Twig condition variable is not available in the current scope.');
        }

        if ($types !== [] && !in_array($variable->getType(), $types, true)) {
            throw new InvalidArgumentException('Twig condition variable type is not supported by this condition.');
        }

        $filters = $operand['filters'] ?? [];

        if (!is_array($filters) || !array_is_list($filters) || count($filters) > 20) {
            throw new InvalidArgumentException('Twig condition variable filters must be a list of at most 20 filters.');
        }

        $expression = $name;

        foreach ($filters as $filterState) {
            $expression .= $this->serializeFilter($filterState, $variable->getType());
        }

        return $expression;
    }
}
