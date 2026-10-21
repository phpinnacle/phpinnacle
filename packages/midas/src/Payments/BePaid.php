<?php

namespace PHPinnacle\Midas\Payments;

use DateTimeImmutable;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Support\Colors\Color;
use Illuminate\Http\Request;
use PHPinnacle\Eidos\JsonSchema\Schema;
use PHPinnacle\Midas\Contracts\AuthorizationGateway;
use PHPinnacle\Midas\Contracts\QueuedGateway;
use PHPinnacle\Midas\Contracts\RefundGateway;
use PHPinnacle\Midas\Contracts\WebhookGateway;
use PHPinnacle\Midas\Enums\Ability;
use PHPinnacle\Midas\Enums\AmountMode;
use PHPinnacle\Midas\Enums\TransactionStatus;
use PHPinnacle\Midas\Enums\TransactionType;
use PHPinnacle\Midas\Instruments\EncryptedCard;
use PHPinnacle\Midas\Models\Continuation;
use PHPinnacle\Midas\Models\CreditCard;
use PHPinnacle\Midas\Models\GatewayRequest;
use PHPinnacle\Midas\Models\Intent;
use PHPinnacle\Midas\Models\Notification;
use PHPinnacle\Midas\Models\Payer;
use PHPinnacle\Midas\Models\PaymentMethod;
use PHPinnacle\Midas\Models\Transaction;
use PHPinnacle\Midas\Services\BePaid\CardClient;
use PHPinnacle\Midas\Services\BePaid\TransactionResponse;
use PHPinnacle\Midas\Services\BePaid\Webhook;

class BePaid extends Base implements AuthorizationGateway, QueuedGateway, RefundGateway, WebhookGateway
{
    public function amountMode(TransactionType $operation): AmountMode
    {
        return AmountMode::Partial;
    }

    public function acceptsWebhook(Transaction $transaction, Request $request): bool
    {
        return Webhook::accepts($transaction, $request);
    }

    public function prepare(Intent $intent): GatewayRequest
    {
        return $this->client($intent->method)->prepare($intent);
    }

    public function derive(Transaction $transaction): GatewayRequest
    {
        return $this->client($transaction->method)->derive($transaction);
    }

    public function execute(Transaction $transaction, array $payload): Continuation
    {
        return $this->complete(
            $transaction->method,
            new Payer($transaction->payer_id, $transaction->payer_type),
            $this->client($transaction->method)->execute($transaction, $payload),
            persistCard: $transaction->metadata['persist_card'] ?? false,
        );
    }

    public function synchronize(Transaction $transaction): Continuation
    {
        return $this->complete(
            $transaction->method,
            new Payer($transaction->payer_id, $transaction->payer_type),
            $this->client($transaction->method)->synchronize($transaction),
            persistCard: $transaction->metadata['persist_card'] ?? false,
        );
    }

    public function key(): string
    {
        return 'bepaid';
    }

    public function getColor(): array
    {
        return Color::Orange;
    }

    public function getIcon(): string
    {
        return 'phosphor-credit-card';
    }

    public function getDescription(): string
    {
        return __('phpinnacle-midas::providers.bepaid.description');
    }

    public function getLabel(): string
    {
        return __('phpinnacle-midas::providers.bepaid.label');
    }

    /** @param array<string, mixed> $settings */
    public function validate(array $settings): bool
    {
        return (
            ($settings['shop_id'] ?? null) !== null
            && ($settings['public_key'] ?? null) !== null
            && ($settings['secret_key'] ?? null) !== null
        );
    }

    public function capture(Transaction $transaction): Continuation
    {
        return $this->client($transaction->method)->capture($transaction);
    }

    public function void(Transaction $transaction): Continuation
    {
        return $this->client($transaction->method)->void($transaction);
    }

    public function refund(Transaction $transaction): Continuation
    {
        return $this->client($transaction->method)->refund($transaction);
    }

    /** @return array<Component> */
    public function form(): array
    {
        return [
            Group::make()
                ->columns()
                ->schema([
                    TextInput::make('shop_id')
                        ->label(__('phpinnacle-midas::providers.bepaid.fields.shop_id'))
                        ->integer()
                        ->required(),
                    Group::make()
                        ->columns(4)
                        ->schema([
                            TextInput::make('timeout')
                                ->label(__('phpinnacle-midas::providers.bepaid.fields.timeout'))
                                ->columnSpan(2)
                                ->integer()
                                ->step(1)
                                ->maxValue(60 * 60 * 24)
                                ->minValue(0)
                                ->default(0),
                            Toggle::make('test_mode')
                                ->label(__('phpinnacle-midas::providers.bepaid.fields.test_mode'))
                                ->inline(false)
                                ->default(false),
                        ]),
                    TextInput::make('public_key')
                        ->label(__('phpinnacle-midas::providers.bepaid.fields.public_key'))
                        ->required()
                        ->password()
                        ->revealable(),
                    TextInput::make('secret_key')
                        ->label(__('phpinnacle-midas::providers.bepaid.fields.secret_key'))
                        ->required()
                        ->password()
                        ->revealable(),
                ]),
            TextInput::make('return_url')
                ->label(__('phpinnacle-midas::providers.webpay.fields.return_url'))
                ->helperText(__('phpinnacle-midas::providers.webpay.help.return_url')),
            TextInput::make('cancel_url')
                ->label(__('phpinnacle-midas::providers.webpay.fields.cancel_url'))
                ->helperText(__('phpinnacle-midas::providers.webpay.help.cancel_url')),
        ];
    }

    public function abilities(): array
    {
        return [
            Ability::Online,
            Ability::Recurring,
            Ability::Refund,
        ];
    }

    public function schema(PaymentMethod $method, Payer $payer): Schema
    {
        $tokens = CreditCard::list($method, $payer);
        $requiredCardFields = [
            'holder',
            'number',
            'verification_value',
            'exp_month',
            'exp_year',
            'persist',
        ];

        return Schema::fromArray([
            'type' => 'object',
            'properties' => [
                'card' => [
                    'type' => ['object', 'null'],
                    'required' => $requiredCardFields,
                    'properties' => [
                        'holder' => ['type' => 'string'],
                        'number' => ['type' => 'string'],
                        'verification_value' => ['type' => 'string'],
                        'exp_month' => ['type' => 'string'],
                        'exp_year' => ['type' => 'string'],
                        'persist' => ['type' => 'boolean', 'default' => false],
                    ],
                    'x-public-key' => $method->settings['public_key'],
                    'x-validation' => 'required_array_keys:' . implode(',', $requiredCardFields),
                ],
                'token' => [
                    'type' => ['string', 'null'],
                    'format' => 'uuid',
                    'enum' => $tokens->pluck('id')->all(),
                    'x-cards' => $tokens->map(fn (CreditCard $card) => [
                        'id' => $card->id,
                        'brand' => $card->brand,
                        'subbrand' => $card->subbrand,
                        'bin' => $card->bin,
                        'mask' => $card->mask,
                        'expires_at' => $card->expires_at->format(DateTimeImmutable::ATOM),
                        'is_expired' => $card->expires_at->isPast(),
                    ])->all(),
                ],
            ],
            'oneOf' => [['required' => ['card']], ['required' => ['token']]],
        ]);
    }

    public function intent(Intent $intent): Continuation
    {
        return $this->complete(
            $intent->method,
            $intent->payer,
            $this->client($intent->method)->payment($intent),
            persistCard: $intent->instrument instanceof EncryptedCard && $intent->instrument->persist,
        );
    }

    public function authorize(Intent $intent): Continuation
    {
        return $this->complete(
            $intent->method,
            $intent->payer,
            $this->client($intent->method)->authorize($intent),
            persistCard: $intent->instrument instanceof EncryptedCard && $intent->instrument->persist,
        );
    }

    public function handle(Notification $notification): Continuation
    {
        return $this->complete(
            $notification->method,
            $notification->payer,
            TransactionResponse::parse($notification->payload)->notificationContinuation(),
            persistCard: (bool) filter_var($notification->payload['persist'] ?? false, FILTER_VALIDATE_BOOLEAN),
        );
    }

    private function complete(
        PaymentMethod $method,
        Payer $payer,
        Continuation $continuation,
        bool $persistCard,
    ): Continuation {
        if (!$persistCard || $continuation->status !== TransactionStatus::Success) {
            return $continuation;
        }

        $details = TransactionResponse::card($continuation->response['transaction']['credit_card'] ?? null);

        if ($details !== null) {
            $continuation->metadata['payment_card_id'] = CreditCard::store($method, $payer, $details)->getKey();
        }

        return $continuation;
    }

    private function client(PaymentMethod $method): CardClient
    {
        return CardClient::create($method->settings);
    }
}
