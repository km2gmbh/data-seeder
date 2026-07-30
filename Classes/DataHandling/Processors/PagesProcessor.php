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
use KM2\DataSeeder\DataHandling\Node\NodeHasParentNodeInterface;
use KM2\DataSeeder\DataHandling\Node\NodeInterface;
use KM2\DataSeeder\DataHandling\Property\Converter\PropertyConversionException;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * @internal
 */
#[SeedingProcessor(identifier: 'pages')]
class PagesProcessor extends RecordProcessor
{
    private const string SLUG_FIELD = 'slug';

    protected array $requiredProperties = [
        'pid',
        'title',
        'sorting',
    ];

    #[\Override]
    public function canProcess(NodeInterface $node): bool
    {
        return $node->getRecordType() === 'pages';
    }

    #[\Override]
    protected function processSingleRecord(NodeInterface $node): void
    {
        try {
            $this->convertProperties($node);
        } catch (PropertyConversionException) {
            // If some properties cannot be converted, create a minimal database record for current node.
            // If other node properties depends on this node, they can be resolved.
            // Node will not be marked as processed. The existing record will get updated next run.
            $this->createSkeletonRecord($node);
            return;
        }

        if (!$node->getProperties()->has(self::SLUG_FIELD)) {
            $node->getProperties()->add(self::SLUG_FIELD, $this->buildSlug($node));
        }

        if ($this->recordExists($node)) {
            $this->updateRecord($node);
        } else {
            $this->createRecord($node);
        }

        $node->setProcessed(true);
    }

    private function buildSlug(NodeInterface $node): string
    {
        $slugHelper = GeneralUtility::makeInstance(
            SlugHelper::class,
            $node->getRecordType(),
            self::SLUG_FIELD,
            $GLOBALS['TCA'][$node->getRecordType()]['columns'][self::SLUG_FIELD]['config'] ?? []
        );

        $properties = $node->getProperties()->toArray();
        $pid = $properties['pid'] ?? null;
        if (!MathUtility::canBeInterpretedAsInteger($pid)) {
            if (!$node instanceof NodeHasParentNodeInterface) {
                return $node->getIdentifier();
            }
            if (!$node->getParentNode()->getProperties()->has('uid')) {
                return $node->getIdentifier();
            }
            $pid = (int)$node->getParentNode()->getProperties()->get('uid')->getValue();
        }

        return $slugHelper->generate($properties, (int)$pid);
    }
}
