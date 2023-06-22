<?php

declare(strict_types=1);

namespace Phoundation\Data\Validator;


use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Utils\Json;


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
        if (count($this->selected_fields) === count(static::$get)) {
            return $this;
        }

        $fields = [];
        $get    = array_keys(static::$get);

        foreach ($post as $field) {
            if (!in_array($field, $this->selected_fields)) {
                $fields[]   = $field;
                $messages[] = tr('Unknown field ":field" encountered', [
                    ':field' => $field
                ]);
            }
        }

        throw ValidationFailedException::new(tr('Unknown GET fields ":fields" encountered', [
            ':fields' => Strings::force($get, ', ')
        ]))->setData($messages)->makeWarning()->log();
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
     * Force a return of all GET data without check
     *
     * @return array|null
     */
    public function forceRead(): ?array
    {
        Log::warning(tr('Forceably returned all $_GET data without data validation!'));
        return $this->source;
    }


    /**
     * Force a return of a single GET key value
     *
     * @return array
     */
    public function forceReadKey(string $key): mixed
    {
        Log::warning(tr('Forceably returned $_GET[:key] without data validation!', [':key' => $key]));
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
}