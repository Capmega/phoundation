<?php

namespace Phoundation\Web\Http\Html\Components;

use Phoundation\Web\Http\Html\Elements\ElementsBlock;
use Phoundation\Web\Http\Url;



/**
 * Menu class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation/Web
 */
abstract class Menu extends ElementsBlock
{
    /**
     * Set the menu source and ensure all URL's are absolute
     *
     * @param array|null $source
     * @return $this
     */
    public function setSource(?array $source): static
    {
        $source = $this->makeUrlsAbsolute($source);
        return parent::setSource($source);
    }


    /**
     * Recursively make all "url" keys absolute URL's, recursing into "menu" keys
     */
    public function makeUrlsAbsolute(array $source): array
    {
        // Ensure all URL's are absolute
        foreach ($source as $label => &$entry) {
            if (is_string($entry)) {
                $entry = ['url' => $entry];
            }

            if (array_key_exists('url', $entry)) {
                $entry['url'] = Url::build($entry['url'])->www();
            }

            if (array_key_exists('menu', $entry)) {
                // Recurse
                $entry['menu'] = $this->makeUrlsAbsolute($entry['menu']);
            }
        }

        unset($entry);
        return $source;
    }
}