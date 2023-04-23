<?php

namespace Phoundation\Virtualization\Traits;


/**
 * Trait DataLabels
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Virtualization
 */
trait DataLabels
{
    /**
     * The kubernetes labels
     *
     * @var array $labels
     */
    protected array $labels = [];

    /**
     * Returns the labels
     *
     * @return array
     */
    public function getLabels(): array
    {
        return $this->labels;
    }

    /**
     * Clears the labels
     *
     * @return $this
     */
    public function clearLabels(): static
    {
        $this->labels = [];
        return $this;
    }

    /**
     * Sets the labels
     *
     * @param array $labels
     * @return $this
     */
    public function setLabels(array $labels): static
    {
        $this->labels = $labels;
        return $this;
    }

    /**
     * Adds the specified labels
     *
     * @param array|string $labels
     * @return $this
     */
    public function addLabels(array|string $labels): static
    {
        if (is_array($labels)) {
            foreach ($labels as $label) {
                $this->addLabels($label);
            }

        } else {
            $this->labels[] = $labels;
        }

        return $this;
    }
}