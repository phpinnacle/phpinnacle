<?php

namespace PHPinnacle\Cerber\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $role_id
 * @property string $email
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
class Invitation extends Model
{
    use HasUuids;

    public $timestamps = true;

    protected $table = 'invitations';
}
