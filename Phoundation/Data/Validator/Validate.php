<?php

namespace Phoundation\Data\Validator;

use Phoundation\Data\Validator\Exception\ValidationFailedException;


/**
 * Validate class
 *
 * This class can apply a large amount of validation tests on a single value
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Data
 */
class Validate
{
    /**
     * The source data that will be validated
     *
     * @var mixed $source
     */
    protected mixed $source;


    /**
     * Validate class constructor
     *
     * @param mixed $source
     */
    public function __construct(mixed $source)
    {
        $this->source = $source;
    }


    /**
     * Returns a new Validate object
     *
     * @param mixed $source
     * @return static
     */
    public static function new(mixed $source): static
    {
        return new static($source);
    }


    /**
     * Validates if the selected field is a valid domain name
     *
     * @return static
     */
    public function isNumeric(): static
    {
        if (!is_numeric($this->source)) {
            throw new ValidationFailedException(tr('The specified value must be numeric'));
        }

        return $this;
    }


    /**
     * Validates if the selected field is a valid domain name
     *
     * @param int|float $amount
     * @param bool $equal
     * @return static
     */
    public function isLessThan(int|float $amount, bool $equal = false): static
    {
        if ($this->source < $amount) {
            return $this;
        }

        if (($this->source == $amount) and $equal) {
            return $this;
        }

        if ($equal) {
            throw new ValidationFailedException(tr('The specified value must be less than or equal to ":amount"', [
                ':amount' => $amount
            ]));
        }

        throw new ValidationFailedException(tr('The specified value must be less than ":amount"', [
            ':amount' => $amount
        ]));
    }


    /**
     * Validates if the selected field is a valid domain name
     *
     * @param int|float $amount
     * @param bool $equal
     * @return static
     */
    public function isMoreThan(int|float $amount, bool $equal = false): static
    {
        if ($this->source > $amount) {
            return $this;
        }

        if (($this->source == $amount) and $equal) {
            return $this;
        }


        if ($equal) {
            throw new ValidationFailedException(tr('The specified value must be more than or equal to ":amount"', [
                ':amount' => $amount
            ]));
        }

        throw new ValidationFailedException(tr('The specified value must be more than ":amount"', [
            ':amount' => $amount
        ]));
    }


    /**
     * Validates if the specified value is a valid port
     *
     * @return static
     */
    public function isInArray(array $compare): static
    {
        if (!in_array($this->source, $compare)) {
            throw new ValidationFailedException(tr('The specified value must be one of ":list"', [
                ':list' => $compare
            ]));
        }

        return $this;
    }


    /**
     * Validates if the specified value is a valid port
     *
     * @return static
     */
    public function isPort(): static
    {
        return $this->isNumeric()->isMoreThan(0)->isLessThan(65536);
    }
}