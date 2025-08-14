<?php

declare(strict_types=1);

namespace Pluswerk\SentryTestExtension\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function password_hash;
use const PASSWORD_ARGON2I;
use const PASSWORD_DEFAULT;
use const PHP_EOL;

class SetupDbCommand extends Command
{
    private InputInterface $input;
    private OutputInterface $output;

    public function __construct(private ConnectionPool $connectionPool)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Setup testing db');
        $this->addOption('force', 'f', null, 'Force the setup even if the database is already set up');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;
        if ($this->connectionPool->getConnectionForTable('pages')->count('*', 'pages', []) && !$input->getOption('force')) {
            $output->writeln('<info>Database already set up, skipping setup.</info>');
            return 0;
        }

        $this->overwriteInsert('be_users', [
            'username' => '_cli_',
            'admin' => 1,
        ], [
            'username' => 'admin',
            'password' => password_hash('Admin123!', PASSWORD_ARGON2I),
            'admin' => 1,
        ]);
        $this->overwriteInsert('pages', [
            'title' => 'Root ~auto~',
            'pid' => 0,
            'doktype' => 1,
            'hidden' => 0,
            'is_siteroot' => 1,
            'slug' => '/',
        ]);
        $this->overwriteInsert('tt_content', [
            'CType' => 'header',
            'header' => 'Content ~auto~',
            'pid' => 1,
        ]);

        // can be removed if only TYPO3 >=13 is used ( as than siteSet is used )
        $this->overwriteInsert('sys_template', [
            'title' => 'Root ~auto~',
            'pid' => 1,
            'config' => "@import 'EXT:test_extension/Configuration/Sets/MySet/setup.typoscript'",
            'hidden' => 0,
        ]);

        return 0;
    }

    public function overwriteInsert(string $table, ...$dataArray): void
    {
        $this->output->writeln('<info>truncate ' . $table . '</info>');
        $connection = $this->connectionPool->getConnectionForTable($table);
        $connection->truncate($table, true);

        $counter = 0;
        foreach ($dataArray as $data) {
            $counter++;
            $connection->insert($table, [...$data, 'uid' => $counter]);
        }
        $this->output->writeln('<info>inserted ' . $counter . ' rows into ' . $table . '</info>');
    }
}
