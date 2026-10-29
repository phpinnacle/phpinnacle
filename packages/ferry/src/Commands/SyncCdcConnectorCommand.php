<?php

namespace PHPinnacle\Ferry\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PHPinnacle\Ferry\Contracts\ChangeConsumer;
use PHPinnacle\Ferry\Data\TrackedTable;
use PHPinnacle\Ferry\Enums\ConnectorStatus;
use PHPinnacle\Ferry\Origins\Kafka\SourceFactory;
use PHPinnacle\Ferry\Services\ConnectorManager;
use PHPinnacle\Ferry\Services\TrackedTableRegistry;

class SyncCdcConnectorCommand extends Command
{
    protected $signature = 'ferry:cdc-sync
        {--status : Report connector state, replica identity and lag without pushing configuration}';

    protected $description = 'Synchronize or inspect the Debezium source connectors of tracked application tables';

    public function handle(
        TrackedTableRegistry $registry,
        ConnectorManager $connectors,
        SourceFactory $sources,
        ChangeConsumer $consumer,
    ): int {
        $healthy = true;

        foreach ($registry->byConnection() as $connection => $tables) {
            $result = $this->option('status')
                ? $connectors->checkApplicationSource($connection)
                : $connectors->syncApplicationSource($connection, $tables);

            $this->components->twoColumnDetail(
                $sources->applicationName($connection),
                sprintf(
                    '%s, WAL lag: %s bytes',
                    $result['status']->value,
                    $this->walLag($connection, $sources->applicationSlot($connection)) ?? '-',
                ),
            );

            if ($result['status'] !== ConnectorStatus::Running) {
                $healthy = false;

                if ($result['error'] !== null) {
                    $this->components->error($result['error']);
                }
            }

            foreach ($this->withoutFullReplicaIdentity($connection, $tables) as $table) {
                $healthy = false;

                $this->components->error(sprintf('%s does not use REPLICA IDENTITY FULL.', $table));
            }
        }

        foreach ($consumer->lag($registry->topics()) as $topic => $lag) {
            $this->components->twoColumnDetail($topic, sprintf('consumer lag: %d', $lag));
        }

        return $healthy ? self::SUCCESS : self::FAILURE;
    }

    private function walLag(string $connection, string $slot): ?int
    {
        /** @var object{lag: int|numeric-string}|null $row */
        $row = DB::connection($connection)->selectOne(
            'SELECT pg_wal_lsn_diff(pg_current_wal_lsn(), confirmed_flush_lsn)::bigint AS lag FROM pg_replication_slots WHERE slot_name = ?',
            [$slot],
        );

        return $row === null ? null : (int) $row->lag;
    }

    /**
     * @param list<TrackedTable> $tables
     *
     * @return list<string>
     */
    private function withoutFullReplicaIdentity(string $connection, array $tables): array
    {
        $qualified = array_values(array_unique(array_map(
            fn (TrackedTable $table) => $table->qualifiedTable(),
            $tables,
        )));

        /** @var list<string> $full */
        $full = DB::connection($connection)
            ->table('pg_class')
            ->join('pg_namespace', 'pg_namespace.oid', '=', 'pg_class.relnamespace')
            ->where('pg_class.relreplident', 'f')
            ->whereIn(DB::raw("pg_namespace.nspname || '.' || pg_class.relname"), $qualified)
            ->selectRaw("pg_namespace.nspname || '.' || pg_class.relname AS qualified")
            ->pluck('qualified')
            ->all();

        return array_values(array_diff($qualified, $full));
    }
}
