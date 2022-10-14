<?php

namespace Phoundation\Data\Validator;



use Phoundation\Data\Exception\KeyAlreadySelectedException;

/**
 * Validator class
 *
 * This class validates data from untrusted arrays
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Utils
 */
class Validator
{
    /**
     * The source array that we will be validating
     *
     * @var array|null $source
     */
    protected ?array $source = null;

    /**
     * The current key that is being validated
     *
     * @var string|null $key
     */
    protected string|null $key = null;

    /**
     * The keys that have been selected to be validated. All keys found in the $source array that are not in this array
     * will be removed
     *
     * @var array $keys
     */
    protected array $keys = [];



    /**
     * Validator constructor.
     *
     * @note Keys that do not exist in $data that are validated will automatically be created
     * @note Keys in $data that are not validated will automatically be removed
     * @param array|null $data The data array that must be validated.
     */
    public function __construct(?array &$data = []) {
        // Ensure we have an array
        if ($data === null) {
            $data = [];
        }

        $this->data = &$data;
    }



    /**
     * Array validator
     */
    public static function array(array $array): Validator
    {
        return new Validator($array);
    }



    /**
     * GET array validator
     */
    public static function get(array $array): Validator
    {
        // Clear the $_REQUEST array, it should never be used
        $_REQUEST = [];

        return new Validator($_GET);
    }



    /**
     * POST array validator
     */
    public static function post(): Validator
    {
        // Clear the $_REQUEST array, it should never be used
        $_REQUEST = [];

        return new Validator($_POST);
    }



    /**
     * Selects the specified key within the array that we are validating
     *
     * @param int|string $key
     * @return Validator
     */
    public function select(int|string $key): Validator
    {
        if (in_array($key, $this->keys)) {
            throw new KeyAlreadySelectedException(tr('The specified key ":key" has already been selected before', [':key' => $key]));
        }

        $this->key = $key;
        $this->keys[] = $key;

        return $this;
    }



    /**
     * Validates the datatype for the selected array key
     *
     * This method ensures that the specified array key is a string
     *
     * @return Validator
     */
    public function isString(): Validator
    {
        $this->ensureSelected();

        foreach ($this->source as $value) {
            if (!is_string($value)) {
                $this->addFailure(tr('The field ":key" must be a string'));
            }
        }

        return $this;
    }



    /**
     * Add the specified failure message to the failures list
     *
     * @return void
     */
    public function addFailure(string $failure): void
    {
        $this->failures[] = $failure;
    }



    /**
     * Ensure that a key has been selected
     *
     * @return void
     */
    protected function ensureSelected(): void
    {
        if (empty($this->key)) {
            throw new NoKeySelectedException(tr('The specified key ":key" has already been selected before', [':key' => $key]));
        }
    }
}