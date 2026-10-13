<?php

namespace PHPinnacle\Midas\Services;

use Closure;
use Illuminate\Queue\DatabaseQueue;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use LogicException;
use PHPinnacle\Midas\Contracts\AuthorizationGateway;
use PHPinnacle\Midas\Contracts\QueuedGateway;
use PHPinnacle\Midas\Contracts\RefundGateway;
use PHPinnacle\Midas\Enums\TransactionType;
use PHPinnacle\Midas\Jobs\ProcessPayment;
use PHPinnacle\Midas\Models\Intent;
use PHPinnacle\Midas\Models\PaymentMethod;
use PHPinnacle\Midas\Models\Transaction;
use PHPinnacle\Money\Money;

class PaymentManager
{
    public function __construct(
        private ProviderRegistry $providers,
    ) {}

    public function paymentNow(Intent $intent): Transaction
    {
        $transaction = Transaction::payment($intent);

        if (!$intent->method->isOnline()) {
            return $transaction;
        }

        $gateway = $this->providers->get($intent->method->provider);

        return $transaction->handle($gateway->intent($intent));
    }

    public function payment(Intent $intent): Transaction
    {
        return $this->record($intent, fn () => Transaction::payment($intent));
    }

    public function authorize(Intent $intent): Transaction
    {
        return $this->record($intent, fn () => Transaction::authorize($intent));
    }

    public function capture(Transaction $parent, string $number, Money $amount): Transaction
    {
        return $this->record($parent, fn () => $parent->capture($number, $amount));
    }

    public function void(Transaction $parent, string $number, Money $amount): Transaction
    {
        return $this->record($parent, fn () => $parent->void($number, $amount));
    }

    public function refund(Transaction $parent, string $number, Money $amount, string $reason): Transaction
    {
        return $this->record($parent, fn () => $parent->refund($number, $amount, $reason));
    }

    public function synchronize(Transaction $transaction): void
    {
        $this->gateway($transaction->method);
        $this->dispatch($transaction, new ProcessPayment($transaction->id));
    }

    public function gateway(PaymentMethod $method): QueuedGateway
    {
        $gateway = $this->providers->get($method->provider);

        if (!$gateway instanceof QueuedGateway) {
            throw new LogicException('This provider does not support durable, idempotent execution.');
        }

        return $gateway;
    }

    /** @param Closure(): Transaction $create */
    private function record(Intent|Transaction $origin, Closure $create): Transaction
    {
        $method = $origin->method;

        return $method
            ->getConnection()
            ->transaction(function () use ($method, $create, $origin) {
                $transaction = $create();

                if (!$method->isOnline()) {
                    return $transaction;
                }

                $gateway = $this->gateway($method);

                if (
                    in_array(
                        $transaction->type,
                        [TransactionType::AUTHORIZE, TransactionType::CAPTURE, TransactionType::VOID],
                        true,
                    )
                    && !$gateway instanceof AuthorizationGateway
                ) {
                    throw new LogicException('This provider does not support authorization operations.');
                }

                if ($transaction->type === TransactionType::REFUND && !$gateway instanceof RefundGateway) {
                    throw new LogicException('This provider does not support refunds.');
                }

                $request = $origin instanceof Intent ? $gateway->prepare($origin) : $gateway->derive($transaction);

                if ($request->metadata !== []) {
                    $transaction->metadata = array_replace($transaction->metadata, $request->metadata);
                    $transaction->save();
                }

                $this->dispatch($transaction, new ProcessPayment($transaction->id, $request));

                return $transaction;
            });
    }

    private function dispatch(Transaction $transaction, ProcessPayment $job): void
    {
        $connection = config('phpinnacle-midas.queue.connection');
        $queue = Queue::connection($connection);

        if (!$queue instanceof DatabaseQueue || $queue->getDatabase() !== $transaction->getConnection()) {
            throw new LogicException(
                'Midas jobs require the database queue on the same database connection as its transactions.',
            );
        }

        Bus::dispatch($job->onConnection($connection)->onQueue(config('phpinnacle-midas.queue.queue'))->beforeCommit());
    }
}
