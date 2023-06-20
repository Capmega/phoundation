<?php

declare(strict_types=1);

namespace Phoundation\Data\Validator;

use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;


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
    public function __construct(?ValidatorInterface $parent = null) {
        $this->construct($parent, static::$post);
    }


    /**
     * Returns a new $_POST data Validator object
     *
     * @param ValidatorInterface|null $parent
     * @return static
     */
    public static function new(?ValidatorInterface $parent = null): static
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
     */
    public function noArgumentsLeft(bool $apply = true): static
    {
        if (!$apply) {
            return $this;
        }

        if (count($this->selected_fields) === count(static::$get)) {
            return $this;
        }

        throw ValidationFailedException::new(tr('Unknown fields ":arguments" encountered', [
            ':arguments' => Strings::force(static::$post, ', ')
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
     * @return array|null
     */
    public function forceRead(): ?array
    {
        Log::warning(tr('Forceably returned all $_POST data without data validation!'));
        return $this->source;
    }


    /**
     * Force a return of a single POST key value
     *
     * @return array
     */
    public function forceReadKey(string $key): mixed
    {
        Log::warning(tr('Forceably returned $_POST[:key] without data validation!', [':key' => $key]));
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
     * Return the submit method
     *
     * @param string $submit
     * @return string|null
     */
    public static function getSubmitButton(string $submit = 'submit'): ?string
    {
        $button = trim((string) isset_get(self::$post[$submit]));

        unset(self::$post[$submit]);

        if (!$button) {
            return null;
        }

        if ((strlen($button) > 32) or !ctype_print($button)) {
            throw ValidationFailedException::new(tr('Invalid submit button specified'))->setData([
                'submit' => tr('The specified submit button is invalid'),
            ]);
        }

        return $button;
    }
}