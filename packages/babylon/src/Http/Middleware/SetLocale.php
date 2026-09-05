<?php

namespace PHPinnacle\Babylon\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Locale;
use PHPinnacle\Babylon\BabylonPlugin;
use PHPinnacle\Settings\Models\Preference;

class SetLocale
{
    public function handle(Request $request, Closure $next): mixed
    {
        $locale = $this->resolveLocale($request);

        App::setLocale($locale);
        Locale::setDefault($locale);

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        $plugin = BabylonPlugin::get();

        if ($request->hasSession()) {
            /** @var ?string $locale */
            $locale = $request->session()->get(
                $plugin->getSessionKey(),
            ) ?? $request->session()->get(BabylonPlugin::PREFERENCE_KEY);

            if ($locale !== null) {
                return $locale;
            }
        }

        if (($user = $request->user()) !== null) {
            /** @var ?string $locale */
            $locale = Preference::get(
                $user,
                $plugin->getPreferenceGroup(),
                BabylonPlugin::PREFERENCE_KEY,
            ) ?? Preference::get($user, BabylonPlugin::PREFERENCE_GROUP, BabylonPlugin::PREFERENCE_KEY);

            if ($locale !== null) {
                return $locale;
            }
        }

        $locales = array_keys($plugin->getLocales());

        if ($locales !== [] && $request->headers->has('Accept-Language')) {
            return $request->getPreferredLanguage($locales) ?? App::getLocale();
        }

        return App::getLocale();
    }
}
