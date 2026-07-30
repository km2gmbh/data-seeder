<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\Repository;

use KM2\DataSeeder\Configuration\DataTransferObject\Storage;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class StorageRepository
{
    public function __construct(private ConnectionPool $connectionPool, private FlexFormTools $flexFormTools)
    {
    }

    public function hasResourceStorage(string $identifier): bool
    {
        $connection = $this->connectionPool->getConnectionForTable('sys_file_storage');

        return $connection->count(
            'uid',
            'sys_file_storage',
            [
                'seed_identifier' => $identifier,
            ]
        ) > 0;
    }

    /**
     * @see \TYPO3\CMS\Core\Resource\StorageRepository::createLocalStorage()
     * Extends the default method by allow setting baseUri
     *
     * @param Storage $storage
     * @return int
     */
    public function createLocalResourceStorage(Storage $storage, string $description = ''): int
    {
        $name = $storage->getIdentifier();
        $basePath = $storage->getPath();
        $pathType = $storage->isAbsolute() ? 'absolute' : 'relative';
        $context = GeneralUtility::makeInstance(Context::class);

        $caseSensitive = $this->testCaseSensitivity($pathType === 'relative' ? Environment::getPublicPath() . '/' . $basePath : $basePath);
        // create the FlexForm for the driver configuration
        $flexFormData = [
            'data' => [
                'sDEF' => [
                    'lDEF' => [
                        'basePath' => ['vDEF' => rtrim($basePath, '/') . '/'],
                        'pathType' => ['vDEF' => $pathType],
                        'baseUri' => ['vDEF' => (string)$storage->getBaseUri()],
                        'caseSensitive' => ['vDEF' => $caseSensitive],
                    ],
                ],
            ],
        ];
        $flexFormXml = $this->flexFormTools->flexArray2Xml($flexFormData);
        // create the record
        $field_values = [
            'pid' => 0,
            'tstamp' => $context->getPropertyFromAspect('date', 'timestamp'),
            'crdate' => $context->getPropertyFromAspect('date', 'timestamp'),
            'name' => $name,
            'description' => $description,
            'driver' => 'Local',
            'configuration' => $flexFormXml,
            'is_online' => 1,
            'auto_extract_metadata' => 1,
            'is_browsable' => 1,
            'is_public' => 1,
            'is_writable' => 1,
            'is_default' => $storage->isDefault() ? 1 : 0,
            'seed_identifier' => $storage->getIdentifier(),
        ];
        $dbConnection = $this->connectionPool->getConnectionForTable('sys_file_storage');
        $dbConnection->insert('sys_file_storage', $field_values);

        return (int)$dbConnection->lastInsertId();
    }

    public function getResourceStorageUid(string $identifier): ?int
    {
        $connection = $this->connectionPool->getConnectionForTable('sys_file_storage');

        $storageUid = $connection->select(
            ['uid'],
            'sys_file_storage',
            [
                'seed_identifier' => $identifier,
            ]
        )->fetchOne();

        return $storageUid === false ? null : (int)$storageUid;
    }

    /**
     * @see \TYPO3\CMS\Core\Resource\StorageRepository::testCaseSensitivity()
     * @param string $absolutePath
     * @return bool
     */
    protected function testCaseSensitivity(string $absolutePath): bool
    {
        $caseSensitive = true;
        $path = rtrim($absolutePath, '/') . '/aAbB';
        $testFileExists = @file_exists($path);

        // create test file
        if (!$testFileExists) {
            touch($path);
        }

        // do the actual sensitivity check
        if (@file_exists(strtoupper($path)) && @file_exists(strtolower($path))) {
            $caseSensitive = false;
        }

        // clean filesystem
        if (!$testFileExists) {
            unlink($path);
        }

        return $caseSensitive;
    }
}
