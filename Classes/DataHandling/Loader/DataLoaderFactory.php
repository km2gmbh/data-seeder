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
use KM2\DataSeeder\Configuration\DataTransferObject\Configuration;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

final readonly class DataLoaderFactory
{
    /**
     * @param ServiceLocator<DataLoaderInterface> $dataLoaders
     */
    public function __construct(
        #[AutowireLocator(services: DataLoader::TAG_NAME, indexAttribute: 'identifier')]
        private ServiceLocator $dataLoaders
    ) {
    }

    /**
     * @throws ConfigurationPropertyException
     */
    public function create(Configuration $configuration): DataLoaderInterface
    {
        $loaderIdentifier = $configuration->getDataLoader()->getType();
        if (!$this->dataLoaders->has($loaderIdentifier)) {
            $availableLoaders = array_keys($this->dataLoaders->getProvidedServices());
            throw new ConfigurationPropertyException(
                sprintf('Invalid loader "%s" in configuration given. Available loaders: [%s]', $loaderIdentifier, implode(',', $availableLoaders)),
                1783262393
            );
        }

        return $this->dataLoaders->get($loaderIdentifier);
    }
}
