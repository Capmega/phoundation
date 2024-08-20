<?php

/**
 * Class Img
 *
 * This class represents an HTML <img> tag
 *
 * The class can render basic <img src="file"> tags, but on steriods.
 *
 * It will make sure the alt, width, and height attributes are always specified
 *
 * If width and height were specified, it will resize the specified source image to ensure it is pixel perfect size
 *
 * If width and height are not specified, it will fetch width and height from the specified image itself
 *
 * If an external image is specified (an image that is not on this server) it will download the image, and then serve
 * the local version. If this is disabled (either globally from configuration, or this image specifically) it will still
 * attempt to download the image so that it can fetch the width and height.
 *
 * If served locally, it will optimize the image in a variety of ways (depending on configuration) to ensure it always
 * serves optimized images
 *
 * It will generate the new <picture> tags and auto generate all image sizes if the client supports this (see
 * https://webdesign.tutsplus.com/quick-tip-how-to-use-html5-picture-for-responsive-images--cms-21015t for more
 * information)
 *
 * @note: The core implementation of this class is done in ImgCore, this class only contains the constructor and new
 *        methods
 *
 * @see \Phoundation\Web\Html\Components\ImgCore
 * @see \Phoundation\Web\Html\Components\Div
 * @see \Phoundation\Web\Html\Components\Element
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Content\Images\Interfaces\ImageFileInterface;
use Phoundation\Utils\Config;


class Img extends ImgCore
{
    /**
     * Img constructor
     *
     * @param ImageFileInterface|string $src
     * @param string|null               $alt
     */
    public function __construct(ImageFileInterface|string $src, ?string $alt = null)
    {
        parent::___construct();

        if ($src instanceof ImageFileInterface) {
            $src = $src->getSource();
        }

        $this->setSrc($src)
             ->setAlt($alt)
             ->setElement('img')
             ->setLazyLoad(Config::get('web.images.lazy-load', true))
             ->requires_closing_tag = false;
    }


    /**
     * Returns an Img object
     *
     * @param ImageFileInterface|string $src
     * @param string|null               $alt
     *
     * @return static
     */
    public static function new(ImageFileInterface|string $src, ?string $alt = null): static
    {
        return new static($src, $alt);
    }
}
