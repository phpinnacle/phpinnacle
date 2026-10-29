<?php

namespace PHPinnacle\Ferry\Origins\Kafka;

use PHPinnacle\Ferry\Contracts\ChangeConsumer;
use PHPinnacle\Ferry\Data\ChangeMessage;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RdKafka\TopicPartition;
use RuntimeException;

class RdKafkaChangeConsumer implements ChangeConsumer
{
    private const int METADATA_TIMEOUT = 10_000;

    private readonly KafkaConsumer $consumer;

    public function __construct(string $bootstrapServers, string $groupId)
    {
        $conf = new Conf;
        $conf->set('bootstrap.servers', $bootstrapServers);
        $conf->set('group.id', $groupId);
        $conf->set('enable.auto.commit', 'false');
        $conf->set('auto.offset.reset', 'earliest');

        $this->consumer = new KafkaConsumer($conf);
    }

    public function subscribe(array $topics): void
    {
        $this->consumer->subscribe($topics);
    }

    public function consume(int $timeout): ?ChangeMessage
    {
        $message = $this->consumer->consume($timeout);

        return match ($message->err) {
            RD_KAFKA_RESP_ERR_NO_ERROR => new ChangeMessage(
                $message->topic_name,
                $message->partition,
                $message->offset,
                $message->payload,
            ),
            RD_KAFKA_RESP_ERR__TIMED_OUT,
            RD_KAFKA_RESP_ERR__PARTITION_EOF,
            RD_KAFKA_RESP_ERR_UNKNOWN_TOPIC_OR_PART,
                => null,
            default => throw new RuntimeException($message->errstr()),
        };
    }

    public function commit(ChangeMessage $message): void
    {
        $this->consumer->commit([
            new TopicPartition($message->topic, $message->partition, $message->offset + 1),
        ]);
    }

    public function lag(array $topics): array
    {
        $partitions = [];

        foreach ($topics as $topic) {
            $metadata = $this->consumer->getMetadata(
                false,
                $this->consumer->newTopic($topic),
                self::METADATA_TIMEOUT,
            );

            foreach ($metadata->getTopics() as $described) {
                foreach ($described->getPartitions() as $partition) {
                    $partitions[] = new TopicPartition($topic, $partition->getId());
                }
            }
        }

        $lag = array_fill_keys($topics, 0);
        /** @var list<TopicPartition> $committed */
        $committed = $this->consumer->getCommittedOffsets($partitions, self::METADATA_TIMEOUT);

        foreach ($committed as $partition) {
            $low = 0;
            $high = 0;
            $this->consumer->queryWatermarkOffsets(
                $partition->getTopic(),
                $partition->getPartition(),
                $low,
                $high,
                self::METADATA_TIMEOUT,
            );

            /**
             * @var int $low
             * @var int $high
             */
            $lag[$partition->getTopic()] += $high - ($partition->getOffset() >= 0 ? $partition->getOffset() : $low);
        }

        return $lag;
    }

    public function close(): void
    {
        $this->consumer->close();
    }
}
