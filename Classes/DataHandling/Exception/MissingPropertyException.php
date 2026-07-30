<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\DataHandling\Exception;

class MissingPropertyException extends \Exception
{
    public function __construct(string $property, string $type, int $code = 0, ?\Throwable $previous = null)
    {
        $message = sprintf('Missing property %s for type %s', $property, $type);
        parent::__construct($message, $code, $previous);
    }
}
