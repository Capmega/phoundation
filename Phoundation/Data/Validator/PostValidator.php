<?php

namespace Phoundation\Data\Validator;



use Phoundation\Data\Validator\Exception\ValidationFailedException;

/**
 * PostValidator class
 *
 * This class validates data from untrusted $_POST
 *
 * $_REQUEST will be cleared automatically as this array should not  be used.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Data
 */
class PostValidator extends Validator
{
    /**
     * Internal $_POST array until validation has been completed
     *
     * @var array|null $post
     */
    public static ?array $post = null;



    /**
     * Validator constructor.
     *
     * @note This class will purge the $_REQUEST array as this array contains a mix of $_GET and $_POST variables which
     *       should never be used
     *
     * @note Keys that do not exist in $data that are validated will automatically be created
     * @note Keys in $data that are not validated will automatically be removed
     *
     * @param Validator|null $parent If specified, this is actually a child validator to the specified parent
     */
    public function __construct(?Validator $parent = null) {
        $this->construct($parent, static::$post);
    }



    /**
     * Returns a new $_POST data Validator object
     *
     * @param Validator|null $parent
     * @return static
     */
    public static function new(?Validator $parent = null): static
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
     * Validate GET data and liberate GET data if all went well.
     *
     * @return static
     */
    public function validate(): static
    {
        try {
            parent::validate();
            $this->liberateData();
        } catch (ValidationFailedException $e) {
            // Failed data will have been filtered, liberate data!
            $this->liberateData();
            throw $e;
        }

        return $this;
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
     * Gives free and full access to $_POST data, now that it has been validated
     *
     * @return void
     */
    protected function liberateData(): void
    {
        global $_POST;
        $_POST = static::$post;
        static::$post = null;
    }
}