<?php

declare(strict_types=1);

namespace Phoundation\Web\Routing;

use Phoundation\Core\Log\Log;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Routing\Interfaces\MapInterface;

/**
 * Class Map
 *
 * Maps
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */
class Map implements MapInterface
{
    /**
     * The value on which to apply this map
     *
     * @var string $value
     */
    protected string $value;

    /**
     * The map source
     *
     * @var array|null $source
     */
    protected ?array $source;


    /**
     * Map class constructor
     *
     * @param string     $value
     * @param array|null $map
     */
    public function __construct(string $value, ?array $map)
    {
        $this->set($value);
        $this->setSource($map);
    }


    /**
     * Returns a new Mapping object
     *
     * @param string     $value
     * @param array|null $map
     *
     * @return static
     */
    public static function new(string $value, ?array $map): static
    {
        return new static($value, $map);
    }


    /**
     * Returns source for all mappings
     *
     * @return array|null
     */
    public function getSource(): ?array
    {
        return $this->source;
    }


    /**
     * Sets map source
     *
     * @param array|null $source
     *
     * @return static
     */
    public function setSource(?array $source): static
    {
        if (is_array($source) and empty($source)) {
            throw new OutOfBoundsException(tr('Empty map source specified for value ":value". Specify NULL for source if no mapping is required for the value', [
                ':value' => $this->value,
            ]));
        }
        $this->source = $source;

        return $this;
    }


    /**
     * Returns value for all mappings
     *
     * @return string
     */
    public function get(): string
    {
        return $this->value;
    }


    /**
     * Sets map value
     *
     * @param string $value
     *
     * @return static
     */
    public function set(string $value): static
    {
        if (empty($value)) {
            throw new OutOfBoundsException(tr('No value on which to apply map specified'));
        }
        $this->value = $value;

        return $this;
    }


    /**
     * Translates the given URL with the internal map
     *
     * @param $url
     *
     * @return string
     */
    public function apply($url): string
    {
        if ($this->source) {
            foreach ($this->source as $key => $value) {
                $url = str_replace($key, $value, $url);
            }
            Log::success(tr('Applied URL mapping for value ":value"', [
                ':value' => $this->value,
            ]), 4);
        }

        return $url;
    }
}
