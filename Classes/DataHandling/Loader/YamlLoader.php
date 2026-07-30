<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\DataHandling\Loader;

use KM2\DataSeeder\Attribute\DataLoader;
use KM2\DataSeeder\Configuration\ConfigurationPropertyException;
use KM2\DataSeeder\DataHandling\Exception\MissingDataPathException;
use KM2\DataSeeder\DataHandling\SeedingData;
use KM2\DataSeeder\DataHandling\Variable\VariableCollection;
use Symfony\Component\Finder\Finder;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * @internal
 */
#[DataLoader('yaml')]
readonly class YamlLoader implements DataLoaderInterface
{
    public function __construct(private YamlFileLoader $fileLoader)
    {
    }

    /**
     * @throws MissingDataPathException
     * @throws ConfigurationPropertyException
     */
    public function load(array $options = []): SeedingData
    {
        $path = $options['path'] ?? '';
        if (empty($path)) {
            throw new ConfigurationPropertyException(
                'Missing or empty option "path" for yaml data loader given. Please check configuration file.',
                1783261684
            );
        }

        $data = match($options['type'] ?? 'folder') {
            'file' => $this->collectFromFile($path),
            'folder' => $this->collectFromPath($path),
            default => throw new ConfigurationPropertyException(
                sprintf('Invalid value "%s" for option "type" for yaml data loader given. Allowed values are [file,folder].', $options['type']),
                1783261824
            )
        };

        $variables = new VariableCollection();
        if (isset($data['_variables'])) {
            $variables = $this->buildVariableCollection($data['_variables']);
            unset($data['_variables']);
        }

        return new SeedingData($data, $variables);
    }

    /**
     * @return array<string, mixed>
     * @throws ConfigurationPropertyException
     */
    private function collectFromFile(string $filePath): array
    {
        $absoluteFilePath = $this->buildAbsoluteDataPath($filePath);
        $this->checkIfFileExists($absoluteFilePath);

        return $this->fileLoader->load($absoluteFilePath);
    }

    /**
     * @return array<string, mixed>
     * @throws ConfigurationPropertyException
     */
    private function collectFromPath(string $path): array
    {
        $data = [];
        $absolutePath = $this->buildAbsoluteDataPath($path);
        $this->checkIfFolderExists($absolutePath);

        // Collect all yaml files but no form yaml files
        $finder = new Finder();
        $finder->files()->name('*.yaml')->notName('*.form.yaml')->in($absolutePath)->depth(0)->sortByName();
        foreach ($finder as $file) {
            $data = array_merge_recursive(
                $data,
                $this->collectFromFile($file->getRealPath()),
            );
        }

        // Collect folders - they need to be loaded after the files in order to make references to parent page work.
        $finder = new Finder();
        $finder->directories()->in($absolutePath)->depth(0)->sortByName();
        foreach ($finder as $folder) {
            $data = array_merge_recursive(
                $data,
                $this->collectFromPath($folder->getRealPath())
            );
        }

        return $data;
    }

    /**
     * Variable collection is shared and is initialized here.
     *
     * @param array<string, mixed> $variables
     * @throws ConfigurationPropertyException
     */
    private function buildVariableCollection(array $variables): VariableCollection
    {
        $variableCollection = new VariableCollection();

        foreach ($variables as $variableName => $variableValue) {
            if (!is_string($variableValue) && !is_int($variableValue) && !is_float($variableValue) && !is_bool($variableValue)) {
                throw new ConfigurationPropertyException(
                    sprintf('Invalid value given for variable "%s". Values need to be of type string, int, float or bool.', $variableName),
                    1783266718
                );
            }

            if (preg_match('/^[a-zA-Z0-9_\-]+$/', $variableName) === false) {
                throw new ConfigurationPropertyException(
                    sprintf('Invalid variable name "%s" given. Variable names must only contain [a-z,A-Z,0-9,_,-].', $variableName),
                    1783266906
                );
            }

            $variableCollection->add($variableName, $variableValue);
        }

        return $variableCollection;
    }

    private function buildAbsoluteDataPath(string $path): string
    {
        $absolutePath = $path;
        if (PathUtility::isExtensionPath($path)) {
            $absolutePath = GeneralUtility::getFileAbsFileName($path);
        } elseif (!PathUtility::isAbsolutePath($path)) {
            $absolutePath = Environment::getProjectPath() . '/' . $path;
        }

        return $absolutePath;
    }

    /**
     * @throws ConfigurationPropertyException
     */
    private function checkIfFolderExists(string $folderPath): void
    {
        if (!is_dir($folderPath)) {
            throw new ConfigurationPropertyException(
                sprintf('Data directory "%s" does not exist or is not readable. Please check configuration or filesystem.', $folderPath),
                1736352330
            );
        }
    }

    /**
     * @throws ConfigurationPropertyException
     */
    private function checkIfFileExists(string $filePath): void
    {
        if (!is_file($filePath)) {
            throw new ConfigurationPropertyException(
                sprintf('Data directory "%s" does not exist or is not readable. Please check configuration or filesystem.', $filePath),
                1736236969
            );
        }
    }
}
