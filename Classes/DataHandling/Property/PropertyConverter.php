<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\DataHandling\Property;

use KM2\DataSeeder\Configuration\DataTransferObject\Configuration;
use KM2\DataSeeder\DataHandling\Node\NodeInterface;
use KM2\DataSeeder\DataHandling\Property\Converter\PropertyConversionException;
use KM2\DataSeeder\DataHandling\Property\Converter\PropertyConverterInterface;
use KM2\DataSeeder\DataHandling\RuntimeValues;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[Autoconfigure(public: true, shared: true)]
final class PropertyConverter
{
    private Configuration $configuration;

    protected RuntimeValues $runtimeValues;

    /**
     * @var array<string, array{target: class-string, before: list<string>, after: list<string>}>
     */
    private static array $unorderedPropertyConverters = [];

    /**
     * @var list<PropertyConverterInterface>
     */
    private static array $orderedPropertyConverters = [];

    public function __construct(private readonly DependencyOrderingService $dependencyOrderingService)
    {
    }

    public function setConfiguration(Configuration $configuration): void
    {
        $this->configuration = $configuration;
    }

    public function setRuntimeValues(RuntimeValues $runtimeValues): void
    {
        $this->runtimeValues = $runtimeValues;
    }

    /**
     * @param class-string $target
     * @param list<string> $before
     * @param list<string> $after
     */
    public function addConverter(string $identifier, string $target, array $before, array $after): void
    {
        self::$unorderedPropertyConverters[$identifier] = [
            'target' => $target,
            'before' => $before,
            'after' => $after,
        ];
    }

    /**
     * @throws PropertyConversionException
     */
    public function convertPropertiesForNode(NodeInterface $node): void
    {
        $node->getProperties()->rewind();
        foreach ($node->getProperties() as $property) {
            $this->convertProperty($property, $node);
        }
    }

    /**
     * @throws PropertyConversionException
     */
    private function convertProperty(Property $property, NodeInterface $node): void
    {
        foreach ($this->getOrdererPropertyConverters() as $propertyConverter) {
            $converted = $propertyConverter->convert($property, $node);
            if ($converted) {
                break;
            }
        }
    }

    /**
     * @return list<PropertyConverterInterface>
     */
    public function getOrdererPropertyConverters(): array
    {
        if (self::$orderedPropertyConverters !== []) {
            return self::$orderedPropertyConverters;
        }

        $propertyConverters = [];
        $orderedPropertyConverters = $this->dependencyOrderingService->orderByDependencies(self::$unorderedPropertyConverters);
        foreach ($orderedPropertyConverters as $propertyConverterConfiguration) {
            // @phpstan-ignore-next-line
            $propertyConverter = GeneralUtility::makeInstance($propertyConverterConfiguration['target']);
            if (!$propertyConverter instanceof PropertyConverterInterface) {
                throw new \RuntimeException(
                    sprintf('Registered property converter needs to implement %s.', PropertyConverterInterface::class),
                    1783179222
                );
            }
            $propertyConverter->setConfiguration($this->configuration);
            $propertyConverter->setRuntimeValues($this->runtimeValues);

            $propertyConverters[] = $propertyConverter;
        }

        self::$orderedPropertyConverters = $propertyConverters;

        return self::$orderedPropertyConverters;
    }
}
