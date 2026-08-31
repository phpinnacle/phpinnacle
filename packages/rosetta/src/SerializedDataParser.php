<?php

namespace PHPinnacle\Rosetta;

use RuntimeException;

/**
 * Parses the positional text serialization used by 1C configuration storage.
 */
final class SerializedDataParser
{
    private string $input = '';

    private int $length = 0;

    private int $offset = 0;

    /** @return list<mixed> */
    public function parse(string $input): array
    {
        $this->input = str_starts_with($input, "\xEF\xBB\xBF") ? substr($input, 3) : $input;
        $this->length = strlen($this->input);
        $this->offset = 0;

        $this->skipWhitespace();
        $result = $this->parseObject();
        $this->skipWhitespace();

        if ($this->offset !== $this->length) {
            throw $this->error('Unexpected data after the root object');
        }

        return $result;
    }

    private function error(string $message): RuntimeException
    {
        return new RuntimeException(sprintf('%s at byte %d', $message, $this->offset));
    }

    private function expect(string $char): void
    {
        if ($this->peek() !== $char) {
            throw $this->error(sprintf('Expected "%s"', $char));
        }

        $this->offset++;
    }

    private function parseBareValue(): string|int|float|null
    {
        $start = $this->offset;

        while ($this->offset < $this->length) {
            $char = $this->input[$this->offset];

            if ($char === ',' || $char === '}') {
                break;
            }

            $this->offset++;
        }

        $value = trim(substr($this->input, $start, $this->offset - $start));

        if ($value === '') {
            return null;
        }

        if (preg_match('/^-?\d+$/D', $value) === 1) {
            return (int) $value;
        }

        return is_numeric($value) ? (float) $value : $value;
    }

    /** @return list<mixed> */
    private function parseObject(): array
    {
        $this->expect('{');
        $items = [];
        $expectValue = true;

        while (true) {
            $this->skipWhitespace();

            if ($this->peek() === '}') {
                $this->offset++;

                return $items;
            }

            if (!$expectValue) {
                $this->expect(',');
                $this->skipWhitespace();
            }

            if ($this->peek() === ',') {
                $items[] = null;
                $expectValue = false;

                continue;
            }

            $items[] = $this->parseValue();
            $expectValue = false;
        }
    }

    private function parseString(): string
    {
        $this->expect('"');
        $value = '';

        while ($this->offset < $this->length) {
            $quote = strpos($this->input, '"', $this->offset);

            if ($quote === false) {
                throw $this->error('Unterminated string');
            }

            $value .= substr($this->input, $this->offset, $quote - $this->offset);
            $this->offset = $quote + 1;

            if ($this->peek() === '"') {
                $value .= '"';
                $this->offset++;

                continue;
            }

            return $value;
        }

        throw $this->error('Unterminated string');
    }

    /** @return array<mixed>|string|int|float|null */
    private function parseValue(): array|string|int|float|null
    {
        return match ($this->peek()) {
            '{' => $this->parseObject(),
            '"' => $this->parseString(),
            null => throw $this->error('Unexpected end of input'),
            default => $this->parseBareValue(),
        };
    }

    private function peek(): ?string
    {
        return $this->offset < $this->length ? $this->input[$this->offset] : null;
    }

    private function skipWhitespace(): void
    {
        while ($this->offset < $this->length && str_contains(" \t\r\n", $this->input[$this->offset])) {
            $this->offset++;
        }
    }
}
