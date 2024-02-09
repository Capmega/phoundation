<?php

declare(strict_types=1);

namespace Phoundation\Web\Routing;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Iterator;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Routing\Interfaces\MapInterface;
use Phoundation\Web\Routing\Interfaces\MappingInterface;


/**
 * Class Mapping
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Mapping implements MappingInterface
{
    /**
     * The maps source
     *
     * @var array $source
     */
    protected array $source = [];


    /**
     * Returns a new Mapping object
     *
     * @return static
     */
    public static function new(): static
    {
        return new static();
    }


    /**
     * Returns source for all mappings
     *
     * @return array
     */
    public function getSource(): array
    {
        return $this->source;
    }


    /**
     * Returns the map for the specified regex
     *
     * @param string $regex
     * @return MapInterface
     */
    public function getMap(string $regex): MapInterface
    {
        if (!array_key_exists($regex, $this->source)) {
            throw new OutOfBoundsException(tr('Cannot return MapInterface object for the specified regex ":regex", the regex does not exist', [
                ':regex' => $regex
            ]));
        }

        return $this->source[$regex];
    }


    /**
     * Sets source for these mappings
     *
     * @param array $source
     * @return static
     */
    public function setSource(array $source): static
    {
        if (!$source) {
            throw new OutOfBoundsException(tr('Empty source specified for which this mapping will be applied'));
        }

        $this->source = $source;
        return $this;
    }


    /**
     * Adds a new map
     *
     * @param string $regex
     * @param MapInterface ...$maps
     * @return $this
     */
    public function add(string $regex, MapInterface ...$maps): static
    {
        if (array_key_exists($regex, $this->source)) {
            throw new OutOfBoundsException(tr('Cannot register regex ":regex", it has already been registered', [
                ':regex' => $regex
            ]));
        }

        // Register the maps
        $this->source[$regex] = [];

        foreach ($maps as $map) {
            $this->source[$regex][$map->getValue()] = $map;
        }

        return $this;
    }


    /**
     * Translates the given URL with the internal map
     *
     * @param $url
     * @return string
     */
    public function apply($url): string
    {
        if (!$this->source) {
            // No mappings to be applied
            return $url;
        }

        // Apply all registered mappings
        foreach ($this->source as $regex => $maps) {
            // Apply the regex and see the matching value
            if (preg_match_all($regex, $url, $matches)) {
                if (empty($matches[1][0])) {
                    throw new OutOfBoundsException(tr('URL mapping regex "" resulted in either no $matches[1][0] or an empty value for $matches[1][0]. Please ensure that the mapping regex contains one capturing group'));
                }

                // See if the matching value is registered.
                if (!array_key_exists($matches[1][0], $maps)) {
                    Log::warning(tr('No URL mappings found for regex ":regex" value ":value"', [
                        ':regex' => $regex,
                        ':value' => $matches[1][0]
                    ]));
                    break;
                }

                // Apply the matching value
                $url = $maps[$matches[1][0]]->apply($url);
            }
        }

        return $url;
    }
}