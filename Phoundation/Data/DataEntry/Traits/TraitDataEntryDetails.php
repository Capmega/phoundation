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

use Phoundation\Core\Log\Log;
use Phoundation\Utils\Exception\JsonException;
use Phoundation\Utils\Json;

trait TraitDataEntryDetails
{
    /**
     * Returns the details for this object
     *
     * @return array|string|null
     */
    public function getDetails(): array|string|null
    {
        try {
            return Json::decode($this->getTypesafe('string', 'details'));

        } catch (JsonException $e) {
            Log::warning(tr('Failed to decode details because of following exception'));
            Log::warning(tr('NOTE: This is due to DataEntry::setDetails() JSON encoding incoming arrays automatically, but when reading from DB, it reads strings, it gets messy and a better solution must be found'));
            Log::error($e);

            return $this->getTypesafe('string', 'details');
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
        if (is_array($details)) {
            $details = Json::encode($details);
        }

        return $this->set($details, 'details');
    }
}
