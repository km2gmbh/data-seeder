<?php

declare(strict_types=1);

/*
 * This file is part of the "data_seeder" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace KM2\DataSeeder\DataHandling\Variable;

use KM2\DataSeeder\DataHandling\Node\InvalidPropertyException;

final class VariableCollection
{
    /**
     * @var array<string, Variable>
     */
    private array $variables = [];

    /**
     * @param array<string, int|string|bool|float>|list<Variable> $variables
     */
    public function __construct(array $variables = [])
    {
        $this->set($variables);
    }

    /**
     * @param array<string, int|string|bool|float>|list<Variable> $variables
     */
    public function set(array $variables): void
    {
        foreach ($variables as $variableName => $variableValue) {
            if ($variableValue instanceof Variable) {
                $this->variables[$variableValue->getName()] = $variableValue;
                continue;
            }

            if (!is_string($variableName)) {
                throw new \RuntimeException(
                    sprintf('Array key for $variables needs to be a string or value need to be of type %s.', Variable::class),
                    1783265267
                );
            }

            $this->variables[$variableName] = new Variable($variableName, $variableValue);
        }
    }

    public function add(string $variableName, mixed $variableValue): void
    {
        $this->variables[$variableName] = new Variable($variableName, $variableValue);
    }

    /**
     * @throws InvalidPropertyException
     */
    public function get(string $variableName): Variable
    {
        if (!$this->has($variableName)) {
            throw new InvalidPropertyException(
                sprintf('Variable %s does not exist.', $variableName),
                1783265327
            );
        }

        return $this->variables[$variableName];
    }

    public function has(string $variableName): bool
    {
        return isset($this->variables[$variableName]);
    }
}
