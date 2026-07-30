<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\DataHandling;

use Symfony\Component\Console\Helper\ProgressIndicator as SymfonyProgressIndicator;

final class ProgressIndicator
{
    private static SymfonyProgressIndicator $progressIndicator;

    public function setProgressIndicator(SymfonyProgressIndicator $progressIndicator): void
    {
        self::$progressIndicator = $progressIndicator;
    }

    public function start(string $message): void
    {
        self::$progressIndicator->start($message);
    }

    public function advance(): void
    {
        self::$progressIndicator->advance();
    }

    public function finish(string $message): void
    {
        self::$progressIndicator->finish($message);
    }
}
