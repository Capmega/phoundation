<?php

declare(strict_types=1);

namespace Phoundation\Data\Validator;


use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;


/**
 * GetValidator class
 *
 * This class validates data from untrusted $_GET
 *
 * $_REQUEST will be cleared automatically as this array should not  be used.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Data
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
    public function __construct(?ValidatorInterface $parent = null) {
        $this->construct($parent, static::$get);
    }


    /**
     * Returns a new $_GET data Validator object
     *
     * @param ValidatorInterface|null $parent
     * @return static
     */
    public static function new(?ValidatorInterface $parent = null): static
    {
        return new static($parent);
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
     * @return static
     */
    public function noArgumentsLeft(bool $apply = true): static
    {
        if (!$apply) {
            return $this;
        }

        if (empty(static::$get)) {
            return $this;
        }

        throw ValidationFailedException::new(tr('Invalid GET fields ":arguments" encountered', [
            ':arguments' => Strings::force(static::$get, ', ')
        ]))->makeWarning();
    }


    /**
     * Add the specified value for key to the internal GET array
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function addData(string $key, mixed $value): void
    {
        static::$get[$key] = $value;
    }


    /**
     * Validate GET data and liberate GET data if all went well.
     *
     * @return array
     */
    public function validate(): array
    {
        try {
            parent::validate();
            return $this->liberateData();

        } catch (ValidationFailedException $e) {
            // Failed data will have been filtered, liberate data!
            $this->liberateData();
            throw $e;
        }
    }


    /**
     * Force a return of all GET data without check
     *
     * @return array|null
     */
    public function extract(): ?array
    {
        Log::warning(tr('Liberated all $_GET data without data validation!'));
        return $this->source;
    }


    /**
     * Force a return of a single GET key value
     *
     * @return array
     */
    public function extractKey(string $key): mixed
    {
        Log::warning(tr('Liberated $_GET[:key] without data validation!', [':key' => $key]));
        return isset_get($this->source[$key]);
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


    /**
     * Gives free and full access to $_GET data, now that it has been validated
     *
     * @return array
     */
    protected function liberateData(): array
    {
        global $_GET;

        $_GET = static::$get;
        static::$get = null;

        return $_GET;
    }
}