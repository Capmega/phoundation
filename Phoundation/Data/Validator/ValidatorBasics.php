<?php

namespace Phoundation\Data\Validator;

use Phoundation\Core\Log;
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
     * The human-readable label for the current field that is being validated
     *
     * @var string|int|null $selected_label
     */
    protected string|int|null $selected_label = null;

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
     * If specified, this is a child element to a parent.
     *
     * The ->validate() call will NOT cause an exception but instead will send the failures list to the parent and then
     * return the parent element
     *
     * @var Validator|null
     */
    protected ?Validator $parent = null;

    /**
     * If set, all label errors will show the parent name as well
     *
     * @var string|null
     */
    protected ?string $parent_label = null;

    /**
     * Child Validator object for sub array elements. When validating the final result, the results from all the child
     * validators will be added to the result as well
     *
     * @var array $children
     */
    protected array $children = [];



    /**
     * Validator constructor.
     *
     * @note Keys that do not exist in $data that are validated will automatically be created
     * @note Keys in $data that are not validated will automatically be removed
     * @param array|null $source The data array that must be validated.
     * @param Validator|null $parent If specified, this is actually a child validator to the specified parent
     */
    public function __construct(?array &$source = [], ?Validator $parent = null) {
        // Ensure the source is an array
        if ($source === null) {
            $source = [];
        }

        $this->source = &$source;
        $this->parent = $parent;
    }



    /**
     * Returns a new array validator
     *
     * @param array $source
     * @return Validator
     */
    public static function array(array &$source): Validator
    {
        return new Validator($source);
    }



    /**
     * Returns a new GET array validator
     *
     * @return Validator
     */
    public static function get(): Validator
    {
        // Clear the $_REQUEST array, it should never be used
        $_REQUEST = [];

        return new Validator($_GET);
    }



    /**
     * Returns a new POST array validator
     *
     * @return Validator
     */
    public static function post(): Validator
    {
        // Clear the $_REQUEST array, it should never be used
        $_REQUEST = [];

        return new Validator($_POST);
    }


    /**
     * Returns a new file validator
     *
     * @param string $file
     * @return FileValidator
     */
    public static function file(string $file): FileValidator
    {
        return new FileValidator($file);
    }



    /**
     * Returns the parent label with the specified name
     *
     * @return string|null
     */
    public function getParentLabel(): ?string
    {
        return $this->parent_label;
    }



    /**
     * Sets the parent label with the specified name
     *
     * @param string|null $label
     * @return void
     */
    public function setParentLabel(?string $label): void
    {
        $this->parent_label = $label;
    }


    /**
     * Selects the specified key within the array that we are validating
     *
     * @param int|string $field The array key (or HTML form field) that needs to be validated / sanitized
     * @param string|null $label The pretty label for the field. If the field would be pwd, the label could be password
     * @return Validator
     */
    public function select(int|string $field, ?string $label = null): Validator
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

        if (!$label) {
            // The label defaults to the field
            $label = $field;
        }

        // Does the field exist in the source? If not, initialize it with NULL to be able to process it
        if (!array_key_exists($field, $this->source)) {
            $this->source[$field] = null;
        }

show($field);
show($this->parent_label);
show($this->source);

        // Select the field
        $this->selected_label = $label;
        $this->selected_field = $field;
        $this->selected_fields[] = $field;
        $this->selected_value = $this->source[$field];

        return $this;
    }



    /**
     * Recurse into a sub array and return another validator object for that sub array
     *
     * @return Validator
     */
    public function recurse(): Validator
    {
        $this->ensureSelected();

        // Create a new Validator object from the current value. If the current value is not an array (oopsie) then just
        // send in an empty array so that the Validation chain won't break
        if (!is_array($this->selected_value)) {
            $array = [];
            $child = new Validator($array, $this);
        } else {
            $child = new Validator($this->selected_value, $this);
        }

        $child->setParentLabel($this->selected_field);
        $this->children[$this->selected_field] = $child;
        return $child;
    }



    /**
     * Called at the end of defining all validation rules.
     *
     * This method will check the failures array and if any failures were registered, it will throw an exception
     *
     * @return Validator|null
     */
    public function validate(): ?Validator
    {
        if ($this->parent) {
            unset($this->source);
            return $this->parent;
        }

        if ($this->failures) {
            Log::warning(tr('Array validation ended with the following failures'), 3);
            Log::warning($this->failures, 2);

            throw new ValidationFailedException(tr('Validation of the array failed with the registered failures'), $this->failures);
        }

        unset($this->source);
        return null;
    }



    /**
     * Add the specified failure message to the failures list
     *
     * @param string $field
     * @param string $failure
     * @return void
     */
    public function addFailure(string $field, string $failure): void
    {
        $this->failures[$field] = $failure;
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