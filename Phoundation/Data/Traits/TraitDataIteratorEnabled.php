<?php

/**
 * Trait TraitDataEnableds
 *
 * This trait adds support for an Iterator that manages a list of enableds
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
use Stringable;


trait TraitDataIteratorEnabled
{
    /**
     * Tracks the enableds iterator
     *
     * @var IteratorInterface $_enableds
     */
    protected IteratorInterface $_enableds;


    /**
     * Returns the enableds iterator
     *
     * @return IteratorInterface
     */
    public function getEnabledsObject(): IteratorInterface
    {
        if (empty($this->_enableds)) {
            $this->_enableds = new Iterator();
        }

        return $this->_enableds;
    }


    /**
     * Sets the enableds iterator
     *
     * @param IteratorInterface|array $_enableds
     *
     * @return static
     */
    public function setEnabledsObject(IteratorInterface|array $_enableds): static
    {
        $this->_enableds = new Iterator($_enableds);
        return $this;
    }


    /**
     * Adds the specified enableds iterator
     *
     * @param IteratorInterface|array $_enableds
     *
     * @return static
     */
    public function addEnabledsObject(IteratorInterface|array $_enableds): static
    {
        $this->getEnabledsObject();

        foreach ($_enableds as $key => $value) {
            $this->_enableds->add($value, $key);
        };

        return $this;
    }


    /**
     * Returns the actual enabled for the specified enabled key
     *
     * @param string    $key
     * @param bool      $exception
     * @param bool|null $default
     *
     * @return bool
     */
    public function getEnabled(string $key, bool $exception = false, ?bool $default = null): bool
    {
        return (bool) ($this->getEnabledsObject()->get($key, exception: $exception) ?? $default);
    }


    /**
     * Sets the actual enabled for the specified enabled key
     *
     * @param bool        $enabled
     * @param string|null $key
     *
     * @return static
     */
    public function setEnabled(bool $enabled, ?string $key = null): static
    {
        $key = get_null($key);

        $this->getEnabledsObject()->set($enabled, $key ?? $enabled);
        return $this;
    }
}
