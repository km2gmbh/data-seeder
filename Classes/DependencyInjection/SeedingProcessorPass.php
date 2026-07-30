<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\DependencyInjection;

use KM2\DataSeeder\DataHandling\ProcessorManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final readonly class SeedingProcessorPass implements CompilerPassInterface
{
    public function __construct(private string $tagName)
    {
    }

    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(ProcessorManager::class)) {
            return;
        }

        $processorManagerDefinition = $container->getDefinition(ProcessorManager::class);
        foreach ($container->findTaggedServiceIds($this->tagName) as $serviceName => $tags) {
            $service = $container->findDefinition($serviceName);
            $service->setPublic(true);
            foreach ($tags as $attributes) {
                $processorManagerDefinition->addMethodCall('addProcessor', [
                    $attributes['identifier'],
                    $serviceName,
                    $attributes['before'],
                    $attributes['after'],
                ]);
            }
        }
    }
}
