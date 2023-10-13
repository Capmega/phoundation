<?php

namespace Phoundation\Data\Validator;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\DataMaxStringSize;
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
    use DataMaxStringSize;


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
     * Validates if the selected field is integer
     *
     * @return static
     */
    public function isInteger(): static
    {
        if (!is_integer($this->source)) {
            throw new ValidationFailedException(tr('The specified value must be integer'));
        }

        return $this;
    }


    /**
     * Validates if the selected field is a float
     *
     * @return static
     */
    public function isFloat(): static
    {
        if (!is_float($this->source)) {
            throw new ValidationFailedException(tr('The specified value must be a float'));
        }

        return $this;
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
     * Validates if the selected field is a string value
     *
     * @return static
     */
    public function isString(): static
    {
        if (!is_string($this->source)) {
            throw new ValidationFailedException(tr('The specified value must be a string'));
        }

        return $this;
    }


    /**
     * Validates if the selected field is a scalar value
     *
     * @return static
     */
    public function isScalar(): static
    {
        if (!is_scalar($this->source)) {
            throw new ValidationFailedException(tr('The specified value must be a scalar'));
        }

        return $this;
    }


    /**
     * Validates if the selected field is an array
     *
     * @return static
     */
    public function isArray(): static
    {
        if (!is_array($this->source)) {
            throw new ValidationFailedException(tr('The specified value must be an array'));
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
     * @param array $compare
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
     * Validates that the selected field is equal or larger than the specified amount of characters
     *
     * @param int $characters
     * @return static
     */
    public function hasCharacters(int $characters): static
    {
        $this->isString();

        if (strlen($this->source) != $characters) {
            throw new ValidationFailedException(tr('The specified valuemust have exactly ":count" characters', [':count' => $characters]));
        }

        return $this;
    }


    /**
     * Validates that the selected field is equal or larger than the specified amount of characters
     *
     * @param int $characters
     * @return static
     */
    public function hasMinCharacters(int $characters): static
    {
        $this->isString();

        if (strlen($this->source) < $characters) {
            throw new ValidationFailedException(tr('The specified value must have ":count" characters or more', [':count' => $characters]));
        }

        return $this;
    }


    /**
     * Validates that the selected field is equal or shorter than the specified amount of characters
     *
     * @param int|null $characters
     * @return static
     */
    public function hasMaxCharacters(?int $characters = null): static
    {
        $this->isString();

        // Validate the maximum amount of characters
        $characters = $this->getMaxStringSize($characters);

        if (strlen($this->source) > $characters) {
            throw new ValidationFailedException(tr('The specified value must have ":count" characters or less', [':count' => $characters]));
        }

        return $this;
    }


    /**
     * Validates that the selected field matches the specified regex
     *
     * @param string $regex
     * @return static
     */
    public function matchesRegex(string $regex): static
    {
        return $this->contains($regex, true);
    }


    /**
     * Ensures that the value has the specified string
     *
     * This method ensures that the specified array key contains the specified string
     *
     * @param string $string
     * @param bool $regex
     * @return static
     */
    public function contains(string $string, bool $regex = false): static
    {
        // This value must be scalar
        $this->isScalar();

        if ($regex) {
            if (!preg_match($string, $this->source)) {
                throw new ValidationFailedException(tr('The specified value must match regex ":value"', [':value' => $string]));
            }
        } else {
            if (!str_contains($this->source, $string)) {
                throw new ValidationFailedException(tr('The specified value must contain ":value"', [':value' => $string]));
            }
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


    /**
     * Validates if the selected field is a valid version number
     *
     * @param int $characters
     * @param bool $allow_post If true, the version "post" will be allowed as a valid version
     * @return static
     */
    public function isVersion(int $characters = 11, bool $allow_post = false): static
    {
        $this->hasMaxCharacters($characters);

        if (!preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}/', $this->source)) {
            switch($this->source) {
                case 'post_once':
                    // no break

                case 'post_always':
                    if ($allow_post) {
                        break;
                    }

                    // no break
                default:
                    throw new ValidationFailedException(tr('The specified value must contain a valid version number'));
            }

            // This is a valid "post" version, and it's allowed. Continue!
        }

        return $this;
    }


    /**
     * Validates if the selected field is a valid email address
     *
     * @param int $characters
     * @return static
     */
    public function isEmail(int $characters = 2048): static
    {
        $this->hasMaxCharacters($characters);

        if (!filter_var($this->source, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationFailedException(tr('The specified value must contain a valid email address'));
        }

        return $this;
    }


    /**
     * Validates if the selected field is a valid phone number
     *
     * @param int $min_characters
     * @param int $max_characters
     * @return static
     */
    public function isPhone(int $min_characters = 10, int $max_characters = 20): static
    {
        $this->hasMinCharacters($min_characters)
             ->hasMaxCharacters($max_characters)
             ->matchesRegex('/^\+?[0-9-#\* ].+?$/');

        return $this;
    }
}
