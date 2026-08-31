<?php

namespace PHPinnacle\Cerber\Http\Controllers;

use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use PHPinnacle\Cerber\CerberPlugin;
use PHPinnacle\Cerber\Http\Requests\LoginRequest;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request): RedirectResponse
    {
        $panel = Filament::getPanel($request->validated('panel'));
        /** @var CerberPlugin $plugin */
        $plugin = $panel->getPlugin(CerberPlugin::ID);

        $credentials = $request->validated('credentials');
        $tenant = null;

        if ($request->route()->hasParameter('tenant')) {
            $tenant = $panel->getTenant($request->route()->parameter('tenant'));
        }

        if (!$plugin->auth($credentials, $panel, $tenant)) {
            throw new AuthorizationException;
        }

        return redirect()->to($panel->getUrl($tenant));
    }
}
