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
     * @var IteratorInterface $o_texts
     */
    protected IteratorInterface $o_texts;


    /**
     * Returns the texts iterator
     *
     * @return IteratorInterface
     */
    public function getTextsObject(): IteratorInterface
    {
        if (empty($this->o_texts)) {
            $this->o_texts = new Iterator();
        }

        return $this->o_texts;
    }


    /**
     * Sets the texts iterator
     *
     * @param IteratorInterface|array $o_texts
     *
     * @return TraitDataIteratorTexts
     */
    public function setTextsObject(IteratorInterface|array $o_texts): static
    {
        $this->o_texts = new Iterator($o_texts);
        return $this;
    }


    /**
     * Adds the specified texts iterator
     *
     * @param IteratorInterface|array $o_texts
     *
     * @return static
     */
    public function addTextsObject(IteratorInterface|array $o_texts): static
    {
        $this->getTextsObject();

        foreach ($o_texts as $key => $value) {
            $this->o_texts->add($value, $key);
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
        return $this->getTextsObject()->get($key, $exception) ?? $default ?? $key;
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
