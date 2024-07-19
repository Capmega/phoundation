<?php

declare(strict_types=1);

namespace Phoundation\Data\Validator\Interfaces;

interface ArgvValidatorInterface extends ValidatorInterface
{
    /**
     * Selects the specified key within the array that we are validating
     *
     * @param string|int  $fields The array key (or HTML form field) that needs to be validated / sanitized
     * @param string|bool $next
     *
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
