<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Validator;
use Phoundation\Web\Http\UrlBuilder;
use ReturnTypeWillChange;
use Stringable;


/**
 * Class FilterForm
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class FilterForm extends DataEntryForm
{
    /**
     * FilterForm class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        parent::__construct($content);
        $this->setId('filters');
        $this->useForm(true)->getForm()->setMethod('GET')->setAction(UrlBuilder::getWww());
    }


    /**
     * Returns value for the specified key
     *
     * @note This is the standard Iterator::getSourceKey, but here $exception is by default false
     *
     * @param Stringable|string|float|int $key
     * @param bool $exception
     * @return mixed
     */
    #[ReturnTypeWillChange] public function getSourceValue(Stringable|string|float|int $key, bool $exception = false): mixed
    {
        return parent::getSourceValue($key, $exception);
    }


    /**
     * Apply the filters from the Validator
     *
     * @param bool $clear_source
     * @return $this
     */
    public function apply(bool $clear_source = true): static
    {
        $validator = Validator::get();

        // Go over each field and let the field definition do the validation since it knows the specs
        foreach ($this->definitions as $definition) {
//            if ($definition->getReadonly() or $definition->getDisabled()) {
//                // This field cannot be modified and should not be validated, unless its new or has a static value
//                if (!$this->isNew() and !$definition->getValue()) {
//                    $validator->removeSourceKey($definition->getField());
//                    continue;
//                }
//            }
//
            $definition->validate($validator, null);
        }

        try {
            // Execute the validate method to get the results of the validation
            $this->source = $validator->validate($clear_source);

        } catch (ValidationFailedException $e) {
            // Add the DataEntry object type to the exception message
            throw $e->setMessage('(' . get_class($this) . ') ' . $e->getMessage());
        }

        return $this;
    }
}
