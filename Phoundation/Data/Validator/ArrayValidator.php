<?php

namespace Phoundation\Data\Validator;



/**
 * ArrayValidator class
 *
 * This class validates data for the specified array
 *
 * $_REQUEST will be cleared automatically as this array should not  be used.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
        // Ensure the source is an array
        if ($source === null) {
            $source = [];
        }

        $this->source = &$source;
        $this->parent = $parent;
    }



    /**
     * Returns a new array validator
     *
     * @param array $source
     * @param Validator|null $parent
     * @return static
     */
    public static function new(array &$source, ?Validator $parent = null): static
    {
        return new static($source, $parent);
    }
}