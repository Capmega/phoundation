<?php

declare(strict_types=1);

namespace Phoundation\Virtualization\Kubernetes\Traits;

/**
 * Trait TraitDataAnnotations
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openannotations.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataAnnotations
{
    /**
     * Annotations
     *
     * @var array $annotations
     */
    protected array $annotations = [];


    /**
     * Returns the annotations
     *
     * @return array|null
     */
    public function getAnnotations(): ?array
    {
        return $this->annotations;
    }


    /**
     * Sets the annotations
     *
     * @param array|null $annotations
     *
     * @return static
     */
    public function setAnnotations(?array $annotations): static
    {
        $this->annotations = $annotations;

        return $this;
    }


    /**
     * Clears the annotations
     *
     * @return static
     */
    public function clearAnnotations(): static
    {
        $this->annotations = [];

        return $this;
    }


    /**
     * Adds the specified annotations
     *
     * @param array|null $annotations
     *
     * @return static
     */
    public function addAnnotations(?array $annotations): static
    {
        foreach ($annotations as $key => $value) {
            $this->addAnnotation($key, $value);
        }

        return $this;
    }


    /**
     * Adds the specified annotation
     *
     * @param string     $key
     * @param string|int $value
     *
     * @return static
     */
    public function addAnnotation(string $key, string|int $value): static
    {
        $this->annotations[$key] = $value;

        return $this;
    }
}
