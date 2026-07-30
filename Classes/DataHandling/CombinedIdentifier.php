<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\DataHandling;

use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class CombinedIdentifier
{
    public function __construct(private string $recordType, private string $identifier, private string $property = 'uid')
    {
    }

    public static function fromString(string $combinedIdentifier): CombinedIdentifier
    {
        $trimmedCombinedIdentifier = trim($combinedIdentifier);
        if (!str_starts_with($trimmedCombinedIdentifier, '{') && !str_ends_with($trimmedCombinedIdentifier, '}')) {
            trigger_error(
                sprintf('Using identifier without curly brackets id deprecated and will be removed in the future. Triggered by "%s".', $trimmedCombinedIdentifier),
                E_USER_DEPRECATED
            );
        }

        $combinedIdentifierWithoutBraces = trim($combinedIdentifier, '{}');
        $countParts = substr_count($combinedIdentifierWithoutBraces, ':') + 1;
        if ($countParts === 3) {
            [$recordType, $identifier, $property] = GeneralUtility::trimExplode(':', $combinedIdentifierWithoutBraces, true, 3);

            return new CombinedIdentifier($recordType, $identifier, $property);
        }
        if ($countParts === 2) {
            [$recordType, $identifier] = GeneralUtility::trimExplode(':', $combinedIdentifierWithoutBraces, true, 2);

            return new CombinedIdentifier($recordType, $identifier);
        }

        throw new \RuntimeException(
            sprintf('Invalid combined identifier given. Combined identifier should follow the pattern <recordType>:<recordIdentifier>:<recordField>. The record field is optional. "%s" given.', $combinedIdentifier),
            1783715507
        );
    }

    public static function isValid(string $candidate): bool
    {
        return (bool)preg_match('/^{?[a-zA-Z_0-9\-]+:[a-zA-Z_0-9\-]+(:[a-zA-Z0-9_\-]+)?}?$/', trim($candidate));
    }

    public function getRecordType(): string
    {
        return $this->recordType;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getProperty(): string
    {
        return $this->property;
    }
}
