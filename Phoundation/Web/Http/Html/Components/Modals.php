<?php

namespace Phoundation\Web\Http\Html\Components;

use Phoundation\Core\Arrays;
use Phoundation\Exception\OutOfBoundsException;



/**
 * Phoundation Modals class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Modals extends ElementsBlock
{
    /**
     * An optional list of modals that are required before rendering can be done
     *
     * @var array|null $required
     */
    protected ?array $required = null;



    /**
     * Add a new modal
     *
     * @param string $identifier
     * @param Modal $modal
     * @return $this
     */
    public function add(string $identifier, Modal $modal): static
    {
        $this->source[$identifier] = $modal;
        return $this;
    }



    /**
     * Remove the specified modal
     *
     * @param string $identifier
     * @return $this
     */
    public function remove(string $identifier): static
    {
        unset($this->source[$identifier]);
        return $this;
    }



    /**
     * Returns true if the specified modal exists
     *
     * @param array|string|null $required
     * @return Modals
     */
    public function setRequired(array|string|null $required): static
    {
        if (!$required) {
            $this->required = null;
        } else {
            $this->required = Arrays::force($required);
        }

        return $this;
    }



    /**
     * Render the modals and return the HTML
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if ($this->required) {
            // Ensure that these modals are available
            foreach ($this->required as $required) {
                if (!array_key_exists($required, $this->source)) {
                    throw new OutOfBoundsException(tr('Cannot render modals, the required modal ":modal" is not set', [
                        ':modal' => $required
                    ]));
                }
            }
        }

        $this->render = '';

        foreach ($this->source as $modal) {
            $this->render .= $modal->render();
        }

        return parent::render();
    }
}