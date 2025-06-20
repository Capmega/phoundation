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
use Throwable;


class ValidationFailedException extends ValidatorException
{
    use TraitDataDataEntry {
        setDataEntryObject as protected __setDataEntry;
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
    }


    public function addData(mixed $data, ?string $key = null): static
    {
        parent::addData($data, $key);

        // Automatically log every validation exception IF:
        // * The system is not in booting up state
        //
        // AND one of the following:
        //
        // * Verbose mode is on
        // * Security configuration says to log validation exceptions
        // * Debug configuration says to log all exceptions
        if (!Core::inBootState() and (Log::getVerbose() or config()->getBoolean('security.validation.failures.log', true) or config()->getBoolean('debug.exceptions.log.auto.enabled', false))) {
            // Automatically log validation failures, but only once!
            if (empty($previous)) {
                Log::warning($this, PLATFORM_CLI ? 10 : Log::getThreshold());
            }
        }

        return $this;
    }


    /**
     * Sets the source data entry object used to translate validation failed fields to human-readable labels
     *
     * @param DataEntryInterface|null $o_data_entry
     *
     * @return static
     */
    public function setDataEntryObject(?DataEntryInterface $o_data_entry): static
    {
        $this->__setDataEntry($o_data_entry);
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
            Log::warning(ts('Failed to apply labels to validation exception keys, creating the source object class ":class" caused another ValidationFailedException', [
                ':class' => $this->o_data_entry::class
            ]));
            return;
        }

        $processing = true;

        // Apply the data entry definition labels to the data
        if ($this->getDataKey('failures') and $this->o_data_entry) {
            // Create a temporary data entry object to get its definitions.
            $o_definitions = $this->o_data_entry->getDefinitionsObject();

            // Create a new exception data array with labels instead of keys
            foreach ($this->getDataKey('failures') as $key => $failure) {
                $label   = $o_definitions->get($o_definitions->removeColumnPrefix($key))->getLabel() ?? $key;
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
        $failures = isset_get($this->data['failures'], []);

        if (empty($failures)) {
            $failures = [$this->getMessage()];
        }

        return $failures;
    }
}
