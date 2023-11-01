<?php

declare(strict_types=1);

namespace Phoundation\Data\Validator;

use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Exception\ValidatorException;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Web\Page;


/**
 * PostValidator class
 *
 * This class validates data from untrusted $_POST
 *
 * $_REQUEST will be cleared automatically as this array should not  be used.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Data
 */
class PostValidator extends Validator
{
    /**
     * Internal $_POST array until validation has been completed
     *
     * @var array|null $post
     */
    protected static ?array $post = null;

    /**
     * Tracks what button was pressed for the POST request
     *
     * @var string|false|null $button
     */
    protected static string|false|null $button = null;

    /**
     * Tracks prefix for the pressed button
     *
     * @var string|null $key
     */
    protected static ?string $key = null;


    /**
     * Validator constructor.
     *
     * @note This class will purge the $_REQUEST array as this array contains a mix of $_GET and $_POST variables which
     *       should never be used
     *
     * @note Keys that do not exist in $data that are validated will automatically be created
     * @note Keys in $data that are not validated will automatically be removed
     *
     * @param ValidatorInterface|null $parent If specified, this is actually a child validator to the specified parent
     */
    protected function __construct(?ValidatorInterface $parent = null) {
        $this->construct($parent, static::$post);
    }


    /**
     * Returns a new $_POST data Validator object
     *
     * @param ValidatorInterface|null $parent
     * @return PostValidator
     */
    public static function new(?ValidatorInterface $parent = null): PostValidator
    {
        return new static($parent);
    }


    /**
     * Link $_GET and $_POST and $argv data to internal arrays to ensure developers cannot access them until validation
     * has been completed
     *
     * @note This class will purge the $_REQUEST array as this array contains a mix of $_GET and $_POST variables which
     *       should never be used
     *
     * @return void
     */
    public static function hideData(): void
    {
        global $_POST;

        // Copy POST data and reset both POST and REQUEST
        static::$post = $_POST;

        $_POST    = [];
        $_REQUEST = [];
    }


    /**
     * Throws an exception if there are still arguments left in the POST source
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

        if (count($this->selected_fields) === count(static::$post)) {
            return $this;
        }

        $messages = [];
        $fields   = [];
        $post     = array_keys(static::$post);

        foreach ($post as $field) {
            if (!in_array($field, $this->selected_fields)) {
                $fields[]   = $field;
                $messages[] = tr('Unknown field ":field" encountered', [
                    ':field' => $field
                ]);
            }
        }

        throw ValidationFailedException::new(tr('Unknown POST fields ":fields" encountered', [
            ':fields' => Strings::force($fields, ', ')
        ]))->addData($messages)->makeWarning()->log();
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
        static::$post[$key] = $value;
    }


    /**
     * Returns the submitted array keys
     *
     * @return array|null
     */
    public static function getKeys(): ?array
    {
        return array_keys(static::$post);
    }


    /**
     * Force a return of all POST data without check
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
     * Force a return of a single POST key value
     *
     * @return array
     */
    public function getSourceKey(string $key): mixed
    {
        Log::warning(tr('Forceably returned $_POST[:key] without data validation!', [':key' => $key]));
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


    /**
     * Return the submit method
     *
     * @param string $submit
     * @param bool $prefix
     * @param bool $return_key
     * @return string|false|null
     */
    public static function getSubmitButton(string $submit = 'submit', bool $prefix = false, bool $return_key = false): string|false|null
    {
        if (!Page::isPostRequestMethod()) {
            return null;
        }

        // Return cache
        if ($return_key and static::$key) {
            return static::$key;

        }

        if (static::$button !== null) {
            return static::$button;
        }

        // Search for the specified prefix code for the button
        if ($prefix) {
            $prefix = $submit;
            $button = null;

            foreach (static::$post as $key => $value) {
                if (str_ends_with($key, $submit)) {
                    $submit = $key;
                    $button = trim((string) $value);
                    break;
                }
            }

        } else {
            // Button must be an exact match
            $button = trim((string) isset_get(static::$post[$submit]));
        }

        if (!$submit) {
            // Specified button not found
            return null;
        }

        unset(static::$post[$submit]);

        if ($button) {
            if ((strlen($button) > 255) or !ctype_print($button)) {
                throw ValidationFailedException::new(tr('Invalid submit button specified'))->addData([
                    'submit' => tr('The specified submit button is invalid'),
                ]);
            }
        }

        if ($return_key) {
            static::$key = Strings::until($submit, $prefix);

            if ((strlen(static::$key) > 32) or !ctype_print(static::$key)) {
                throw ValidationFailedException::new(tr('Invalid submit button specified'))->addData([
                    'submit' => tr('The specified submit button is invalid'),
                ]);
            }

            return Strings::until($submit, $prefix);
        }

        static::$button = $button;

        if (!$button) {
            // Button exists, but has no value
            return false;
        }

        return $button;
    }
}
