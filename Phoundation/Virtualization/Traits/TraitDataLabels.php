<?php

/**
 * Trait TraitDataLabels
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Virtualization
 */


declare(strict_types=1);

namespace Phoundation\Virtualization\Traits;


trait TraitDataLabels
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
     * Sets the labels
     *
     * @param array $labels
     *
     * @return static
     */
    public function setLabels(array $labels): static
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
     * @param array|string $labels
     *
     * @return static
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
