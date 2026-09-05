<?php

namespace PHPinnacle\Casus\Support;

class SensitiveValuePresenter
{
    /**
     * @param array<array-key, mixed>|null $data
     * @return array<array-key, string>|null
     */
    public function present(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        return $this->flatten($this->redact($data));
    }

    /**
     * @param array<array-key, mixed> $data
     * @return array<array-key, mixed>
     */
    public function redact(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if ($this->isSensitive($key)) {
                $result[$key] = config('phpinnacle-casus.redaction.replacement', '[hidden]');

                continue;
            }

            $result[$key] = is_array($value) ? $this->redact($value) : $value;
        }

        return $result;
    }

    /**
     * @param array<array-key, mixed> $data
     * @return array<array-key, string>
     */
    private function flatten(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', array_map(fn ($item) => is_scalar($item)
                    ? (string) $item
                    : json_encode($item, JSON_UNESCAPED_UNICODE), $value));
            } elseif (!is_scalar($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }

            $result[$key] = (string) $value;
        }

        return $result;
    }

    private function isSensitive(int|string $key): bool
    {
        $key = strtolower((string) $key);

        foreach (config('phpinnacle-casus.redaction.keys', []) as $pattern) {
            if (str_contains($key, strtolower($pattern))) {
                return true;
            }
        }

        return false;
    }
}
