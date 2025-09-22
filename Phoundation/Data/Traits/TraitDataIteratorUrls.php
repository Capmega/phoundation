<?php

/**
 * Trait TraitDataIteratorUrls
 *
 * This trait adds support for an Iterator that manages a list of urls
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


trait TraitDataIteratorUrls
{
    /**
     * Tracks the urls iterator
     *
     * @var IteratorInterface $o_urls
     */
    protected IteratorInterface $o_urls;


    /**
     * Returns the urls iterator
     *
     * @return IteratorInterface
     */
    public function getUrlsObject(): IteratorInterface
    {
        if (empty($this->o_urls)) {
            $this->o_urls = new Iterator();
        }

        return $this->o_urls;
    }


    /**
     * Returns the urls iterator
     *
     * @param IteratorInterface|array $o_urls
     *
     * @return static
     */
    public function setUrlsObject(IteratorInterface|array $o_urls): static
    {
        $this->o_urls = new Iterator($o_urls);
        return $this;
    }


    /**
     * Adds the specified urls iterator
     *
     * @param IteratorInterface|array $o_urls
     *
     * @return static
     */
    public function addUrlsObject(IteratorInterface|array $o_urls): static
    {
        $this->getUrlsObject();

        foreach ($o_urls as $key => $value) {
            $this->o_urls->add($value, $key);
        };

        return $this;
    }


    /**
     * Returns the actual url for the specified url key
     *
     * @param Stringable|string      $key
     * @param bool                   $exception
     * @param Stringable|string|null $default
     *
     * @return UrlInterface|string|null
     */
    public function getUrl(Stringable|string $key, bool $exception = false, Stringable|string|null $default = null): UrlInterface|string|null
    {
        return $this->getUrlsObject()->get($key, exception: $exception) ?? $default;
    }


    /**
     * Sets the actual url for the specified url key
     *
     * @param UrlInterface|string|null $url
     * @param string|null            $key
     *
     * @return static
     */
    public function setUrl(UrlInterface|string|null $url, ?string $key = null): static
    {
        $key = get_null($key);

        $this->getUrlsObject()->set((string) $url, $key ?? (string) $url);
        return $this;
    }
}
