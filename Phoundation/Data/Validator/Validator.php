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

        if (!is_string($this->selected_value)) {
            $this->addFailure(tr('The field ":key" must have an integer value'));
        }

        return $this;
    }



    /**
     * Validates the datatype for the selected array key
     *
     * This method ensures that the specified array key is an integer
     *
     * @return Validator
     */
    public function isNumeric(): Validator
    {
        $this->ensureSelected();

        if (!is_numeric($this->selected_value)) {
            $this->addFailure(tr('The field ":key" must have a numeric value'));
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
            $this->addFailure(tr('The field ":key" must have a string value'));
        }

        return $this;
    }
}