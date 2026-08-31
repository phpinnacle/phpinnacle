<?php

namespace PHPinnacle\Babylon\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Locale;
use PHPinnacle\Settings\Models\Preference;

class SetLocale
{
    public function handle(Request $request, Closure $next): mixed
    {
        $locale = $request->hasSession() ? $request->session()->get('locale') : null;

        if ($locale === null) {
            $user = $request->user();
            $locale = $user !== null ? Preference::get($user, 'babylon', 'locale') : null;
            $locale ??= App::getLocale();
        }

        App::setLocale($locale);
        Locale::setDefault($locale);

        return $next($request);
    }
}
