<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;
use Ssch\TYPO3Rector\Configuration\Typo3Option;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;
use Ssch\TYPO3Rector\Set\Typo3SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/../../Build',
        __DIR__ . '/../../Classes',
        __DIR__ . '/../../Configuration',
        __DIR__ . '/../../ext_emconf.php',
    ])
    ->withPhpSets(php84: true)
    ->withPhpVersion(PhpVersion::PHP_84)
    ->withSets([
        Typo3SetList::CODE_QUALITY,
        Typo3SetList::GENERAL,
        Typo3LevelSetList::UP_TO_TYPO3_12,
    ])
    ->withPHPStanConfigs([Typo3Option::PHPSTAN_FOR_RECTOR_PATH])
    ->withImportNames(false, false, false, true)
;
