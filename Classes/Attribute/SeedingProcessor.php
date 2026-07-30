<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class SeedingProcessor
{
    public const string TAG_NAME = 'data_seeder.processor';

    /**
     * @param list<string> $before
     * @param list<string> $after
     */
    public function __construct(
        public string $identifier,
        public array $before = [],
        public array $after = [],
    ) {
    }
}
