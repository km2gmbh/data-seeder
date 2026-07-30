<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use TYPO3\CMS\Core\Database\ConnectionPool;

#[AsCommand(
    name: 'database:flush',
    description: 'Flush all data in the database.',
)]
final class FlushDatabaseCommand extends Command
{
    public function __construct(private readonly ConnectionPool $connectionPool)
    {
        parent::__construct('database:flush');
    }

    public function configure(): void
    {
        $this->setHelp('This command will delete all database data.');
        $this->addOption(
            'delete-tables',
            'd',
            InputOption::VALUE_NONE,
            'Delete all tables instead of truncate.'
        );
        $this->addOption(
            'connection',
            'c',
            InputOption::VALUE_OPTIONAL,
            'The database connection to use.',
            'Default'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->isInteractive()) {
            /** @var FormatterHelper $formatterHelper */
            $formatterHelper = $this->getHelper('formatter');
            $message = $formatterHelper->formatBlock(
                'Executing this action will destroy all data in the database.',
                'warning'
            );
            $output->writeln($message);

            /** @var QuestionHelper $questionHelper */
            $questionHelper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Do you want to continue? (y/n) ', false);
            if (!$questionHelper->ask($input, $output, $question)) {
                return Command::SUCCESS;
            }
        }

        $connectionIdentifier = $input->getOption('connection');
        $output->writeln(sprintf('Flushing database contents for connection %s.', $connectionIdentifier));

        $connection = $this->connectionPool->getConnectionByName($connectionIdentifier);
        $databaseTables = $connection->createSchemaManager()->listTableNames();

        foreach ($databaseTables as $databaseTable) {
            if ($input->getOption('delete-tables')) {
                $cropStatement = $connection->getDatabasePlatform()->getDropTableSQL($databaseTable);
                $connection->executeStatement($cropStatement);
                continue;
            }
            $connection->truncate($databaseTable);
        }

        return Command::SUCCESS;
    }
}
