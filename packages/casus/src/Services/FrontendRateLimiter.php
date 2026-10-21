<?php

namespace PHPinnacle\Casus\Services;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class FrontendRateLimiter
{
    /**
     * @return list<Limit>
     */
    public function limits(Request $request): array
    {
        /** @var array{global: array<positive-int, positive-int>, per_ip: array<positive-int, positive-int>} $windows */
        $windows = Config::array('phpinnacle-casus.frontend.rate_limits');
        $limits = [];

        foreach (['global' => 'global', 'per_ip' => 'ip:' . $request->ip()] as $scope => $key) {
            foreach ($windows[$scope] as $seconds => $requests) {
                $limits[] = Limit::perSecond($requests, $seconds)->by($key . ':' . $seconds);
            }
        }

        return $limits;
    }
}
