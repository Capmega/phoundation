<?php

/**
 * Class CookieValidator
 *
 * This class validates data from untrusted $_COOKIE
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Validator;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\TraitDataStaticArrayBackup;
use Phoundation\Data\Traits\TraitStaticMethodNew;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Developer\Debug;
use Phoundation\Utils\Strings;
use Stringable;


class CookieValidator extends Validator
{
    use TraitDataStaticArrayBackup;
    use TraitStaticMethodNew;


    /**
     * Internal $_COOKIE array until validation has been completed
     *
     * @var array|null $cookies
     */
    protected static ?array $cookies = null;


    /**
     * CookieValidator constructor.
     *
     * @note This class will purge the $_REQUEST array as this array contains a mix of $_COOKIE and $_COOKIE variables
     *       which should never be used
     *
     * @note Keys that do not exist in $data that are validated will automatically be created
     * @note Keys in $data that are not validated will automatically be removed
     */
    protected function __construct()
    {
        $this->construct(null, static::$cookies);
    }


    /**
     * Link $_COOKIE and $_COOKIE and $argv data to internal arrays to ensure developers cannot access them until
     * validation has been completed
     *
     * @note This class will purge the $_REQUEST array as this array contains a mix of $_COOKIE and $_COOKIE variables
     *       which should never be used
     *
     * @return void
     */
    public static function hideData(): void
    {
        global $_COOKIE;
        // Copy COOKIE data and reset both COOKIE and REQUEST

        static::$cookies = $_COOKIE;
        static::$backup  = $_COOKIE;

        $_COOKIE = [];
    }


    /**
     * Throws an exception if there are still arguments left in the COOKIE source
     *
     * @param bool $apply
     *
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
                    ':field' => $field,
                ]);
            }
        }

        throw ValidationFailedException::new(tr('Unknown COOKIE fields ":fields" encountered', [
            ':fields' => Strings::force($fields, ', '),
        ]))
        ->addData($messages)
        ->makeWarning()
        ->log();
    }


    /**
     * Add the specified value for key to the internal COOKIE array
     *
     * @param mixed                      $value
     * @param Stringable|string|int|null $key
     * @param bool                       $skip_null_values
     *
     * @return static
     */
    public function add(mixed $value, Stringable|string|int|null $key = null, bool $skip_null_values = false): static
    {
        if (($value === null) and $skip_null_values) {
            // Don't permit empty values
            return $this;
        }

        // Don't permit empty keys, quietly drop them
        $key = trim((string) $key);

        if (!$key) {
            return $this;
        }


        $this->source[$key] = $value;
        return $this;
    }


    /**
     * Returns the complete source, or only the source entries starting with the specified prefix
     *
     * @param string|null $prefix
     *
     * @return array
     */
    public function getSource(?string $prefix = null): array
    {
        if (!$prefix) {
            return parent::getSource();
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
}
