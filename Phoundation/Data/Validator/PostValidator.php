<?php

/**
 * PostValidator class
 *
 * This class validates data from untrusted $_POST
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
use Phoundation\Data\Validator\Exception\CsrfFailedException;
use Phoundation\Data\Validator\Exception\PostValidationFailedException;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Developer\Debug;
use Phoundation\Utils\Exception\JsonException;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Csrf;
use Phoundation\Web\Requests\Request;
use Stringable;


class PostValidator extends Validator
{
    use TraitDataStaticArrayBackup;
    use TraitStaticMethodNew;


    /**
     * Internal $_POST array until validation has been completed
     *
     * @var array|null $post
     */
    protected static ?array $post = null;

    /**
     * Tracks what button was pressed for the POST request
     *
     * @var array $buttons
     */
    protected static array $buttons = [];

    /**
     * Tracks prefix for the pressed button
     *
     * @var string|null $key
     */
    protected static ?string $key = null;

    /**
     * If true will force CSRF testing
     *
     * @var bool
     */
    protected bool $test_csrf = true;

    /**
     * Tracks the CSRF code we received
     *
     * @var string|null
     */
    protected static ?string $received_csrf = null;


    /**
     * PostValidator constructor.
     *
     * @note This class will purge the $_REQUEST array as this array contains a mix of $_GET and $_POST variables which
     *       should never be used
     *
     * @note Keys that do not exist in $data that are validated will automatically be created
     * @note Keys in $data that are not validated will automatically be removed
     */
    public function __construct()
    {
        $this->construct(null, static::$post);
    }


    /**
     * Link $_POST data to internal arrays to ensure developers cannot access them until validation has been completed
     *
     * @note This class will purge the $_REQUEST array as this array contains a mix of $_GET and $_POST variables which
     *       should never be used
     *
     * @return void
     */
    public static function hideData(): void
    {
        global $_POST;

        // Get $_POST data from RAW JSON?
        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            // Check for RAW input
            static::hideRawData();
        }

        // Copy POST data and reset both POST and REQUEST
        static::$post   = $_POST;
        static::$backup = $_POST;

        $_POST    = [];
        $_REQUEST = [];
    }


    /**
     * Link RAW $_POST data to internal arrays to ensure developers cannot access them until validation has been
     * completed
     *
     * @return void
     */
    protected static function hideRawData(): void
    {
        // Check for RAW input
        if(empty($_POST)){
            try{
                $json = file_get_contents('php://input');

                if ($json) {
                    $json = Json::decode($json);

                    if (!is_array($json)) {
                        throw new ValidationFailedException(tr('Invalid RAW POST JSON ":json" encountered, it should be an array', [
                            ':json' => $json
                        ]));
                    }

                    $_POST = $json;
                }

            } catch (JsonException $e) {
                throw new ValidationFailedException(tr('Failed to decode RAW POST JSON'), $e);
            }
        }
    }


    /**
     * Returns the value for the specified submit button for POST requests
     *
     * This method will search for and -if found- return the value for the specified HTTP POST key. By default it will
     * search for the POST name "submit". If can scan in prefix mode, where it will search for HTTP POST keys that start
     * with the specified POST key. If it finds a matching entry, that first entry will be returned.
     *
     * This method will not (and cannot) return if ANY button was pressed as it cannot see the difference between a
     * button and a form value. A specific button name must be specified.
     *
     * @note This method will return NULL in case the requested submit button was not pressed
     * @note For non POST requests this method will always return NULL
     * @note For buttons that have an empty value (null, ""), this method will return TRUE instead
     * @note The specified POST key, if found, will be removed from the POST array. If a prefix scan was requested, the
     *       found (and returned) POST key will be removed from the POST array.
     * @note The button values will be removed from the POST array but kept in cache, so calling this method twice for
     *       the same button will return the same value, even though it was removed from POST after the first call.
     * @note If $return_key AND $prefix were specified, this method will return the POST key FROM the specified
     *       $post_key. So if $post_key was "delete_" and the found key was "delete_2342897342", this method will return
     *       the value "2342897342" instead of "delete_2342897342"
     *
     * @param string $post_key
     * @param bool   $prefix     Will not return the specified POST $post_key value but scan for a POST key that starts
     *                           with $post_key, and return that value.
     * @param bool   $return_key If true, will return the found POST_KEY instead of the value.
     *
     * @return string|true|null
     */
    public function getSubmitButton(string $post_key = 'submit', bool $prefix = false, bool $return_key = false): string|true|null
    {
        if (!Request::isPostRequestMethod()) {
            return null;
        }

        // Return button from cache if available
        $button = $this->getButton(static::$buttons, $post_key, $prefix, false);

        if ($button) {
            // We had it from cache. Get button key and value
            $key   = (string) key($button);
            $value = (string) current($button);

        } else {
            // Not cached. Get button from post and remove it there, then store it in cache
            $button = $this->getButton($this->source, $post_key, $prefix, true);

            if (!$button) {
                // Button was not in cache nor in POST
                return null;
            }

            // Get the button key and value
            $key   = (string) key($button);
            $value = (string) current($button);

            // Quick validate button value. Don't allow weird shit
            if ($key) {
                if ((strlen($key) > 255) or !ctype_print($key)) {
                    throw ValidationFailedException::new(tr('Invalid submit button specified'))
                                                   ->addData([
                                                       'submit' => tr('The specified submit button is invalid'),
                                                   ]);
                }
            }

            if ($value) {
                if ((strlen($value) > 255) or !ctype_print($value)) {
                    throw ValidationFailedException::new(tr('Invalid submit button specified'))
                                                   ->addData([
                                                       'submit' => tr('The specified submit button is invalid'),
                                                   ]);
                }
            }

            static::$buttons[$key] = $value;
        }

        // We have a button, yay!
        if ($return_key) {
            if ($prefix) {
                return Strings::from($key, $post_key);
            }

            return $key;
        }

        if (!$value) {
            // Button exists, but has no value
            return true;
        }

        return $value;
    }


    /**
     * Returns the requested button from the specified source
     *
     * @param array  $source
     * @param string $post_key
     * @param bool   $prefix
     * @param bool   $remove
     *
     * @return array|null
     */
    protected function getButton(array &$source, string $post_key, bool $prefix, bool $remove): array|null
    {
        if ($prefix) {
            // Search for the specified prefix code for the button
            foreach ($source as $key => $value) {
                if (str_starts_with($key, $post_key)) {
                    $post_key = $key;
                    $button   = trim((string) $value);
                    break;
                }
            }

            if (!isset($button)) {
                // No button with specified prefix found
                return null;
            }

        } else {
            // Button must be an exact match
            if (!array_key_exists($post_key, $source)) {
                // Button was not pressed
                return null;
            }

            $button = trim((string) $source[$post_key]);
        }

        if ($remove) {
            unset($source[$post_key]);
        }

        return [$post_key => $button];
    }


    /**
     * Add the specified value for key to the internal POST array
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
     * Throws an exception if there are still arguments left in the POST source
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

        if (count($this->selected_fields) === count($this->source)) {
            return $this;
        }

        $messages = [];
        $fields   = [];
        $post     = array_keys($this->source);

        foreach ($post as $field) {
            if (!in_array($field, $this->selected_fields)) {
                $fields[]   = $field;
                $messages[] = tr('Unknown field ":field" encountered', [
                    ':field' => $field,
                ]);
            }
        }

        throw ValidationFailedException::new(tr('Unknown POST fields ":fields" encountered', [
            ':fields' => Strings::force($fields, ', '),
        ]))->addData([
                'failures' => $messages,
            ])
           ->makeWarning()
           ->log();
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

        // Return only source entries starting with the specified prefix
        $return = [];

        foreach ($this->source as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                $return[Strings::from($key, $prefix)] = $value;
            }
        }

        return $return;
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
     * Will throw a PostValidationFailedException if validation fails
     *
     * @param bool $require_clean_source
     *
     * @return array
     */
    public function validate(bool $require_clean_source = true): array
    {
        try {
            $this->checkCsrf();

            return parent::validate($require_clean_source);

        } catch (ValidationFailedException $e) {
            if ($e instanceof CsrfFailedException) {
                throw new PostValidationFailedException(tr('Post validation failed due to CSRF exception'), $e);
            }

            throw $e;
        }
    }


    /**
     * Enables CSRF testing
     *
     * @return static
     */
    public function enableCsrf(): static
    {
        $this->test_csrf = true;

        return $this;
    }


    /**
     * Disables CSRF testing
     *
     * @return static
     */
    public function disableCsrf(): static
    {
        $this->test_csrf = false;

        return $this;
    }


    /**
     * Returns the CSRF code we received with this request
     *
     * @return string|null
     */
    public function getReceivedCsrf(): ?string
    {
        return static::$received_csrf;
    }


    /**
     * Checks if the CSRF code
     *
     * @return static
     */
    protected function checkCsrf(): static
    {
        if ($this->test_csrf) {
            if (empty(static::$received_csrf)) {
                // Try to get the CSRF code from the POST data and remove it
                if (array_key_exists('__csrf', $this->source)) {
                    $csrf = $this->source['__csrf'];
                    unset($this->source['__csrf']);

                    if (!is_string($csrf)) {
                        throw new CsrfFailedException(tr('Specified CSRF code is a ":csrf" while it should be a string value', [
                            ':csrf' => gettype($csrf),
                        ]));
                    }

                } else {
                    $csrf = null;
                }

                // Check CSRF
                static::$received_csrf = $csrf;
                Csrf::check(static::$received_csrf);
            }
        }

        return $this;
    }
}
