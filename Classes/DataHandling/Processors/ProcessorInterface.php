<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\DataHandling\Processors;

use KM2\DataSeeder\Configuration\DataTransferObject\Configuration;
use KM2\DataSeeder\DataHandling\Node\NodeInterface;
use KM2\DataSeeder\DataHandling\RuntimeValues;

interface ProcessorInterface
{
    public function canProcess(NodeInterface $node): bool;

    public function process(NodeInterface $node): void;

    public function setConfiguration(Configuration $configuration): void;

    public function setRuntimeValues(RuntimeValues $runtimeValues): void;
}
