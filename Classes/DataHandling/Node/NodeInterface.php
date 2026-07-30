<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\DataHandling\Node;

use KM2\DataSeeder\DataHandling\Property\PropertyCollection;

interface NodeInterface
{
    public function getIdentifier(): string;

    public function getRecordType(): string;

    public function isProcessed(): bool;

    public function setProcessed(bool $isProcessed): void;

    public function getChildNodes(): NodeCollection;

    public function getProperties(): PropertyCollection;
}
