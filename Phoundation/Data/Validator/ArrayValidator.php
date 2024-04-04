<?php

declare(strict_types=1);

namespace Phoundation\Data\Validator;

use Phoundation\Data\Validator\Exception\ValidatorException;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Utils\Strings;


/**
 * ArrayValidator class
 *
 * This class validates data for the specified array
 *
 * $_REQUEST will be cleared automatically as this array should not  be used.
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
class ArrayValidator extends Validator
{
    /**
     * Validator constructor.
     *
     * @note Keys that do not exist in $data that are validated will automatically be created
     * @note Keys in $data that are not validated will automatically be removed
     *
     * @param array &                 $source The data array that must be validated.
     * @param ValidatorInterface|null $parent If specified, this is actually a child validator to the specified parent
     */
    public function __construct(array &$source = [], ?ValidatorInterface $parent = null)
    {
        $this->construct($parent, $source);
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
     * Throws an exception if there are still arguments left in the POST source
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

        throw ValidatorException::new(tr('Unknown ARRAY fields ":fields" encountered', [
            ':fields' => Strings::force($fields, ', '),
        ]))->addData($messages)->makeWarning()->log();
    }

    /**
     * Returns a new array data Validator object
     *
     * @param array                    $source
     * @param ValidatorInterface|null &$parent
     *
     * @return static
     */
    public static function new(array &$source, ?ValidatorInterface $parent = null): static
    {
        return new static($source, $parent);
    }
}
