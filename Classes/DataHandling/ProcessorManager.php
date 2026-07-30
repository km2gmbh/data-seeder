<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\DataHandling;

use KM2\DataSeeder\Configuration\DataTransferObject\Configuration;
use KM2\DataSeeder\DataHandling\Node\NodeInterface;
use KM2\DataSeeder\DataHandling\Processors\ProcessorInterface;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class ProcessorManager
{
    /**
     * @var array<string, array{target: class-string, before: list<string>, after: list<string>}>
     */
    private static array $unorderedProcessors = [];

    /**
     * @var list<ProcessorInterface>
     */
    private static array $orderedProcessors = [];

    public function __construct(
        private readonly DependencyOrderingService $dependencyOrderingService,
        private readonly ProgressIndicator $progressIndicator,
    ) {
    }

    /**
     * @param class-string $target
     * @param list<string> $before
     * @param list<string> $after
     */
    public function addProcessor(string $identifier, string $target, array $before, array $after): void
    {
        self::$unorderedProcessors[$identifier] = [
            'target' => $target,
            'before' => $before,
            'after' => $after,
        ];
    }

    public function processNode(NodeInterface $node, Configuration $configuration, RuntimeValues $runtimeValues): bool
    {
        $isProcessed = $node->isProcessed();
        $processors = $this->getOrdererProcessors();
        if (!$isProcessed) {
            $this->progressIndicator->advance();
            foreach ($processors as $processor) {
                if ($node->isProcessed()) {
                    break;
                }

                $processor->setConfiguration($configuration);
                $processor->setRuntimeValues($runtimeValues);
                if (!$processor->canProcess($node)) {
                    continue;
                }

                $processor->process($node);
                $isProcessed = $node->isProcessed();
                break;
            }
        }

        foreach ($node->getChildNodes()->getAll() as $groupedChildNodes) {
            foreach ($groupedChildNodes as $childNode) {
                $isProcessed = $this->processNode($childNode, $configuration, $runtimeValues) && $isProcessed;
            }
        }

        return $isProcessed;
    }

    /**
     * @return list<ProcessorInterface>
     */
    private function getOrdererProcessors(): array
    {
        if (self::$orderedProcessors !== []) {
            return self::$orderedProcessors;
        }

        $processors = [];
        $orderedProcessors = $this->dependencyOrderingService->orderByDependencies(self::$unorderedProcessors);
        foreach ($orderedProcessors as $processorConfiguration) {
            // @phpstan-ignore-next-line
            $processor = GeneralUtility::makeInstance($processorConfiguration['target']);

            if (!$processor instanceof ProcessorInterface) {
                throw new \RuntimeException(
                    sprintf('Registered processor needs to implement %s.', ProcessorInterface::class),
                    1782449944
                );
            }

            $processors[] = $processor;
        }

        self::$orderedProcessors = $processors;

        return $processors;
    }
}
