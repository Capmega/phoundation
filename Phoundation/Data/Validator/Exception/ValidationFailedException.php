<?php

/**
 * Class ValidationFailedException
 *
 * This exception is thrown when a validator found validation failures
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Validator\Exception;

use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Data\Traits\TraitDataDataEntry;
use Phoundation\Data\Validator\Exception\Interfaces\ValidationFailedExceptionInterface;
use Throwable;


class ValidationFailedException extends ValidatorException implements ValidationFailedExceptionInterface
{
    use TraitDataDataEntry {
        setDataEntry as protected __setDataEntry;
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

        if (!Core::inBootState() and config()->getBoolean('security.validation.failures.log', true) and !config()->getBoolean('debug.exceptions.log.auto.enabled', false)) {
            // Automatically log validation failures, but only once!
            if (empty($previous)) {
                Log::warning($this);
            }
        }
    }


    /**
     * Sets the source data entry object used to translate validation failed fields to human-readable labels
     *
     * @param DataEntryInterface|null $data_entry
     *
     * @return static
     */
    public function setDataEntry(?DataEntryInterface $data_entry): static
    {
        $this->__setDataEntry($data_entry);
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
                ':class' => $this->data_entry::class
            ]));
            return;
        }

        $processing = true;

        // Apply the data entry definition labels to the data
        if ($this->getDataKey('failures') and $this->data_entry) {
            // Create a temporary data entry object to get its definitions.
            $definitions = $this->data_entry->getDefinitionsObject();

            // Create a new exception data array with labels instead of keys
            foreach ($this->getDataKey('failures') as $key => $failure) {
                $label   = $definitions->get($definitions->removeColumnPrefix($key))->getLabel() ?? $key;
                $message = str_replace('"' . $key . '"', '"' . $label . '"', $failure['message']);

                $this->data['failures'][$key]['label']   = $label;
                $this->data['failures'][$key]['message'] = $message;
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


    /**
     * Returns the validation failures that are stored with this object
     *
     * @return array
     */
    public function getFailures(): array
    {
        return isset_get($this->data['failures'], []);
    }
}
