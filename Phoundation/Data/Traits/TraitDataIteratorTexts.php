<?php

/**
 * Trait TraitDataTexts
 *
 * This trait adds support for an Iterator that manages a list of texts
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


trait TraitDataIteratorTexts
{
    /**
     * Tracks the texts iterator
     *
     * @var IteratorInterface $_texts
     */
    protected IteratorInterface $_texts;


    /**
     * Returns the texts iterator
     *
     * @return IteratorInterface
     */
    public function getTextsObject(): IteratorInterface
    {
        if (empty($this->_texts)) {
            $this->_texts = new Iterator();
        }

        return $this->_texts;
    }


    /**
     * Sets the texts iterator
     *
     * @param IteratorInterface|array $_texts
     *
     * @return TraitDataIteratorTexts
     */
    public function setTextsObject(IteratorInterface|array $_texts): static
    {
        $this->_texts = new Iterator($_texts);
        return $this;
    }


    /**
     * Adds the specified texts iterator
     *
     * @param IteratorInterface|array $_texts
     *
     * @return static
     */
    public function addTextsObject(IteratorInterface|array $_texts): static
    {
        $this->getTextsObject();

        foreach ($_texts as $key => $value) {
            $this->_texts->add($value, $key);
        };

        return $this;
    }


    /**
     * Returns the actual text for the specified text key
     *
     * @param string      $key
     * @param bool        $exception
     * @param string|null $default
     *
     * @return string|null
     */
    public function getText(string $key, bool $exception = false, ?string $default = null): ?string
    {
        return (($this->getTextsObject()->get($key, exception: $exception) ?? $default) ?? $key);
    }


    /**
     * Sets the actual text for the specified text key
     *
     * @param \Stringable|string|null $text
     * @param string|null             $key
     *
     * @return static
     */
    public function setText(Stringable|string|null $text, ?string $key = null): static
    {
        $key = get_null($key);

        $this->getTextsObject()->set((string) $text, $key ?? (string) $text);
        return $this;
    }
}
