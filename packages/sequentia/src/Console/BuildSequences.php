<?php

namespace PHPinnacle\Sequentia\Console;

use Illuminate\Console\Command;
use PHPinnacle\Sequentia\SequenceWatcher;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'sequentia:rebuild')]
class BuildSequences extends Command
{
    protected $description = 'Rebuild all registered sequence counters';

    public function handle(): int
    {
        SequenceWatcher::rebuild();

        $this->components->info('Sequence counters rebuilt.');

        return self::SUCCESS;
    }
}
