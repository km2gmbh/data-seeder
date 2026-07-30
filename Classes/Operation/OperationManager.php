<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\Operation;

use KM2\DataSeeder\Configuration\DataTransferObject\Configuration;
use KM2\DataSeeder\DataHandling\Node\RootNode;
use KM2\DataSeeder\DataHandling\Variable\VariableCollection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class OperationManager
{
    /**
     * @var array<string, class-string<object>>
     */
    private static array $beforeOperations = [];

    /**
     * @var array<string, class-string>
     */
    private static array $afterOperations = [];

    /**
     * @param class-string<object> $target
     */
    public function addOperation(string $identifier, string $target, bool $beforeDataSeeding): void
    {
        if ($beforeDataSeeding) {
            self::$beforeOperations[$identifier] = $target;
        } else {
            self::$afterOperations[$identifier] = $target;
        }
    }

    public function runBeforeOperations(Configuration $configuration, VariableCollection $variables): void
    {
        $this->runOperations(self::$beforeOperations, $configuration, $variables);
    }

    public function runAfterOperations(Configuration $configuration, VariableCollection $variables, RootNode $rootNode): void
    {
        $this->runOperations(self::$afterOperations, $configuration, $variables, $rootNode);
    }

    /**
     * @param array<string, class-string<object>> $operations
     */
    private function runOperations(array $operations, Configuration $configuration, VariableCollection $variables, ?RootNode $rootNode = null): void
    {
        foreach ($operations as $className) {
            $operation = GeneralUtility::makeInstance($className);
            if (!$operation instanceof OperationInterface) {
                throw new \RuntimeException(
                    sprintf('Operation mus implement %s.', OperationInterface::class),
                    1783285786
                );
            }

            $operation->run($configuration, $variables, $rootNode);
        }
    }
}
