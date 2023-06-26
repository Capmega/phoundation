<?php

declare(strict_types=1);

namespace Phoundation\Data\Validator;

use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;


/**
 * ArrayValidator class
 *
 * This class validates data for the specified array
 *
 * $_REQUEST will be cleared automatically as this array should not  be used.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Data
 */
class ArrayValidator extends Validator
{
    /**
     * Validator constructor.
     *
     * @note Keys that do not exist in $data that are validated will automatically be created
     * @note Keys in $data that are not validated will automatically be removed
     *
     * @param array $source The data array that must be validated.
     * @param ValidatorInterface|null $parent If specified, this is actually a child validator to the specified parent
     */
    public function __construct(array $source = [], ?ValidatorInterface $parent = null) {
        $this->construct($parent, $source);
    }


    /**
     * Returns a new array data Validator object
     *
     * @param array $source
     * @param ValidatorInterface|null $parent
     * @return static
     */
    public static function new(array $source, ?ValidatorInterface $parent = null): static
    {
        return new static($source, $parent);
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
     * Force a return of all POST data without check
     *
     * @return array|null
     */
    public function forceRead(): ?array
    {
        Log::warning(tr('Forceably returned all $array data without data validation!'));
        return $this->source;
    }


    /**
     * Force a return of a single POST key value
     *
     * @return array
     */
    public function forceReadKey(string $key): mixed
    {
        Log::warning(tr('Forceably returned $array[:key] without data validation!', [':key' => $key]));
        return isset_get($this->source[$key]);
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

        if (count($this->selected_fields) === count($this->source)) {
            return $this;
        }

        $fields = [];
        $post   = array_keys($this->source);

        foreach ($post as $field) {
            if (!in_array($field, $this->selected_fields)) {
                $fields[]   = $field;
                $messages[] = tr('Unknown field ":field" encountered', [
                    ':field' => $field
                ]);
            }
        }

        throw ValidationFailedException::new(tr('Unknown ARRAY fields ":fields" encountered', [
            ':fields' => Strings::force($fields, ', ')
        ]))->setData($messages)->makeWarning()->log();
    }
}