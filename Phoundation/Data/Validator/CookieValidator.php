<?php

declare(strict_types=1);

namespace Phoundation\Data\Validator;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Utils\Strings;
use Phoundation\Web\Page;


/**
 * PostValidator class
 *
 * This class validates data from untrusted $_COOKIE
 *
 * $_REQUEST will be cleared automatically as this array should not  be used.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Data
 */
class CookieValidator extends Validator
{
    /**
     * Internal $_COOKIE array until validation has been completed
     *
     * @var array|null $cookies
     */
    protected static ?array $cookies = null;


    /**
     * CookieValidator constructor.
     *
     * @note This class will purge the $_REQUEST array as this array contains a mix of $_COOKIE and $_COOKIE variables which
     *       should never be used
     *
     * @note Keys that do not exist in $data that are validated will automatically be created
     * @note Keys in $data that are not validated will automatically be removed
     *
     * @param ValidatorInterface|null $parent If specified, this is actually a child validator to the specified parent
     */
    protected function __construct(?ValidatorInterface $parent = null) {
        $this->construct($parent, static::$cookies);
    }


    /**
     * Returns a new $_COOKIE data Validator object
     *
     * @param ValidatorInterface|null $parent
     * @return static
     */
    public static function new(?ValidatorInterface $parent = null): static
    {
        return new static($parent);
    }


    /**
     * Link $_COOKIE and $_COOKIE and $argv data to internal arrays to ensure developers cannot access them until validation
     * has been completed
     *
     * @note This class will purge the $_REQUEST array as this array contains a mix of $_COOKIE and $_COOKIE variables which
     *       should never be used
     *
     * @return void
     */
    public static function hideData(): void
    {
        global $_COOKIE;

        // Copy COOKIE data and reset both COOKIE and REQUEST
        static::$cookies = $_COOKIE;

        $_COOKIE = [];
    }


    /**
     * Throws an exception if there are still arguments left in the COOKIE source
     *
     * @param bool $apply
     * @return static
     * @throws ValidationFailedException
     */
    public function noArgumentsLeft(bool $apply = true): static
    {
        if (!$apply) {
            return $this;
        }

        if (count($this->selected_fields) === count(static::$cookies)) {
            return $this;
        }

        $messages = [];
        $fields   = [];
        $post     = array_keys(static::$cookies);

        foreach ($post as $field) {
            if (!in_array($field, $this->selected_fields)) {
                $fields[]   = $field;
                $messages[] = tr('Unknown field ":field" encountered', [
                    ':field' => $field
                ]);
            }
        }

        throw ValidationFailedException::new(tr('Unknown COOKIE fields ":fields" encountered', [
            ':fields' => Strings::force($fields, ', ')
        ]))->addData($messages)->makeWarning()->log();
    }


    /**
     * Add the specified value for key to the internal COOKIE array
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function addData(string $key, mixed $value): void
    {
        static::$cookies[$key] = $value;
    }


    /**
     * Returns the submitted array keys
     *
     * @return array|null
     */
    public static function getKeys(): ?array
    {
        return array_keys(static::$cookies);
    }


    /**
     * Force a return of all COOKIE data without check
     *
     * @param string|null $prefix
     * @return array|null
     */
    public function &getSource(?string $prefix = null): ?array
    {
        if (!$prefix) {
            return $this->source;
        }

        $return = [];

        foreach ($this->source as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                $return[Strings::from($key, $prefix)] = $value;
            }
        }

        return $return;
    }


    /**
     * Force a return of a single COOKIE key value
     *
     * @return array
     */
    public function getSourceKey(string $key): mixed
    {
        Log::warning(tr('Forceably returned $_COOKIE[:key] without data validation!', [':key' => $key]));
        return isset_get($this->source[$key]);
    }


    /**
     * Selects the specified key within the array that we are validating
     *
     * @param string|int $field The array key (or HTML form field) that needs to be validated / sanitized
     * @return static
     */
    public function select(string|int $field): static
    {
        return $this->standardSelect($field);
    }
}
