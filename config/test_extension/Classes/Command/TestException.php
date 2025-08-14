<?php

declare(strict_types=1);

namespace Pluswerk\SentryTestExtension\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestException extends Command
{
    protected function configure(): void
    {
        $this->setDescription('throws a test exception');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('seed: ' . (getenv('SENTRY_MOCK_SEED') ?? 'not set'));
        throw new \RuntimeException('throws a test exception');
    }
}
