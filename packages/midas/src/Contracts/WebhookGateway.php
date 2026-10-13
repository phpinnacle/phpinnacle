<?php

namespace PHPinnacle\Midas\Contracts;

use Illuminate\Http\Request;
use PHPinnacle\Midas\Models\Transaction;

interface WebhookGateway extends PaymentGateway
{
    public function acceptsWebhook(Transaction $transaction, Request $request): bool;
}
