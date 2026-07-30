<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\Configuration\DataTransferObject;

final readonly class Configuration
{
    /**
     * @param array<string, Storage> $storages
     * @param array<string, array{after?: list<string>, before?: list<string>}> $ordering
     * @param array<string, array<string, mixed>> $operationOptions
     */
    public function __construct(
        private DataLoader $dataLoader,
        private array $storages,
        private array $ordering = [],
        private array $operationOptions = [],
    ) {
    }

    public function getDataLoader(): DataLoader
    {
        return $this->dataLoader;
    }

    /**
     * @return array<string, Storage>
     */
    public function getStorages(): array
    {
        return $this->storages;
    }

    /**
     * @return array<string, array{after?: list<string>, before?: list<string>}>
     */
    public function getOrdering(): array
    {
        return $this->ordering;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOperationOptions(string $operationIdentifier): array
    {
        return $this->operationOptions[$operationIdentifier] ?? [];
    }
}
