<?php

namespace Phoundation\Data\Validator;

use Phoundation\Data\Exception\KeyAlreadySelectedException;
use Phoundation\Data\Exception\NoKeySelectedException;
use Phoundation\Data\Exception\ValidationFailedException;
use Phoundation\Exception\OutOfBoundsException;



/**
 * ValidatorBasics trait
 *
 * This class contains the basic controls for Validator classes
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Utils
 */
trait ValidatorBasics
{
    /**
     * The source array that we will be validating
     *
     * @var array|null $source
     */
    protected ?array $source = null;

    /**
     * Register for the failures occurred during validations
     *
     * @var array $failures
     */
    protected array $failures = [];

    /**
     * The current field that is being validated
     *
     * @var string|int|null $selected_field
     */
    protected string|int|null $selected_field = null;

    /**
     * The keys that have been selected to be validated. All keys found in the $source array that are not in this array
     * will be removed
     *
     * @var array $selected_fields
     */
    protected array $selected_fields = [];

    /**
     * The value that is selected for testing
     *
     * @var mixed|null $selected_value
     */
    protected mixed $selected_value = null;



    /**
     * Validator constructor.
     *
     * @note Keys that do not exist in $data that are validated will automatically be created
     * @note Keys in $data that are not validated will automatically be removed
     * @param array|null $source The data array that must be validated.
     */
    public function __construct(?array &$source = []) {
        // Ensure the source is an array
        if ($source === null) {
            $source = [];
        }

        $this->source = &$source;
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
     * @param int|string $field
     * @return Validator
     */
    public function select(int|string $field): Validator
    {
        if (!$field) {
            throw new OutOfBoundsException(tr('No field specified'));
        }

        if (in_array($field, $this->selected_fields)) {
            throw new KeyAlreadySelectedException(tr('The specified key ":key" has already been selected before', [':key' => $field]));
        }

        if ($this->source === null) {
            throw new OutOfBoundsException(tr('No source array specified'));
        }

        // Does the field exist in the source? If not, initialize it with NULL to be able to process it
        if (!array_key_exists($field, $this->source)) {
            $this->source[$field] = null;
        }

        // Select the field
        $this->selected_field = $field;
        $this->selected_fields[] = $field;
        $this->selected_value = $this->source[$field];

        return $this;
    }



    /**
     * Called at the end of defining all validation rules.
     *
     * This method will check the failures array and if any failures were registered, it will throw an exception
     *
     * @return void
     */
    public function validate(): void
    {
        if ($this->failures) {
            throw new ValidationFailedException(tr('Validation of the array failed with the registered failures'), $this->failures);
        }
    }



    /**
     * Add the specified failure message to the failures list
     *
     * @param string $failure
     * @return void
     */
    public function addFailure(string $failure): void
    {
        $this->failures[] = $failure;
    }



    /**
     * Returns the list of failures found during validation
     *
     * @return array
     */
    public function getFailures(): array
    {
        return $this->failures;
    }



    /**
     * Ensure that a key has been selected
     *
     * @return void
     */
    protected function ensureSelected(): void
    {
        if (empty($this->selected_field)) {
            throw new NoKeySelectedException(tr('The specified key ":key" has already been selected before', [':key' => $this->selected_field]));
        }
    }
}