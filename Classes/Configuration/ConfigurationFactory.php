<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\Configuration;

use KM2\DataSeeder\Configuration\DataTransferObject\Configuration;
use KM2\DataSeeder\Configuration\DataTransferObject\DataLoader;
use KM2\DataSeeder\Configuration\DataTransferObject\Storage;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

final readonly class ConfigurationFactory
{
    public function __construct(private YamlFileLoader $loader)
    {
    }

    /**
     * @throws MissingConfigurationFileException
     * @throws ConfigurationPropertyException
     */
    public function buildFromConfigFile(string $configPath): Configuration
    {
        if (PathUtility::isExtensionPath($configPath)) {
            $configPath = GeneralUtility::getFileAbsFileName($configPath);
        } elseif (!PathUtility::isAbsolutePath($configPath)) {
            $configPath = Environment::getProjectPath() . '/' . $configPath;
        }

        if (!is_file($configPath)) {
            throw new MissingConfigurationFileException(sprintf('No configuration found in path %s.', $configPath), 1736234013);
        }

        return $this->buildConfigurationObject($this->loader->load($configPath));
    }

    /**
     * @param array<string, mixed> $configurationData
     * @throws ConfigurationPropertyException
     */
    private function buildConfigurationObject(array $configurationData): Configuration
    {
        return new Configuration(
            dataLoader: $this->buildDataLoader($configurationData),
            storages: $this->buildStorages($configurationData),
            ordering: $configurationData['ordering'] ?? [],
            operationOptions: $configurationData['operations'] ?? []
        );
    }

    /**
     * @param array<string, mixed> $configurationData
     * @throws ConfigurationPropertyException
     */
    private function buildDataLoader(array $configurationData): DataLoader
    {
        if (empty($configurationData['data']['loader']) || !is_string($configurationData['data']['loader'])) {
            throw new ConfigurationPropertyException(
                'Configuration value data/loader needs to be a string.',
                1782412076
            );
        }

        if (isset($configurationData['data']['options']) && !is_array($configurationData['data']['options'])) {
            throw new ConfigurationPropertyException(
                'Configuration value data/options needs to be a array.',
                1782412808
            );
        }

        return new DataLoader($configurationData['data']['loader'], $configurationData['data']['options'] ?? []);
    }

    /**
     * @param array<string, mixed> $configurationData
     * @return array<string, Storage>
     * @throws ConfigurationPropertyException
     */
    private function buildStorages(array $configurationData): array
    {
        $storages = [];
        $defaultStorage = true;
        foreach ($configurationData['fal']['storages'] as $storageConfiguration) {
            $storageIdentifier = (string)($storageConfiguration['identifier'] ?? '');
            if (empty($storageIdentifier)) {
                throw new ConfigurationPropertyException(
                    'Storage identifier cannot be empty.',
                    1783368659
                );
            }

            $storagePath = (string)($storageConfiguration['storagePath'] ?? '');
            if (empty($storagePath)) {
                throw new ConfigurationPropertyException(
                    'Storage property "storagePath" cannot be empty.',
                    1783368689
                );
            }

            $isAbsolute = str_starts_with($storagePath, '/');
            $this->createStorageFolder($storagePath, $isAbsolute);

            $storages[$storageIdentifier] = new Storage(
                $storageIdentifier,
                $storagePath,
                $isAbsolute,
                $defaultStorage,
                (string)($storageConfiguration['baseUri'] ?? '')
            );
            $defaultStorage = false;
        }

        if (empty($storages)) {
            $storages['default'] = new Storage('default', 'fileadmin/', false, true);
        }

        return $storages;
    }

    /**
     * @throws ConfigurationPropertyException
     */
    private function createStorageFolder(string $storagePath, bool $isAbsolute): void
    {
        if (!$isAbsolute) {
            $storagePath = Environment::getPublicPath() . '/' . ltrim($storagePath, '/');
        }

        if (!is_dir($storagePath)) {
            GeneralUtility::mkdir_deep($storagePath);
        }

        if (!is_dir($storagePath)) {
            throw new ConfigurationPropertyException(
                sprintf('Target folder "%s" does not exist and could not be created.', $storagePath),
                1784669317
            );
        }
    }
}
