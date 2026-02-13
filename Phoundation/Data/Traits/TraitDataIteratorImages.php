<?php

/**
 * Trait TraitDataIteratorImages
 *
 * This trait adds support for an Iterator that manages a list of images
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Web\Http\Interfaces\UrlInterface;
use Stringable;


trait TraitDataIteratorImages
{
    /**
     * Tracks the images iterator
     *
     * @var IteratorInterface $_images
     */
    protected IteratorInterface $_images;


    /**
     * Returns the images iterator
     *
     * @return IteratorInterface
     */
    public function getImagesObject(): IteratorInterface
    {
        if (empty($this->_images)) {
            $this->_images = new Iterator();
        }

        return $this->_images;
    }


    /**
     * Returns the images iterator
     *
     * @param IteratorInterface|array $_images
     *
     * @return static
     */
    public function setImagesObject(IteratorInterface|array $_images): static
    {
        $this->_images = new Iterator($_images);
        return $this;
    }


    /**
     * Adds the specified images iterator
     *
     * @param IteratorInterface|array $_images
     *
     * @return static
     */
    public function addImagesObject(IteratorInterface|array $_images): static
    {
        $this->getImagesObject();

        foreach ($_images as $key => $value) {
            $this->_images->add($value, $key);
        };

        return $this;
    }


    /**
     * Returns the actual image for the specified image key
     *
     * @param Stringable|string      $key
     * @param bool                   $exception
     * @param Stringable|string|null $default
     *
     * @return UrlInterface|string|null
     */
    public function getImage(Stringable|string $key, bool $exception = false, Stringable|string|null $default = null): UrlInterface|string|null
    {
        return $this->getImagesObject()->get($key, exception: $exception) ?? $default;
    }


    /**
     * Sets the actual image for the specified image key
     *
     * @param UrlInterface|string|null $image
     * @param string|null            $key
     *
     * @return static
     */
    public function setImage(UrlInterface|string|null $image, ?string $key = null): static
    {
        $key = get_null($key);

        $this->getImagesObject()->set((string) $image, $key ?? (string) $image);
        return $this;
    }
}
