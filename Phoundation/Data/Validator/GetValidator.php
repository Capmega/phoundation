<?php

declare(strict_types=1);

namespace Phoundation\Data\Validator;


use Phoundation\Data\Validator\Exception\GetValidationFailedException;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Exception\ValidatorException;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Utils\Strings;


/**
 * GetValidator class
 *
 * This class validates data from untrusted $_GET
 *
 * $_REQUEST will be cleared automatically as this array should not  be used.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
class GetValidator extends Validator
{
    /**
     * Internal $_GET array until validation has been completed
     *
     * @var array|null $get
     */
    protected static ?array $get = null;


    /**
     * Validator constructor.
     *
     * @note Keys that do not exist in $data that are validated will automatically be created
     * @note Keys in $data that are not validated will automatically be removed
     *
     * @param ValidatorInterface|null $parent If specified, this is actually a child validator to the specified parent
     */
    public function __construct(?ValidatorInterface $parent = null)
    {
        $this->construct($parent, static::$get);
    }

    /**
     * Link $_GET and $_GET and $argv data to internal arrays to ensure developers cannot access them until validation
     * has been completed
     *
     * @note This class will purge the $_REQUEST array as this array contains a mix of $_GET and $_POST variables which
     *       should never be used
     *
     * @return void
     */
    public static function hideData(): void
    {
        global $_GET;

        // Copy GET data and reset both GET and REQUEST
        static::$get = $_GET;

        $_GET     = [];
        $_REQUEST = [];
    }

    /**
     * Throws an exception if there are still arguments left in the GET source
     *
     * @param bool $apply
     *
     * @return static
     */
    public function noArgumentsLeft(bool $apply = true): static
    {
        if (!$apply) {
            return $this;
        }
        if (count($this->selected_fields) === count(static::$get)) {
            return $this;
        }

        $messages = [];
        $get      = array_keys(static::$get);

        foreach ($get as $field) {
            if (!in_array($field, $this->selected_fields)) {
                $messages[] = tr('Unknown field ":field" encountered', [
                    ':field' => $field,
                ]);
            }
        }

        throw ValidatorException::new(tr('Unknown GET fields ":fields" encountered', [
            ':fields' => Strings::force($get, ', '),
        ]))->addData($messages)->makeWarning()->log();
    }

    /**
     * Add the specified value for key to the internal GET array
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public static function addData(string $key, mixed $value): void
    {
        static::$get[$key] = $value;
    }

    /**
     * Returns a new $_GET data Validator object
     *
     * @param ValidatorInterface|null $parent
     *
     * @return GetValidator
     */
    public static function new(?ValidatorInterface $parent = null): GetValidator
    {
        return new static($parent);
    }

    /**
     * Selects the specified key within the array that we are validating
     *
     * @param string|int $field The array key (or HTML form field) that needs to be validated / sanitized
     *
     * @return static
     */
    public function select(string|int $field): static
    {
        return $this->standardSelect($field);
    }


    /**
     * Clears the internal GET array
     *
     * @return void
     */
    public function clear(): void
    {
        static::$get = [];
        parent::clear();
    }


    /**
     * Called at the end of defining all validation rules.
     *
     * Will throw a GetValidationFailedException if validation fails
     *
     * @param bool $clean_source
     *
     * @return array
     * @throws GetValidationFailedException
     */
    public function validate(bool $clean_source = true): array
    {
        try {
            return parent::validate($clean_source);

        } catch (ValidationFailedException $e) {
            throw new GetValidationFailedException($e);
        }
    }
}
