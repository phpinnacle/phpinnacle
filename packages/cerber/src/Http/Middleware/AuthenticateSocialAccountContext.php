<?php

namespace PHPinnacle\Cerber\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PHPinnacle\Cerber\Models\User;
use PHPinnacle\Cerber\Services\SocialAccountContextRepository;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;
use Symfony\Component\HttpFoundation\Response;

readonly class AuthenticateSocialAccountContext
{
    public function __construct(
        private SocialAccountContextRepository $contexts,
    ) {}

    public function handle(Request $request, Closure $next, string $name): Response
    {
        $context = $this->contexts->get($name);
        $guard = auth()->guard($context->guard());
        /** @var User $user */
        $user = $guard->authenticate();
        abort_unless($user->status->canAccess(), 403);

        if ($guard instanceof JWTGuard) {
            abort_unless($guard->payload()->get('social_account_context') === $context->name(), 403);
        }

        auth()->shouldUse($context->guard());

        return $next($request);
    }
}
