<?php

declare(strict_types=1);

namespace Phoundation\Data\Validator;


use Phoundation\Core\Strings;
use Phoundation\Data\Validator\Exception\ValidationFailedException;

/**
 * ArrayValidator class
 *
 * This class validates data for the specified array
 *
 * $_REQUEST will be cleared automatically as this array should not  be used.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Data
 */
class ArrayValidator extends Validator
{
    /**
     * Validator constructor.
     *
     * @note Keys that do not exist in $data that are validated will automatically be created
     * @note Keys in $data that are not validated will automatically be removed
     *
     * @param array|null $source The data array that must be validated.
     * @param Validator|null $parent If specified, this is actually a child validator to the specified parent
     */
    public function __construct(?array &$source = [], ?Validator $parent = null) {
        $this->construct($parent, $source);
    }


    /**
     * Returns a new array data Validator object
     *
     * @param array $source
     * @param Validator|null $parent
     * @return static
     */
    public static function new(array &$source, ?Validator $parent = null): static
    {
        return new static($source, $parent);
    }


    /**
     * Selects the specified key within the array that we are validating
     *
     * @param int|string $field The array key (or HTML form field) that needs to be validated / sanitized
     * @return static
     */
    public function select(int|string $field): static
    {
        return $this->standardSelect($field);
    }
}