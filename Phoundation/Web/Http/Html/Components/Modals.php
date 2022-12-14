<?php

namespace Phoundation\Web\Http\Html\Components;

use Iterator;
use Phoundation\Core\Arrays;
use Phoundation\Exception\NotExistsException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Elements\ElementsBlock;



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
class Modals extends ElementsBlock implements Iterator
{
    /**
     * The list of modal
     *
     * @var array $modals
     */
    protected array $modals = [];

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
        $this->modals[$identifier] = $modal;
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
        unset($this->modals[$identifier]);
        return $this;
    }



    /**
     * Returns true if the specified modal exists
     *
     * @param string $identifier
     * @return bool
     */
    public function exists(string $identifier): bool
    {
        return array_key_exists($identifier, $this->modals);
    }



    /**
     * Returns true if the specified modal exists
     *
     * @param string $identifier
     * @return Modal
     */
    public function get(string $identifier): Modal
    {
        if (!$this->exists($identifier)) {
            throw new NotExistsException(tr('The specified modal ":identifier" does not exist', [
                ':identifier' => $identifier
            ]));
        }
        
        return $this->modals[$identifier];
    }



    /**
     * Returns true if the specified modal exists
     *
     * @param array|string|null $required
     * @return bool
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
     * Iterator methods
     *
     * @return mixed
     */
    public function current(): mixed
    {
        return current($this->modals);
    }

    public function next(): void
    {
        next($this->modals);
    }

    public function key(): mixed
    {
        return key($this->modals);
    }

    public function valid(): bool
    {
        return valid($this->modals);
    }

    public function rewind(): void
    {
        reset($this->modals);
    }



    /**
     * Render the modals and return the HTML
     *
     * @return string
     */
    public function render(): string
    {
        if ($this->required) {
            // Ensure that these modals are available
            foreach ($this->required as $required) {
                if (!array_key_exists($required, $this->modals)) {
                    throw new OutOfBoundsException(tr('Cannot render modals, the required modal ":modal" is not set', [
                        ':modal' => $required
                    ]));
                }
            }
        }

        $html = '';

        foreach ($this->modals as $modal) {
            $html .= $modal->render();
        }

        return $html;
    }
}