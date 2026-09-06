<?php

namespace PHPinnacle\Chronica\Timeline;

use BackedEnum;
use Closure;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;
use TypeError;
use ValueError;

class ValueFormatter
{
    public function __construct(
        public bool $boolean = false,
        public string|BackedEnum $trueIcon = Heroicon::OutlinedCheckCircle,
        public string|BackedEnum $falseIcon = Heroicon::OutlinedXCircle,
        public string $trueColor = 'success',
        public string $falseColor = 'danger',
        public string|BackedEnum|null $nullIcon = null,
    ) {}

    /**
     * @return array{badge: bool, color: array<string>|string|null, icon: BackedEnum|Htmlable|string|null, text: Htmlable|string}
     */
    public function format(Activity $activity, string $name, mixed $value, ?Closure $formatter = null): array
    {
        $presentable = $formatter === null ? $this->presentableValue($activity, $name, $value) : null;

        if ($formatter !== null) {
            $value = $this->castValue($activity, $name, $value);
        }

        $label = $presentable instanceof HasLabel ? $presentable->getLabel() : null;
        $text = $formatter !== null
            ? (string) $formatter($value)
            : match (true) {
                $label !== null => $label,
                $value === null => '—',
                is_bool($value) => $value ? 'true' : 'false',
                is_scalar($value) => (string) $value,
                default => json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            };

        return match (true) {
            $value === null && $this->nullIcon !== null => [
                'badge' => false,
                'color' => 'gray',
                'icon' => $this->nullIcon,
                'text' => $text,
            ],
            is_bool($value) && $this->boolean => [
                'badge' => false,
                'color' => $value ? $this->trueColor : $this->falseColor,
                'icon' => $value ? $this->trueIcon : $this->falseIcon,
                'text' => $text,
            ],
            $presentable !== null => [
                'badge' => $presentable instanceof HasIcon || $presentable instanceof HasColor,
                'color' => $presentable instanceof HasColor ? $presentable->getColor() : null,
                'icon' => $presentable instanceof HasIcon ? $presentable->getIcon() : null,
                'text' => $text,
            ],
            default => [
                'badge' => false,
                'color' => null,
                'icon' => null,
                'text' => $text,
            ],
        };
    }

    private function castValue(Activity $activity, string $name, mixed $value): mixed
    {
        $class = Model::getActualClassNameForMorph($activity->subject_type);
        $model = $activity->subject !== null ? clone $activity->subject : new $class;
        $model->setRawAttributes([$name => $value]);

        return $model->getAttribute($name);
    }

    private function presentableValue(Activity $activity, string $name, mixed $value): HasColor|HasIcon|HasLabel|null
    {
        $candidates = is_array($value) && array_key_exists('id', $value)
            ? [$value, $value['id']]
            : [$value];

        foreach ($candidates as $candidate) {
            try {
                $candidate = $this->castValue($activity, $name, $candidate);
            } catch (TypeError|ValueError) {
                continue;
            }

            if ($candidate instanceof HasColor || $candidate instanceof HasIcon || $candidate instanceof HasLabel) {
                return $candidate;
            }
        }

        return null;
    }
}
