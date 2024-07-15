<?php

declare(strict_types=1);

namespace Phoundation\Data\Validator\Exception;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\TraitSourceObjectClass;
use Phoundation\Data\Validator\Exception\Interfaces\ValidationFailedExceptionInterface;
use Phoundation\Utils\Config;
use Throwable;

/**
 * Class ValidationFailedException
 *
 * This exception is thrown when a validator found validation failures
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
class ValidationFailedException extends ValidatorException implements ValidationFailedExceptionInterface
{
    use TraitSourceObjectClass {
        setSourceObjectClass as protected __setSourceobjectClass;
    }

    /**
     * Class ValidationFailedException constructor
     *
     * @param Throwable|array|string|null $messages
     * @param Throwable|null              $previous
     */
    public function __construct(Throwable|array|string|null $messages, ?Throwable $previous = null)
    {
        parent::__construct($messages, $previous);
        $this->makeWarning();

        if (Config::getBoolean('security.validation.failures.log', true)) {
            // Automatically log validation failures, but only once!
            if (empty($previous)) {
                Log::warning($this);
            }
        }
    }


    /**
     * Sets the data entry object used to translate validation failed fields to human readable labels
     *
     * @param string|null $data_entry_class
     *
     * @return $this
     */
    public function setSourceObjectClass(?string $data_entry_class): static
    {
        $this->__setSourceobjectClass($data_entry_class);
        $this->applyLabels();

        return $this;
    }


    /**
     * Applies labels to keys to improve validation exception labels
     *
     * @return void
     */
    protected function applyLabels(): void
    {
        $failures = $this->getDataKey('failures');

        // Apply the data entry definition labels to the data
        if ($this->source_object_class and $failures) {
            // Create a temporary data entry object to get its definitions.
            $data_entry_class = new $this->source_object_class();
            $definitions      = $data_entry_class->getDefinitionsObject();
            $data             = $failures;

            $this->data['failures'] = [];

            // Create a new exception data array with labels instead of keys
            foreach ($data as $key => $value) {
                $label = $definitions->get($key)->getLabel() ?? $key;
                $value = str_replace('"' . $key . '"', '"' . $label . '"', $value);

                $this->data['failures'][$label] = $value;
            }
        }
    }


    /**
     * @inheritDoc
     */
    public function setData(mixed $data): static
    {
        parent::setData($data);
        $this->applyLabels();

        return $this;
    }
}
