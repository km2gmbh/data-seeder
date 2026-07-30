<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\DataHandling\Processors;

use KM2\DataSeeder\Attribute\SeedingProcessor;
use KM2\DataSeeder\DataHandling\CombinedIdentifier;
use KM2\DataSeeder\DataHandling\Exception\RecordNotFoundException;
use KM2\DataSeeder\DataHandling\Node\NodeInterface;
use KM2\DataSeeder\DataHandling\NodeResolver;
use KM2\DataSeeder\DataHandling\Property\Converter\PropertyConversionException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Form\Slot\FilePersistenceSlot;

/**
 * @internal
 */
#[SeedingProcessor(identifier: 'sys_file', before: ['records'], after: ['pages'])]
class FilesProcessor extends AbstractRecordProcessor
{
    use \TYPO3\CMS\Core\Resource\ResourceInstructionTrait;

    /**
     * @var array<string, ResourceStorage>
     */
    private static array $storages = [];

    protected array $requiredProperties = [
        'source',
        'storage',
        'folder',
    ];

    public function __construct(
        private readonly StorageRepository $storageRepository,
        private readonly NodeResolver $nodeResolver,
    ) {
    }

    public function canProcess(NodeInterface $node): bool
    {
        return $node->getRecordType() === 'sys_file';
    }

    /**
     * @throws RecordNotFoundException
     */
    #[\Override]
    protected function processSingleRecord(NodeInterface $node): void
    {
        try {
            $this->convertProperties($node);
        } catch (PropertyConversionException) {
            return;
        }

        $properties = $node->getProperties();
        $sourceFile = $this->getFileAbsFileName((string)$properties->get('source')->getValue());
        $storage = $this->createStorageIfNotExists((string)$properties->get('storage')->getValue());
        $targetFileName = basename($sourceFile);
        if ($properties->has('targetFileName') && $properties->get('targetFileName')->getValue()) {
            $targetFileName = $properties->get('targetFileName')->getValue();
        }

        try {
            $targetFolder = $storage->getFolder($properties->get('folder')->getValue());
        } catch (FolderDoesNotExistException) {
            $targetFolder = $storage->createFolder($properties->get('folder')->getValue());
        }
        // Allow invocation if form yaml file should be persisted
        $targetFilePath = $targetFolder->getCombinedIdentifier() . $targetFileName;
        if (str_ends_with($sourceFile, 'form.yaml')) {
            $filePersistenceSlot = GeneralUtility::makeInstance(FilePersistenceSlot::class);
            $contentSignature = $filePersistenceSlot->getContentSignature(file_get_contents($sourceFile) ?: '');
            $filePersistenceSlot->allowInvocation('fileAdd', $targetFilePath, $contentSignature);
        }

        $this->skipResourceConsistencyCheckForCommands($storage, $sourceFile, $targetFileName);

        $file = $storage->addFile(
            $sourceFile,
            $targetFolder,
            $targetFileName,
            DuplicationBehavior::RENAME,
            false
        );

        $node->getProperties()->add('uid', $file->getUid());

        /** @var Indexer $indexer */
        $indexer = GeneralUtility::makeInstance(Indexer::class, $storage);
        $indexer->extractMetaData($file);

        $this->applyIdentifierToRecord($file->getUid(), $node->getRecordType(), $node->getIdentifier());

        $node->setProcessed(true);
    }

    /**
     * @param string $storageIdentifier
     * @throws RecordNotFoundException
     */
    protected function createStorageIfNotExists(string $storageIdentifier): ResourceStorage
    {
        if (empty($storageIdentifier)) {
            throw new \RuntimeException('Storage identifier cannot be empty.', 1783252106);
        }

        if (isset(self::$storages[$storageIdentifier])) {
            return self::$storages[$storageIdentifier];
        }

        $storageCombinedIdentifier = new CombinedIdentifier('sys_file_storage', $storageIdentifier);
        $storageNode = $this->nodeResolver->resolve($storageCombinedIdentifier);
        $storageUid = (int)$storageNode->getProperties()->get('uid')->getValue();

        $storage = $this->storageRepository->findByUid($storageUid);
        if ($storage === null) {
            throw new RecordNotFoundException(
                sprintf('No storage record with UID %d found (identifier: %s).', $storageUid, $storageIdentifier),
                1736329717
            );
        }

        self::$storages[$storageIdentifier] = $storage;

        return $storage;
    }

    /**
     * This method is based on GeneralUtility::getFileAbsFileName()
     * For relative paths, it resolves to projectPath instead of publicPath.
     */
    private function getFileAbsFileName(string $fileName): string
    {
        if ($fileName === '') {
            return '';
        }
        $checkForBackPath = fn (string $fileName): string => $fileName !== '' && GeneralUtility::validPathStr($fileName) ? $fileName : '';

        // Extension "EXT:" path resolving.
        if (PathUtility::isExtensionPath($fileName)) {
            $fileName = ExtensionManagementUtility::resolvePackagePath($fileName);

            return $checkForBackPath($fileName);
        }

        // Absolute path, but set to blank if not inside allowed directories.
        if (PathUtility::isAbsolutePath($fileName)) {
            if (GeneralUtility::isAllowedAbsPath($fileName)) {
                return $checkForBackPath($fileName);
            }
            return '';
        }

        // Relative path. Prepend with the public web folder.
        $fileName = Environment::getProjectPath() . '/' . $fileName;
        return $checkForBackPath($fileName);
    }

    private function applyIdentifierToRecord(int $recordUid, string $table, string $identifier): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
        $connection->update(
            $table,
            [
                'seed_identifier' => $identifier,
            ],
            [
                'uid' => $recordUid,
            ]
        );
    }
}
