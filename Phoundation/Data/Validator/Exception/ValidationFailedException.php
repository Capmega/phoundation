<?php

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

declare(strict_types=1);

namespace Phoundation\Data\Validator\Exception;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\TraitSourceObjectClass;
use Phoundation\Data\Validator\Exception\Interfaces\ValidationFailedExceptionInterface;
use Phoundation\Utils\Config;
use Throwable;

class ValidationFailedException extends ValidatorException implements ValidationFailedExceptionInterface
{
    use TraitSourceObjectClass {
        setSourceObjectClass as protected __setSourceObjectClass;
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
     * Sets the source object class name used to translate validation failed fields to human-readable labels
     *
     * @param string|null $source_object_class
     *
     * @return $this
     */
    public function setSourceObjectClass(?string $source_object_class): static
    {
        $this->__setSourceObjectClass($source_object_class);
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
        // Tracks if this function is already processing to avoid endless loops
        static $processing;

        if ($processing) {
            // We've entered an endless loop!
            Log::warning(tr('Failed to apply labels to validation exception keys, creating the source object class ":class" caused another ValidationFailedException', [
                ':class' => $this->source_object_class
            ]));
            return;
        }

        $processing = true;
        $failures   = $this->getDataKey('failures');

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

        $processing = false;
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
