<?php

declare(strict_types=1);

namespace Phoundation\Data\Validator\Exception;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\TraitDataDataEntryClass;
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
    use TraitDataDataEntryClass {
        setDataEntryClass as setDataEntryClassDirect;
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
            // Automatically log validation failures
            Log::warning($this);
        }
    }


    /**
     * Sets the data entry object used to translate validation failed fields to human readable labels
     *
     * @param string|null $data_entry_class
     *
     * @return $this
     */
    public function setDataEntryClass(?string $data_entry_class): static
    {
        $this->setDataEntryClassDirect($data_entry_class);
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
        // Apply the data entry definition labels to the data
        if ($this->data_entry_class and $this->data) {
            // Create a temporary data entry object to get its definitions.
            $data_entry_class = new $this->data_entry_class();
            $definitions      = $data_entry_class->getDefinitionsObject();
            $data             = $this->data;
            $this->data       = [];

            // Create a new exception data array with labels instead of keys
            foreach ($data as $key => $value) {
                $label = $definitions->get($key)->getLabel() ?? $key;
                $value = str_replace($key, $label, $value);

                $this->data[$label] = $value;
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
