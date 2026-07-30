<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\Event;

final class BeforeNodeBuildingEvent
{
    /**
     * @param list<array<string, mixed>> $records
     */
    public function __construct(
        private readonly string $recordType,
        private array $records,
    ) {
    }

    public function getRecordType(): string
    {
        return $this->recordType;
    }

    /**
     * @param list<array<string, mixed>> $records
     */
    public function setRecords(array $records): void
    {
        $this->records = $records;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getRecords(): array
    {
        return $this->records;
    }
}
