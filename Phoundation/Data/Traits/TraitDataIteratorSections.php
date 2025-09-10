<?php

/**
 * Trait TraitDataSections
 *
 * This trait adds support for an Iterator that manages a list of sections
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
use Phoundation\Web\Html\Components\Interfaces\RenderInterface;
use Stringable;


trait TraitDataIteratorSections
{
    /**
     * Tracks the sections iterator
     *
     * @var IteratorInterface $o_sections
     */
    protected IteratorInterface $o_sections;


    /**
     * Returns the sections iterator
     *
     * @return IteratorInterface
     */
    public function getSectionsObject(): IteratorInterface
    {
        if (empty($this->o_sections)) {
            $this->o_sections = new Iterator();
        }

        return $this->o_sections;
    }


    /**
     * Returns the sections iterator
     *
     * @param IteratorInterface|array $o_sections
     *
     * @return static
     */
    public function setSectionsObject(IteratorInterface|array $o_sections): static
    {
        $this->o_sections = new Iterator($o_sections);
        return $this;
    }


    /**
     * Adds the specified sections iterator
     *
     * @param IteratorInterface|array $o_sections
     *
     * @return static
     */
    public function addSectionsObject(IteratorInterface|array $o_sections): static
    {
        $this->getSectionsObject();

        foreach ($o_sections as $key => $value) {
            $this->o_sections->add($value, $key);
        };

        return $this;
    }


    /**
     * Returns the actual section for the specified section key
     *
     * @param string      $key
     * @param bool        $exception
     * @param string|null $default
     *
     * @return string|null
     */
    public function getSection(string $key, bool $exception = false, ?string $default = null): ?string
    {
        return $this->getSectionsObject()->get($key, $exception) ?? $default;
    }


    /**
     * Sets the specified section value for the specified key
     *
     * @param RenderInterface|string $value
     * @param string                 $key
     * @param bool                   $skip_null_values
     *
     * @return static
     */
    public function setSection(RenderInterface|string $value, string $key, bool $skip_null_values = true): static
    {
        if ($value instanceof RenderInterface) {
            $value = $value->render();
        }

        $this->getSectionsObject()->set($value, $key, $skip_null_values);
        return $this;
    }


    /**
     * Adds the specified section to the specified section
     *
     * @param RenderInterface|string|null $section
     * @param string|null                 $key
     *
     * @return static
     */
    public function addToSection(RenderInterface|string|null $section, ?string $key = null): static
    {
        $key     = get_null($key);
        $section = $this->getSection($section) . $section;

        $this->getSectionsObject()->set($section, $key ?? $section);
        return $this;
    }
}
