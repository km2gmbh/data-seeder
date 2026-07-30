<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\DependencyInjection;

use KM2\DataSeeder\DataHandling\Property\PropertyConverter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final readonly class PropertyConverterPass implements CompilerPassInterface
{
    public function __construct(private string $tagName)
    {
    }

    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(PropertyConverter::class)) {
            return;
        }

        $processorManagerDefinition = $container->getDefinition(PropertyConverter::class);
        foreach ($container->findTaggedServiceIds($this->tagName) as $serviceName => $tags) {
            $service = $container->findDefinition($serviceName);
            $service->setPublic(true);
            foreach ($tags as $attributes) {
                $processorManagerDefinition->addMethodCall('addConverter', [
                    $attributes['identifier'],
                    $serviceName,
                    $attributes['before'],
                    $attributes['after'],
                ]);
            }
        }
    }
}
