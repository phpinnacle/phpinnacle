<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPinnacle\Midas\Models\Intent;
use PHPinnacle\Midas\Models\IntentLine;
use PHPinnacle\Midas\Models\Payer;
use PHPinnacle\Midas\Models\PaymentPlan;
use PHPinnacle\Midas\Models\Source;
use PHPinnacle\Midas\Models\Transaction;
use PHPinnacle\Midas\Payments\Cash;
use PHPinnacle\Money\Money;
use Tests\TestCase;

uses(TestCase::class);

class MidasMigrationTenant extends Model
{
    public $timestamps = false;
}

it('assigns new payment records to the configured default tenant', function () {
    Schema::create('midas_migration_tenants', function (Blueprint $table) {
        $table->id();
    });
    $tenant = new MidasMigrationTenant;
    $tenant->save();
    config()->set('phpinnacle-midas.tenancy', ['model' => MidasMigrationTenant::class, 'default' => $tenant->id]);
    (require __DIR__ . '/../../database/migrations/create_midas_tables.php')->up();

    $method = new Cash()->define();
    $method->save();
    $plan = PaymentPlan::query()->create(['name' => 'Single payment', 'parts' => [['value' => 100, 'delay' => 0]]]);
    $payment = Transaction::payment(new Intent(
        id: (string) Str::uuid(),
        number: 'PAY-TENANT',
        description: 'Tenant payment',
        method: $method,
        source: new Source('00000000-0000-0000-0000-000000000001', 'order'),
        payer: new Payer('00000000-0000-0000-0000-000000000002', 'customer'),
        instrument: null,
        lines: [new IntentLine('Item', 1, new Money(1000, 'USD'))],
    ));

    expect($method->fresh()->getAttribute('tenant_id'))
        ->toBe($tenant->id)
        ->and($plan->fresh()->getAttribute('tenant_id'))
        ->toBe($tenant->id)
        ->and($payment->fresh()->getAttribute('tenant_id'))
        ->toBe($tenant->id);
});
