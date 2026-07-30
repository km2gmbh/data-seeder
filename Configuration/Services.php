<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder;

use KM2\DataSeeder\Attribute\DataLoader;
use KM2\DataSeeder\Attribute\Operation;
use KM2\DataSeeder\Attribute\PropertyConverter;
use KM2\DataSeeder\Attribute\SeedingProcessor;
use KM2\DataSeeder\DependencyInjection\DataOperationPass;
use KM2\DataSeeder\DependencyInjection\PropertyConverterPass;
use KM2\DataSeeder\DependencyInjection\SeedingProcessorPass;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container, ContainerBuilder $containerBuilder) {
    $containerBuilder->registerAttributeForAutoconfiguration(
        SeedingProcessor::class,
        static function (ChildDefinition $definition, SeedingProcessor $attribute) {
            $definition->addTag(SeedingProcessor::TAG_NAME, [
                'identifier' => $attribute->identifier,
                'before' => $attribute->before,
                'after' => $attribute->after,
            ]);
        }
    );

    $containerBuilder->registerAttributeForAutoconfiguration(
        PropertyConverter::class,
        static function (ChildDefinition $definition, PropertyConverter $attribute) {
            $definition->addTag(PropertyConverter::TAG_NAME, [
                'identifier' => $attribute->identifier,
                'before' => $attribute->before,
                'after' => $attribute->after,
            ]);
        }
    );

    $containerBuilder->registerAttributeForAutoconfiguration(
        Operation::class,
        static function (ChildDefinition $definition, Operation $attribute) {
            $definition->addTag(Operation::TAG_NAME, [
                'identifier' => $attribute->identifier,
                'beforeDataSeeding' => $attribute->beforeDataSeeding,
            ]);
        }
    );

    $containerBuilder->registerAttributeForAutoconfiguration(
        DataLoader::class,
        static function (ChildDefinition $definition, DataLoader $attribute) {
            $definition->addTag(DataLoader::TAG_NAME, ['identifier' => $attribute->identifier]);
        }
    );

    $containerBuilder->addCompilerPass(new SeedingProcessorPass(SeedingProcessor::TAG_NAME));
    $containerBuilder->addCompilerPass(new PropertyConverterPass(PropertyConverter::TAG_NAME));
    $containerBuilder->addCompilerPass(new DataOperationPass(Operation::TAG_NAME));
};
