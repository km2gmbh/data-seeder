<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\Operation;

use KM2\DataSeeder\Attribute\Operation;
use KM2\DataSeeder\Configuration\DataTransferObject\Configuration;
use KM2\DataSeeder\DataHandling\CombinedIdentifier;
use KM2\DataSeeder\DataHandling\Exception\NodeUnresolvableException;
use KM2\DataSeeder\DataHandling\Node\InvalidPropertyException;
use KM2\DataSeeder\DataHandling\Node\MissingParentException;
use KM2\DataSeeder\DataHandling\Node\RootNode;
use KM2\DataSeeder\DataHandling\NodeResolver;
use KM2\DataSeeder\DataHandling\Variable\VariableCollection;
use TYPO3\CMS\Core\Core\Environment;

/**
 * @internal
 */
#[Operation(self::IDENTIFIER)]
readonly class EnvFileGeneratorOperation implements OperationInterface
{
    public const string IDENTIFIER = 'envFileGenerator';

    public function __construct(private NodeResolver $nodeResolver)
    {
    }

    public function run(Configuration $configuration, VariableCollection $variables, ?RootNode $rootNode): void
    {
        $envFiles = $configuration->getOperationOptions(self::IDENTIFIER);
        if ($envFiles === []) {
            return;
        }

        foreach ($envFiles['envFiles'] ?? [] as $envFileConfiguration) {
            $this->generateEnvFile($envFileConfiguration);
        }
    }

    /**
     * @param array<string, mixed> $envFileConfiguration
     */
    private function generateEnvFile(array $envFileConfiguration): void
    {
        if (empty($envFileConfiguration['path']) || !is_string($envFileConfiguration['path'])) {
            throw new InvalidConfigurationException(
                sprintf('Missing or empty path for %s given.', self::IDENTIFIER),
                1783285070
            );
        }

        $envFilePath = Environment::getProjectPath() . '/' . ltrim($envFileConfiguration['path'], '/');
        $values = $this->generateFileContent($envFileConfiguration['values'] ?? []);

        file_put_contents($envFilePath, implode("\n", $values));
    }

    /**
     * @param array<string, mixed> $values
     * @return list<string>
     */
    private function generateFileContent(array $values): array
    {
        $fileContentLines = [];
        foreach ($values as $valueKey => $valueConfiguration) {
            if (is_string($valueConfiguration)) {
                $fileContentLines[] = sprintf('%s="%s"', $valueKey, $this->processValue($valueConfiguration));
                continue;
            }
            if (is_array($valueConfiguration)) {
                trigger_error(
                    'Using array notation for .env values is deprecated. Use combined identifier instead like {<recordType>:<recordIdentifier>:<recordField>}',
                    E_USER_DEPRECATED
                );
                $fileContentLines[] = sprintf('%s="%s"', $valueKey, $this->getValueFromConfiguration($valueKey, $valueConfiguration));
                continue;
            }

            throw new InvalidConfigurationException(
                sprintf('[%s] Value for key %s needs to be a string or array.', self::IDENTIFIER, $valueKey),
                1782414433
            );
        }

        return $fileContentLines;
    }

    private function processValue(string $value): string
    {
        if (!CombinedIdentifier::isValid($value)) {
            return $value;
        }

        $combinedIdentifier = CombinedIdentifier::fromString($value);
        try {
            $node = $this->nodeResolver->resolve($combinedIdentifier);
        } catch (NodeUnresolvableException | MissingParentException) {
            return $value;
        }

        try {
            return (string)$node->getProperties()->get($combinedIdentifier->getProperty())->getValue();
        } catch (InvalidPropertyException) {
            return $value;
        }
    }

    /**
     * @param array{identifier?: mixed, field?: mixed} $valueConfiguration
     */
    private function getValueFromConfiguration(string $key, array $valueConfiguration): ?string
    {
        $identifier = $valueConfiguration['identifier'] ?? null;
        $field = $valueConfiguration['field'] ?? null;

        if (empty($identifier) || !is_string($identifier)) {
            throw new InvalidConfigurationException(
                sprintf('Property identifier for key %s needs to be a string.', $key),
                1782413568
            );
        }
        if (empty($field) || !is_string($field)) {
            throw new InvalidConfigurationException(
                sprintf('Property field for key %s needs to be a string.', $key),
                1782413597
            );
        }

        try {
            $combinedIdentifier = CombinedIdentifier::fromString($identifier);
        } catch (\Throwable) {
            return null;
        }

        try {
            $node = $this->nodeResolver->resolve($combinedIdentifier);
        } catch (NodeUnresolvableException | MissingParentException) {
            return null;
        }

        try {
            return (string)$node->getProperties()->get($field)->getValue();
        } catch (InvalidPropertyException) {
            return null;
        }
    }
}
