<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\Event\Listener;

use KM2\DataSeeder\DataHandling\Exception\MissingIdentifierException;
use KM2\DataSeeder\Event\BeforeNodeBuildingEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class OrderPagesByDependency
{
    public function __construct(private DependencyOrderingService $dependencyOrderingService)
    {
    }

    #[AsEventListener('km2.data-seeder.order-pages-by-dependency')]
    public function __invoke(BeforeNodeBuildingEvent $event): void
    {
        if ($event->getRecordType() !== 'pages') {
            return;
        }

        $preparedRecords = $this->prepareForDependencyOrdering($event);
        $orderedRecords = $this->dependencyOrderingService->orderByDependencies($preparedRecords, '_dependants', '_dependsOn');
        foreach ($orderedRecords as &$record) {
            if (isset($record['_dependants'])) {
                unset($record['_dependants']);
            }
            if (isset($record['_dependsOn'])) {
                unset($record['_dependsOn']);
            }
        }

        $event->setRecords(array_values($orderedRecords));
    }

    /**
     * @return array<string, array<string, mixed>>
     * @throws MissingIdentifierException
     */
    private function prepareForDependencyOrdering(BeforeNodeBuildingEvent $event): array
    {
        $preparedRecords = [];
        foreach ($event->getRecords() as &$record) {
            $identifier = $record['identifier'] ?? null;
            if (empty($identifier)) {
                throw new MissingIdentifierException(
                    sprintf('Missing identifier for record of type %s.', $event->getRecordType()),
                    1783155246
                );
            }

            $parentCombinedIdentifier = $record['pid'] ?? null;
            if (!empty($parentCombinedIdentifier)) {
                [,$parentIdentifier] = GeneralUtility::trimExplode(':', $parentCombinedIdentifier);
                $record['_dependsOn'] = [
                    $parentIdentifier
                ];
            }

            $preparedRecords[$identifier] = $record;
        }

        return $preparedRecords;
    }
}
