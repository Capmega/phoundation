<?php

namespace Plugins\Mdb;



use Phoundation\Content\Images\Image;

/**
 * Phoundation template class
 *
 * This class is an example template for your website
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\Mdb
 */
class ProfileImage extends ImageMenu
{
    /**
     * ImageMenu class constructor
     *
     * @param Image|string|null $image
     * @param array|null $menu
     */
    public function __construct(Image|string|null $image, ?array $menu)
    {
        if (!is_object($image)) {
            if (!$image) {
                // Default to default profile image
                $image = 'profiles/default.png';
            }
        }

        parent::__construct($image, $menu);
    }
}