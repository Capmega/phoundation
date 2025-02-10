<?php

/**
 * Trait TraitDataEntryData
 *
 * This trait contains methods for DataEntry objects that require a data
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\DataEntries\Traits;

use Phoundation\Core\Log\Log;
use Phoundation\Utils\Exception\JsonException;
use Phoundation\Utils\Json;


trait TraitDataEntryData
{
    /**
     * Returns the data for this object
     *
     * @return array|string|null
     */
    public function getData(): array|string|null
    {
        try {
            return Json::decode($this->getTypesafe('string', 'data'));

        } catch (JsonException $e) {
            Log::warning(tr('Failed to decode data because of following exception'));
            Log::warning(tr('NOTE: This is due to DataEntry::setData() JSON encoding incoming arrays automatically, but when reading from DB, it reads strings, it gets messy and a better solution must be found'));
            Log::error($e);

            return $this->getTypesafe('string', 'data');
        }
    }


    /**
     * Sets the data for this object
     *
     * @param array|string|null $data
     *
     * @return static
     */
    public function setData(array|string|null $data): static
    {
        if ($data) {
            if (is_array($data)) {
                $data = Json::encode($data);
            }
        }

        return $this->set(get_null($data), 'data');
    }
}
