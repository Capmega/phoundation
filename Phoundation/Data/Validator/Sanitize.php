<?php

namespace Phoundation\Data\Validator;

use Phoundation\Accounts\Users\Password;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\Config;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Traits\DataMaxStringSize;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Utils\Strings;


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
class Sanitize
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
     * Returns the source data
     *
     * @return mixed
     */
    public function getSource(): mixed
    {
        return $this->source;
    }


    /**
     * Sanitize the specified field
     */
    public function phoneNumber(): static
    {
        $value  = trim((string) $this->source);
        $prefix = (str_starts_with($value, '+') ? '+' : Config::getString('validation.defaults.phones.country-code', '+1'));
        $ext    = Strings::from($value, 'ext', require: true);
        $value  = preg_replace('/[^0-9]+/', '', $value);
        $ext    = preg_replace('/[^0-9]+/', '', $ext);

        if ($value) {
            $this->source = $prefix . $value . ($ext ? ' ext. ' . $ext : null);

        } else {
            $this->source = null;
        }

        return $this;
    }
}