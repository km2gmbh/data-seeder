<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\DataHandling;

use KM2\DataSeeder\DataHandling\Variable\VariableCollection;

final readonly class SeedingData
{
    /**
     * @param array<string, list<array<string, mixed>>> $seedingData
     */
    public function __construct(private array $seedingData, private VariableCollection $variables)
    {
    }

    /**
     * Example return value:
     *  <code>
     *  [
     *      'pages' => [
     *          ['identifier' => 'test'],
     *          ['identifier' => 'test2'],
     *      ],
     *      'tt_content' => [
     *          ['identifier' => 'text-ce'],
     *      ],
     *  ]
     *  </code>
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public function getSeedingData(): array
    {
        return $this->seedingData;
    }

    public function getVariables(): VariableCollection
    {
        return $this->variables;
    }
}
