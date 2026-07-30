<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\Command;

use KM2\DataSeeder\Configuration\ConfigurationFactory;
use KM2\DataSeeder\Configuration\ConfigurationPropertyException;
use KM2\DataSeeder\Configuration\DataTransferObject\Configuration;
use KM2\DataSeeder\Configuration\MissingConfigurationFileException;
use KM2\DataSeeder\DataHandling\DataSeeder;
use KM2\DataSeeder\DataHandling\Exception\IncompleteNodePrecessingException;
use KM2\DataSeeder\DataHandling\Loader\DataLoaderFactory;
use KM2\DataSeeder\DataHandling\Node\MissingParentException;
use KM2\DataSeeder\DataHandling\ProgressIndicator;
use KM2\DataSeeder\DataHandling\Property\Property;
use KM2\DataSeeder\DataHandling\SeedingData;
use KM2\DataSeeder\Operation\OperationManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator as SymfonyProgressIndicator;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[AsCommand(
    name: 'database:seed',
    description: 'This command seeds data from the configuration file into TYPO3 database.',
)]
final class DatabaseSeedCommand extends Command
{
    private const string DEFAULT_CONFIG_FILENAME = 'seeder.yaml';

    private SymfonyStyle $output;

    public function __construct(
        private readonly ConfigurationFactory $configurationFactory,
        private readonly DataLoaderFactory $dataLoaderFactory,
        private readonly OperationManager $operationManager,
        private readonly ProgressIndicator $progressIndicator,
        private readonly DataSeeder $dataSeeder,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->addOption(
            'config',
            null,
            InputOption::VALUE_OPTIONAL,
            'Path to configuration file. Relative to project route or absolute path needs be given. Default: <projectPath>/seeder.yaml'
        );
        $this->addOption(
            'reset',
            null,
            InputOption::VALUE_NONE,
            'When option is set, all data from the target database and from configured FAL storages will gbe deleted.'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = new SymfonyStyle($input, $output);

        $configuration = $this->buildConfiguration($input);
        if ($configuration === null) {
            return self::FAILURE;
        }

        $continue = $this->flushDataIfRequested($input, $configuration);
        if (!$continue) {
            return self::FAILURE;
        }

        $data = $this->buildSeedingData($configuration);
        if ($data === null) {
            return self::FAILURE;
        }

        $this->progressIndicator->setProgressIndicator(new SymfonyProgressIndicator($this->output));
        $this->processSeedingData($data, $configuration);

        return Command::SUCCESS;
    }

    private function buildConfiguration(InputInterface $input): ?Configuration
    {
        $configPath = $input->getOption('config');
        if (empty($configPath)) {
            $configPath = Environment::getProjectPath() . '/' . self::DEFAULT_CONFIG_FILENAME;
        }

        try {
            return $this->configurationFactory->buildFromConfigFile($configPath);
        } catch (ConfigurationPropertyException | MissingConfigurationFileException $e) {
            $this->output->error($e->getMessage());
        }

        return null;
    }

    private function flushDataIfRequested(InputInterface $input, Configuration $configuration): bool
    {
        if ($input->getOption('reset')) {
            if ($input->isInteractive()) {
                $this->output->warning([
                    'Executing this action will destroy all data in the database and replaces them with seeded data.',
                    'Do you want to continue?',
                ]);

                $shouldContinue = $this->output->confirm('Do you want to continue? (y/n) ', false);
                if (!$shouldContinue) {
                    return false;
                }
            }

            $this->resetDatabase();
            $this->cleanupStorages($configuration);
        }

        return true;
    }

    private function buildSeedingData(Configuration $configuration): ?SeedingData
    {
        try {
            $dataLoader = $this->dataLoaderFactory->create($configuration);
        } catch (ConfigurationPropertyException $e) {
            $this->output->error($e->getMessage());
            return null;
        }

        try {
            return $dataLoader->load($configuration->getDataLoader()->getOptions());
        } catch (ConfigurationPropertyException $e) {
            $this->output->error($e->getMessage());
            return null;
        }
    }

    private function processSeedingData(SeedingData $data, Configuration $configuration): void
    {
        $this->operationManager->runBeforeOperations($configuration, $data->getVariables());

        try {
            $rootNode = $this->dataSeeder->seed($data, $configuration);
        } catch (ConfigurationPropertyException $e) {
            $this->output->error($e->getMessage());
            return;
        } catch (IncompleteNodePrecessingException $e) {
            $this->output->error('Processing all data failed. Failed nodes:');
            foreach ($e->getUnprocessedNodes() as $node) {
                $table = $this->output->createTable();
                $table->setHeaderTitle($node->getRecordType() . ':' . $node->getIdentifier());
                $table->setHeaders(['Property name', 'Property value']);
                foreach ($node->getProperties() as $property) {
                    $this->addPropertyAsTableRow($table, $property);
                }
                $table->render();
                $this->output->newLine();
            }
            $this->output->error(implode(' ', [
                'Processing all data failed.',
                'Check the failed records for references to other records.',
                'Maybe an adjustment of the record type ordering set in the configuration can help.',
            ]));
            return;
        } catch (MissingParentException $e) {
            $this->output->error('Unable to build parent node for record. Check pid property value.');
            $table = $this->output->createTable();
            $table->setHeaders(['Property name', 'Property value']);
            foreach ($e->getNodeProperties() as $nodeProperty) {
                $this->addPropertyAsTableRow($table, $nodeProperty);
            }
            $table->render();
            return;
        }
        $this->output->newLine(2);
        $this->output->success('Successful imported all data');

        $this->operationManager->runAfterOperations($configuration, $data->getVariables(), $rootNode);
    }

    private function addPropertyAsTableRow(Table $table, Property $property): void
    {
        $propertyValue = $property->getValue();
        if (is_array($propertyValue)) {
            $propertyValue = '[array]';
        } elseif (is_object($propertyValue)) {
            $propertyValue = '[object]';
        }
        if (is_string($propertyValue)) {
            // Remove linebreaks for better readability of the table.
            $propertyValue = str_replace("\n", '', $propertyValue);
            if (strlen($propertyValue) > 50) {
                $propertyValue = substr($propertyValue, 0, 50) . '...';
            }
        }
        $table->addRow([$property->getName(), $propertyValue]);
    }

    private function resetDatabase(): void
    {
        $isVerbose = $this->output->isVerbose();
        $output = new ConsoleOutput();

        if (!$isVerbose) {
            // Suppress command output
            $output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
        }

        $databaseFlushCommand = new ArrayInput([
            'command' => 'database:flush',
            '--delete-tables' => true,
        ]);
        $databaseFlushCommand->setInteractive(false);
        $this->getApplication()?->doRun($databaseFlushCommand, $output);

        $databaseUpdateCommand = new ArrayInput([
            'command' => 'database:updateschema',
        ]);
        $databaseUpdateCommand->setInteractive(false);
        $this->getApplication()?->doRun($databaseUpdateCommand, $output);
    }

    private function cleanupStorages(Configuration $configuration): void
    {
        foreach ($configuration->getStorages() as $storage) {
            $path = $storage->getPath();
            if (!$storage->isAbsolute()) {
                $path = Environment::getPublicPath() . '/' . $path;
            }
            GeneralUtility::rmdir($path, true);
            GeneralUtility::mkdir($path);
        }
    }
}
