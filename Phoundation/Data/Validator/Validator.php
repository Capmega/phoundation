<?php

namespace Phoundation\Data\Validator;



use Phoundation\Core\Strings;

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
     * Allow the validator to check each element in a list of values.
     *
     * Basically each method will expect to process a list always and ->select() will put the selected value in an
     * artificial array because of this. ->each() actually will have a list of values, so puts that list directly into
     * $this->process_values
     *
     * @see Validator::self()
     * @see Validator::select()
     * @return Validator
     */
    public function each(): Validator
    {
        // This obviously only works on arrays
        $this->isArray();
        $this->process_values = &$this->selected_value;

        return $this;
    }



    /**
     * Will let the validator treat the value as a single variable
     *
     * Basically each method will expect to process a list always and ->select() will put the selected value in an
     * artificial array because of this. ->each() actually will have a list of values, so puts that list directly into
     * $this->process_values
     *
     * @see Validator::each()
     * @see Validator::select()
     * @return Validator
     */
    public function single(): Validator
    {
        $this->process_values = [&$this->selected_value];

        return $this;
    }



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

        foreach ($this->process_values as &$value) {
            if (!is_integer($value)) {
                $this->addFailure($this->selected_field, tr('The field ":field" must have an integer value', [':field' => $this->selected_label]));
            }
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

        foreach ($this->process_values as &$value) {
            if (!is_numeric($value)) {
                $this->addFailure($this->selected_field, tr('The field ":field" must have a numeric value', [':field' => $this->selected_label]));
            }
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

        foreach ($this->process_values as &$value) {
            if (!is_string($value)) {
                $this->addFailure($this->selected_field, tr('The field ":field" must have a string value', [':field' => $this->selected_label]));
            }
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

        foreach ($this->process_values as &$value) {
            if (!is_array($value)) {
                $this->addFailure($this->selected_field, tr('The field ":field" must have an array value', [':field' => $this->selected_label]));
            }
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

        foreach ($this->process_values as &$value) {
            if (!is_array($value)) {
                $this->addFailure($this->selected_field, tr('The field ":field" must have an array value', [':field' => $this->selected_label]));
            }
        }

        return $this;
    }
}