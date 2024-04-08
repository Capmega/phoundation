<?php

declare(strict_types=1);

namespace Phoundation\Virtualization\Kubernetes\Traits;

/**
 * Trait TraitDataLabels
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://openlabels.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataLabels
{
    /**
     * Labels
     *
     * @var array $labels
     */
    protected array $labels = [];


    /**
     * Returns the labels
     *
     * @return array|null
     */
    public function getLabels(): ?array
    {
        return $this->labels;
    }


    /**
     * Sets the labels
     *
     * @param array|null $labels
     *
     * @return static
     */
    public function setLabels(?array $labels): static
    {
        $this->labels = $labels;

        return $this;
    }


    /**
     * Clears the labels
     *
     * @return static
     */
    public function clearLabels(): static
    {
        $this->labels = [];

        return $this;
    }


    /**
     * Adds the specified labels
     *
     * @param array|null $labels
     *
     * @return static
     */
    public function addLabels(?array $labels): static
    {
        foreach ($labels as $key => $value) {
            $this->addLabel($key, $value);
        }

        return $this;
    }


    /**
     * Adds the specified label
     *
     * @param string     $key
     * @param string|int $value
     *
     * @return static
     */
    public function addLabel(string $key, string|int $value): static
    {
        $this->labels[$key] = $value;

        return $this;
    }
}
