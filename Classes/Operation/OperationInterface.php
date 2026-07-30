<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\Operation;

use KM2\DataSeeder\Configuration\DataTransferObject\Configuration;
use KM2\DataSeeder\DataHandling\Node\RootNode;
use KM2\DataSeeder\DataHandling\Variable\VariableCollection;

interface OperationInterface
{
    /**
     * @param RootNode|null $rootNode RootNode is only set when operation is run after data seeding.
     */
    public function run(Configuration $configuration, VariableCollection $variables, ?RootNode $rootNode): void;
}
