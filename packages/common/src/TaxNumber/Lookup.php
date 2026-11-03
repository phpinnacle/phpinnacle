<?php

namespace PHPinnacle\Common\TaxNumber;

use Illuminate\Contracts\Cache\Repository;

final class Lookup
{
    public function __construct(
        private readonly ?Repository $cacheStore = null,
        private readonly int $ttl = 3600,
    ) {}

    public function resolve(Country $source, string $number): LookupResult
    {
        $number = $source->normalize($number);

        if (!$source->validate($number)) {
            return LookupResult::failure(LookupFailure::InvalidNumber);
        }

        if ($this->cacheStore === null || $this->ttl <= 0) {
            return $source->lookup($number);
        }

        $key = 'phpinnacle-common:tax-number:' . sha1($source::class . ':' . $source->code() . ':' . $number);
        /** @var array{name: string, status: ?string, address: ?string}|null $cached */
        $cached = $this->cacheStore->get($key);

        if ($cached !== null) {
            return LookupResult::success(...$cached);
        }

        $result = $source->lookup($number);

        $data = $result->data();

        if ($data !== null) {
            $this->cacheStore->put($key, $data, $this->ttl);
        }

        return $result;
    }
}
