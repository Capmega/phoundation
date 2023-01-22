<?php

namespace Phoundation\Web\Http\Html\Components;

use Phoundation\Core\Session;
use Phoundation\Web\Http\UrlBuilder;



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
        $source = $this->filterNoAccess($source);
        $source = $this->makeUrlsAbsolute($source);
        return parent::setSource($source);
    }



    /**
     * Filter menu entries where this user has no access to
     *
     * @param array $source
     * @return array
     */
    protected function filterNoAccess(array $source): array
    {
        foreach ($source as $label => &$entry) {
            if (!array_key_exists('rights', $entry)) {
                // No rights required
                continue;
            }

            if (Session::getUser()->hasAllRights($entry['rights'])) {
                if (array_key_exists('menu', $entry)) {
                    // Recurse
                    $entry['menu'] = $this->makeUrlsAbsolute($entry['menu']);

                    if ($entry['menu']) {
                        // The entire sub menu is gone, remove this empty top entry too
                        continue;
                    }
                }
            }

            unset($source[$label]);
        }

        return $source;
    }



    /**
     * Recursively make all "url" keys absolute URL's, recurseing into "menu" keys
     */
    protected function makeUrlsAbsolute(array $source): array
    {
        // Ensure all URL's are absolute
        foreach ($source as &$entry) {
            if (is_string($entry)) {
                $entry = ['url' => $entry];
            }

            if (array_key_exists('url', $entry)) {
                $entry['url'] = UrlBuilder::www($entry['url']);
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