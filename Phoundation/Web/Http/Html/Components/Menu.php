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
    public function setSource(?array $source): static
    {
        // Ensure all URL's are absolute
        foreach ($source as $label => &$entry) {
            if (is_string($entry)) {
                $entry = ['url' => $entry];
            }

            $entry['url'] = Url::build($entry['url'])->www();
        }

        unset($entry);

        return parent::setSource($source);
    }
}