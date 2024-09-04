<?php

/**
 * Trait TraitDataEntryDetails
 *
 * This trait contains methods for DataEntry objects that require a details
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Utils\Exception\JsonException;
use Phoundation\Utils\Json;
use Stringable;


trait TraitDataEntryDetails
{
    /**
     * Returns the details for this object
     *
     * @return array|null
     */
    public function getDetails(): array|null
    {
        try {
            return Json::decode($this->getTypesafe('string', 'details'));

        } catch (JsonException $e) {
            Log::warning(tr('Failed to decode details because of following exception'));
            Log::warning(tr('NOTE: This is due to DataEntry::setDetails() JSON encoding incoming arrays automatically, but when reading from DB, it reads strings, it gets messy and a better solution must be found'));
            Log::error($e);

            return [$this->getTypesafe('string', 'details')];
        }
    }


    /**
     * Sets the details for this object
     *
     * @param array|string|null $details
     *
     * @return static
     */
    public function setDetails(array|string|null $details): static
    {
        if ($this->is_loading) {
            // Specified details will be a Json string at loading time as it comes from the DB
            return $this->set($details, 'details');
        }

        if (!is_array($details)) {
            $details = [$details];
        }

        $details = Json::encode($details);

        return $this->set($details, 'details');
    }


    /**
     * Adds the specified details to the existing details
     *
     * @param Stringable|ArrayableInterface|array|string|null $details
     * @param string|null                                     $key
     *
     * @return $this
     */
    public function addDetails(Stringable|ArrayableInterface|array|string|null $details, ?string $key = null): static
    {
        $current = $this->getDetails() ?? [];

        if (is_array($details) and $key === null) {
            foreach ($details as &$value) {
                // Don't store ArrayableInterface or Stringable objects, get their sources instead
                $value = $this->getSourceData($value);
            }

            $current = array_replace($details, $current);

        } else {
            $current[$key] = $this->getSourceData($details);
        }

        return $this->setDetails($current);
    }


    /**
     * Returns either the specified value, or its source
     *
     * @param Stringable|ArrayableInterface|array|string|null $value
     *
     * @return array|string|null
     */
    protected function getSourceData(Stringable|ArrayableInterface|array|string|null $value): array|string|null
    {
        if ($value instanceof ArrayableInterface) {
            return $value->__toArray();
        }

        if ($value instanceof Stringable) {
            return $value->__toString();
        }

        return $value;
    }
}
