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
final class Operation
{
    public const string TAG_NAME = 'data_seeder.operation';

    public function __construct(
        public string $identifier,
        public bool $beforeDataSeeding = false,
    ) {
    }
}
