<?php

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


declare(strict_types=1);

namespace Phoundation\Data\Validator;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\TraitDataStaticArrayBackup;
use Phoundation\Data\Traits\TraitStaticMethodNew;
use Phoundation\Data\Validator\Exception\GetValidationFailedException;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Exception\ValidatorException;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Developer\Debug;
use Phoundation\Utils\Strings;
use Stringable;


class GetValidator extends Validator
{
    use TraitDataStaticArrayBackup;
    use TraitStaticMethodNew;


    /**
     * Tracks the internal $_GET array until validation has been completed
     *
     * @var array|null $get
     */
    protected static ?array $get = null;


    /**
     * Validator constructor.
     *
     * @note Keys that do not exist in $data that are validated will automatically be created
     * @note Keys in $data that are not validated will automatically be removed
     */
    public function __construct()
    {
        $this->construct(null, static::$get);
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
        static::$get    = $_GET;
        static::$backup = $_GET;

        foreach (static::$get as $key => &$value) {
            $test = trim($key);

            if (!$test) {
                // If variables with empty keys show up, just quietly drop them
                unset(static::$get[$key]);

            } else {
                $value = trim($value);
                $value = urldecode($value);
            }
        }

        unset($value);

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

        if (count($this->selected_fields) === count($this->source)) {
            return $this;
        }

        $messages = [];
        $get      = array_keys($this->source);

        foreach ($get as $field) {
            if (!in_array($field, $this->selected_fields)) {
                $messages[] = tr('Unknown field ":field" encountered', [
                    ':field' => $field,
                ]);
            }
        }

        throw ValidatorException::new(tr('Unknown GET fields ":fields" encountered', [
            ':fields' => Strings::force($get, ', '),
        ]))->addData($messages)
           ->makeWarning()
           ->log();
    }


    /**
     * Add the specified value for key to the internal GET array
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
     * @inheritDoc
     */
    public function get(float|Stringable|int|string $key, bool $exception = false): mixed
    {
        return parent::get($key, $exception);
    }


    /**
     * Removes the specified key from the source
     *
     * @param string $key
     *
     * @return bool
     */
    public function remove(string $key): bool
    {
        $exists = array_key_exists($key, $this->source);

        unset($this->source[$key]);

        return $exists;
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
     * Called at the end of defining all validation rules.
     *
     * Will throw a GetValidationFailedException if validation fails
     *
     * @param bool $require_clean_source
     *
     * @return array
     * @throws GetValidationFailedException
     */
    public function validate(bool $require_clean_source = true): array
    {
        try {
            return parent::validate($require_clean_source);

        } catch (ValidationFailedException $e) {
            throw new GetValidationFailedException($e);
        }
    }


    /**
     * Returns the value for either one of the redirect or previous keys as specified in the GET request
     *
     * @return string|null
     */
    public static function getRedirectValue(): ?string
    {
        static $get;
        static $redirect;

        if (isset($redirect)) {
            return $redirect;
        }

        if (empty($get)) {
            $get = static::new()->select('redirect')->isOptional()->isUrl()
                                ->select('previous')->isOptional()->isUrl()
                                ->validate(false);
        }

        if ($get['redirect']) {
            $redirect = $get['redirect'];

        } elseif ($get['previous']) {
            $redirect = $get['previous'];

        } elseif (isset($_SERVER['HTTP_REFERER'])) {
            // TODO VALIDATE THE HTTP REFERER!!!
            $redirect = $_SERVER['HTTP_REFERER'];

        } else {
            $redirect = null;
        }

        return $redirect;
    }
}
