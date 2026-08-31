<?php

namespace PHPinnacle\Chronica\Timeline;

use Closure;
use DateTimeInterface;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;

class Attribute
{
    public function __construct(
        public string $name,
        public ?string $label = null,
        public ?Closure $formatter = null,
    ) {}

    public static function make(string $name): self
    {
        return new self($name);
    }

    private static function formatDate(string $type): Closure
    {
        return function (?DateTimeInterface $value) use ($type) {
            return $value !== null ? Date::parse($value)->translatedFormat(self::getFormat($type)) : null;
        };
    }

    private static function getFormat(string $type): string
    {
        static $schema = new Schema()->configure();

        return match ($type) {
            'datetime' => $schema->getDefaultDateTimeDisplayFormat(),
            'date' => $schema->getDefaultDateDisplayFormat(),
            'time' => $schema->getDefaultTimeDisplayFormat(),
            default => throw new InvalidArgumentException(sprintf('Unsupported date format type [%s].', $type)),
        };
    }

    public function date(): self
    {
        return $this->formatter(self::formatDate('date'));
    }

    public function datetime(): self
    {
        return $this->formatter(self::formatDate('datetime'));
    }

    public function formatter(Closure $formatter): self
    {
        $this->formatter = $formatter;

        return $this;
    }

    public function label(string $label, ?int $case = null): self
    {
        $this->label = $case !== null ? mb_convert_case($label, $case) : $label;

        return $this;
    }

    public function time(): self
    {
        return $this->formatter(self::formatDate('time'));
    }
}
