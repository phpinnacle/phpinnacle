<?php

namespace PHPinnacle\Cerber\Contracts;

use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\Email\Contracts\HasEmailAuthentication;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasName;
use Filament\Models\Contracts\HasTenants;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;

interface Viewer extends
    Authenticatable,
    Authorizable,
    FilamentUser,
    HasAppAuthentication,
    HasAppAuthenticationRecovery,
    HasAvatar,
    HasDefaultTenant,
    HasEmailAuthentication,
    HasName,
    HasTenants,
    MustVerifyEmail {}
