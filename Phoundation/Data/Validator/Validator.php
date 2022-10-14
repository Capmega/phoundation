<?php

namespace Phoundation\Data\Validator;



/**
 * Validator class
 *
 * This class validates data from untrusted arrays
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Utils
 */
class Validator
{
    use ValidatorBasics;


   /**
     * Validates the datatype for the selected array key
     *
     * This method ensures that the specified array key is an integer
     *
     * @return Validator
     */
    public function isInteger(): Validator
    {
        $this->ensureSelected();

        if (!is_integer($this->selected_value)) {
            $this->addFailure($this->selected_field, tr('The field ":field" must have an integer value', [':field' => $this->selected_field]));
        }

        return $this;
    }



    /**
     * Validates the datatype for the selected array key
     *
     * This method ensures that the specified array key is numeric
     *
     * @return Validator
     */
    public function isNumeric(): Validator
    {
        $this->ensureSelected();

        if (!is_numeric($this->selected_value)) {
            $this->addFailure($this->selected_field, tr('The field ":field" must have a numeric value', [':field' => $this->selected_field]));
        }

        return $this;
    }



    /**
     * Validates the datatype for the selected array key
     *
     * This method ensures that the specified array key is a string
     *
     * @return Validator
     */
    public function isString(): Validator
    {
        $this->ensureSelected();

        if (!is_string($this->selected_value)) {
            $this->addFailure($this->selected_field, tr('The field ":field" must have a string value', [':field' => $this->selected_field]));
        }

        return $this;
    }



    /**
     * Validates the datatype for the selected array key
     *
     * This method ensures that the specified array key is an array
     *
     * @return Validator
     */
    public function isArray(): Validator
    {
        $this->ensureSelected();

        if (!is_array($this->selected_value)) {
            $this->addFailure($this->selected_field, tr('The field ":field" must have an array value', [':field' => $this->selected_field]));
        }

        return $this;
    }
}