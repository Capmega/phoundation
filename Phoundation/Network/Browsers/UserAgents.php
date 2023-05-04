<?php

declare(strict_types=1);

namespace Phoundation\Network\Browsers;

use Phoundation\Core\Arrays;


/**
 * Class UserAgents
 *
 * This class manages browser user agents
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Network
 */
class UserAgents
{
    /**
     * Filters to apply to the user agents list
     *
     * @var array
     */
    protected array $filters = [];


    /**
     * Returns a new UserAgents object
     *
     * @return static
     */
    public static function new(): static
    {
        return new static();
    }


    /**
     * Adds a filter
     *
     * @param string $filter
     * @return static
     */
    public function addFilter(string $filter): static
    {
        $this->filters[] = $filter;
        return $this;
    }


    /**
     * Returns all available user agents
     *
     * @todo Store this data in a database somewhere
     * @todo implement filters
     * @return array
     */
    public static function list(): array
    {
        $return = [
            'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322; Alexa Toolbar)',
            'Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.2.1) Gecko/20021204',
            'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; WOW64; Trident/5.0)',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/29.0.1547.57 Safari/537.36'
        ];

        return $return;
    }


    /**
     * Returns a random user agent
     *
     * @return string
     */
    public static function getRandom(): string
    {
        return Arrays::getRandomValue(static::list());
    }
}