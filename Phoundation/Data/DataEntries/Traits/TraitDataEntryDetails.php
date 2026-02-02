<?php

/**
 * Trait TraitDataEntryDetails
 *
 * This trait contains methods for DataEntry objects that require a details
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Security\Incidents\Incident;
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
    public function getDetails(): ?array
    {
        try {
            return Json::decode($this->getTypesafe('string', 'details'));

        } catch (JsonException $e) {
            Incident::new()
                    ->setTitle(ts('Failed to decode details because of following exception'))
                    ->setBody(ts('NOTE: This is due to DataEntry::setDetails() JSON encoding incoming arrays automatically, but when reading from DB, it reads strings, it gets messy and a better solution must be found'))
                    ->setException($e)
                    ->setLog(ENVIRONMENT === 'production' ? 10 : 4)
                    ->setNotifyRoles('developer')
                    ->save();

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
            // Specified details will be a JSON string at loading time as it comes from the DB
            return $this->set($details, 'details');
        }

        if ($details) {
            if (!is_array($details)) {
                $details = [$details];
            }

            $details = Json::encode($details);
        }

        return $this->set(get_null($details), 'details');
    }


    /**
     * Adds the specified details to the existing details
     *
     * @param Stringable|ArrayableInterface|array|string|null $details
     * @param string|null                                     $key
     *
     * @return static
     */
    public function addDetails(Stringable|ArrayableInterface|array|string|null $details, ?string $key = null): static
    {
        $current = $this->getDetails() ?? [];

        if (is_array($details) and $key === null) {
            foreach ($details as &$value) {
                // Do not store ArrayableInterface or Stringable objects, get their sources instead
                $value = $this->getDetailsSource($value);
            }

            $current = array_replace($details, $current);

        } else {
            $current[$key] = $this->getDetailsSource($details);
        }

        return $this->setDetails($current);
    }


    /**
     * Returns either the specified value, or its source
     *
     * @param mixed $value
     *
     * @return mixed
     */
    protected function getDetailsSource(mixed $value): mixed
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
