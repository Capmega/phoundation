<?php

namespace Phoundation\Data\Validator\Interfaces;


/**
 * ArgvValidator class
 *
 * This class validates data from untrusted $argv
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Data
 */
interface ArgvValidatorInterface extends ValidatorInterface
{
    /**
     * Selects the specified key within the array that we are validating
     *
     * @param string|int $fields The array key (or HTML form field) that needs to be validated / sanitized
     * @param string|bool $next
     * @return static
     */
    public function select(string|int $fields, string|bool $next = false): static;

    /**
     * Returns the $argv array
     *
     * @return array
     */
    public function getArgv(): array;
}
