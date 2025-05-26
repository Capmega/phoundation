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
     * @param bool $keep_array_format
     *
     * @return array|string|null
     */
    public function getData(bool $keep_array_format = false): array|string|null
    {
        if ($keep_array_format) {
            return $this->getTypesafe('array', 'data');
        }

        try {
            return Json::encode($this->getTypesafe('array', 'data'));

        } catch (JsonException $e) {
            Log::warning(ts('Failed to encode data because of following exception'));
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
            if (is_string($data)) {
                try {
                    $data = Json::decode($data);

                } catch (JsonException $e) {
                    Log::warning(ts('Failed to decode data because of following exception'));
                    Log::error($e);
                    throw $e;
                }
            }
        }

        return $this->set(get_null($data), 'data');
    }
}
