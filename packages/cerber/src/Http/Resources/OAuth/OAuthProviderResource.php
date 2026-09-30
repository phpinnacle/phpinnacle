<?php

namespace PHPinnacle\Cerber\Http\Resources\OAuth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use PHPinnacle\Cerber\Models\Provider;
use PHPinnacle\Cerber\Services\ProviderRegistry;

class OAuthProviderResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Provider $provider */
        $provider = $this->resource;

        $authProvider = app(ProviderRegistry::class)->get($provider->type);

        return [
            'id' => $provider->getKey(),
            'name' => $provider->getLabel(),
            'icon' => $authProvider?->getIcon(),
            'color' => $authProvider?->getColor(),
        ];
    }
}
