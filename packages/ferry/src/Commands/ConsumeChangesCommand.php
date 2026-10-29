<?php

namespace PHPinnacle\Ferry\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Context;
use PHPinnacle\Ferry\Contracts\ChangeConsumer;
use PHPinnacle\Ferry\Services\TrackedTableRegistry;

class ConsumeChangesCommand extends Command
{
    private const int POLL_TIMEOUT = 1000;

    protected $signature = 'ferry:cdc-consume
        {--once : Stop after the first poll}
        {--max-messages=0 : Stop after processing this many messages}';

    protected $description = 'Consume application database changes from Kafka and dispatch record change events';

    public function handle(ChangeConsumer $consumer, TrackedTableRegistry $registry, Dispatcher $events): int
    {
        $topics = $registry->topics();

        if ($topics === []) {
            $this->components->info('No tracked tables registered.');

            return self::SUCCESS;
        }

        $running = true;
        $this->trap([SIGTERM, SIGINT], function () use (&$running) {
            $running = false;
        });

        $once = (bool) $this->option('once');
        $limit = (int) $this->option('max-messages');
        $processed = 0;

        $consumer->subscribe($topics);

        try {
            while ($running) {
                $message = $consumer->consume(self::POLL_TIMEOUT);

                if ($message !== null) {
                    Context::add('ferry_cdc_message', $message->id());

                    $event = $registry->change($message);

                    if ($event !== null) {
                        $events->dispatch($event);
                    }

                    $consumer->commit($message);
                    $processed++;
                }

                if ($once || $limit > 0 && $processed >= $limit) {
                    break;
                }
            }
        } finally {
            $consumer->close();
        }

        return self::SUCCESS;
    }
}
