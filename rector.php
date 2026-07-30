<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/Classes',
        __DIR__ . '/Configuration',
    ]);

    $rectorConfig->phpVersion(\Rector\ValueObject\PhpVersion::PHP_84);
    $rectorConfig->sets([
        \Rector\Set\ValueObject\LevelSetList::UP_TO_PHP_84,
        \Ssch\TYPO3Rector\Set\Typo3LevelSetList::UP_TO_TYPO3_13,
    ]);

    $rectorConfig->importNames(false);
};
